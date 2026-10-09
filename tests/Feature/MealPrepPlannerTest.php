<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPrepPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_the_multistep_planner(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get('/planner')
            ->assertOk();

        $response
            ->assertSee('Pilih olahraga atau program makan')
            ->assertSee('Cycling')
            ->assertSee('BMI digunakan sebagai skrining umum')
            ->assertSee('Peralatan di rumah')
            ->assertSee('Anggaran')
            ->assertSee('Menu UMKM sesuai pilihan Anda');

        $this->assertMatchesRegularExpression(
            '/Aktivitas\s+normal.*Pilih\s+bagian otot/s',
            strip_tags($response->getContent()),
        );
    }

    public function test_generates_sport_specific_calorie_and_carbohydrate_targets(): void
    {
        $store = Store::create([
            'name' => 'Toko Uji',
            'address' => 'Surabaya',
            'latitude' => -7.28,
            'longitude' => 112.79,
        ]);
        foreach (['Karbohidrat', 'Protein', 'Sayuran'] as $category) {
            StoreProduct::create([
                'store_id' => $store->id,
                'product_name' => $category.' sample',
                'price' => 10000,
                'category' => $category,
                'is_available' => true,
            ]);
        }

        $response = $this->postJson('/api/meal-prep/generate', [
            'store_id' => $store->id,
            'budget' => 90000,
            'sport' => 'cycling',
            'weight' => 60,
            'age' => 25,
            'height' => 165,
            'sex' => 'female',
            'equipment' => ['kompor', 'rice_cooker'],
            'muscle_groups' => [],
            'start_date' => now()->toDateString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('sport', 'cycling')
            ->assertJsonPath('recommended_calories', 2321)
            ->assertJsonPath('max_carbs_per_day', 348)
            ->assertJsonPath('protein_target', 87)
            ->assertJsonPath('equipment.0', 'kompor');

        $meal = collect($response->json('meal_plan'))->flatten(1)->firstWhere('recipe');
        $this->assertContains('Masak protein hingga matang menggunakan kompor dan wajan.', $meal['recipe']['steps']);
    }

    public function test_runner_and_cycling_accept_empty_muscle_selections(): void
    {
        $store = Store::create([
            'name' => 'Toko Uji',
            'address' => 'Surabaya',
            'latitude' => -7.28,
            'longitude' => 112.79,
        ]);
        foreach (['Karbohidrat', 'Protein', 'Sayuran'] as $category) {
            StoreProduct::create([
                'store_id' => $store->id,
                'product_name' => $category.' sample',
                'price' => 10000,
                'category' => $category,
                'is_available' => true,
            ]);
        }

        foreach (['cycling', 'runner'] as $sport) {
            $this->postJson('/api/meal-prep/generate', [
                'store_id' => $store->id,
                'budget' => 90000,
                'sport' => $sport,
                'weight' => 60,
                'age' => 25,
                'height' => 165,
                'sex' => 'female',
                'equipment' => ['kompor'],
                'muscle_groups' => [],
                'start_date' => now()->toDateString(),
            ])->assertOk()
                ->assertJsonPath('sport', $sport)
                ->assertJsonPath('muscle_groups', []);
        }
    }

    public function test_normal_activity_uses_its_own_calorie_factor_and_umkm_segment(): void
    {
        $store = $this->createPlannerStore();
        Meal::create([
            'name' => 'Menu Aktivitas Normal',
            'seller_name' => 'Dapur Seimbang',
            'price' => 25000,
            'calories' => 500,
            'carbs' => 60,
            'type' => 'lunch',
            'sport_segments' => ['normal'],
            'is_available' => true,
        ]);
        Meal::create([
            'name' => 'Menu Runner',
            'seller_name' => 'Dapur Seimbang',
            'price' => 25000,
            'calories' => 500,
            'carbs' => 60,
            'type' => 'lunch',
            'sport_segments' => ['runner'],
            'is_available' => true,
        ]);

        $this->getJson('/api/meal-prep/umkm-menus?sport=normal&sex=female&weight=60&age=25&height=165')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Menu Aktivitas Normal');

        $this->postJson('/api/meal-prep/generate', $this->planPayload($store, ['sport' => 'normal']))
            ->assertOk()
            ->assertJsonPath('sport', 'normal')
            ->assertJsonPath('activity_factor', 1.2)
            ->assertJsonPath('nutrition_mode', 'balanced')
            ->assertJsonPath('bmi_assessment.category', 'healthy');
    }

    public function test_overweight_adults_of_both_sexes_get_weight_management_recipes_and_no_unverified_umkm_menus(): void
    {
        $store = $this->createPlannerStore();
        Meal::create([
            'name' => 'Menu tanpa label diet',
            'seller_name' => 'Dapur Lokal',
            'ingredients' => "Beras\nTempe\nBrokoli",
            'instructions' => 'Masak hingga matang.',
            'price' => 25000,
            'calories' => 700,
            'carbs' => 80,
            'type' => 'lunch',
            'sport_segments' => ['runner'],
            'is_available' => true,
        ]);

        foreach (['female', 'male'] as $sex) {
            $response = $this->postJson('/api/meal-prep/generate', $this->planPayload($store, [
                'sport' => 'runner',
                'sex' => $sex,
                'weight' => 82,
                'age' => 30,
                'height' => 165,
            ]))->assertOk()
                ->assertJsonPath('bmi_assessment.category', 'obesity')
                ->assertJsonPath('bmi_assessment.reference', 'BMI dewasa')
                ->assertJsonPath('nutrition_mode', 'weight_management')
                ->assertJsonPath('diet_mode_enabled', true);

            $this->assertLessThan($response->json('maintenance_calories'), $response->json('recommended_calories'));
            $recipes = collect($response->json('meal_plan'))->flatten(1)->pluck('recipe')->filter();
            $this->assertNotEmpty($recipes);
            $this->assertStringContainsString('Diet pengelolaan berat', $recipes->first()['title']);
            $this->assertContains('60 g beras mentah', collect($recipes->first()['ingredients'])->pluck('quantity')->all());
        }

        $this->postJson('/api/meal-prep/generate', $this->planPayload($store, [
            'sport' => 'runner',
            'sex' => 'female',
            'weight' => 70,
            'age' => 30,
            'height' => 165,
        ]))->assertOk()
            ->assertJsonPath('bmi_assessment.category', 'overweight')
            ->assertJsonPath('nutrition_mode', 'weight_management');

        $this->getJson('/api/meal-prep/umkm-menus?sport=runner&sex=female&weight=82&age=30&height=165')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('message', 'Menu UMKM disembunyikan karena informasi gizi dan porsinya belum dapat diverifikasi sesuai mode plan otomatis.');
    }

    public function test_adolescent_bmi_for_age_uses_sex_specific_who_cutoffs_without_calorie_restriction(): void
    {
        $store = $this->createPlannerStore();
        $weightAtBmi21 = 21 * ((165 / 100) ** 2);

        $femalePlan = $this->postJson('/api/meal-prep/generate', $this->planPayload($store, [
            'sport' => 'normal',
            'sex' => 'female',
            'weight' => $weightAtBmi21,
            'age' => 13,
            'height' => 165,
        ]))->assertOk()
            ->assertJsonPath('bmi_assessment.category', 'healthy')
            ->assertJsonPath('bmi_assessment.reference', 'WHO 2007 BMI-for-age')
            ->assertJsonPath('nutrition_mode', 'balanced');

        $malePlan = $this->postJson('/api/meal-prep/generate', $this->planPayload($store, [
            'sport' => 'normal',
            'sex' => 'male',
            'weight' => $weightAtBmi21,
            'age' => 13,
            'height' => 165,
        ]))->assertOk()
            ->assertJsonPath('bmi_assessment.category', 'overweight')
            ->assertJsonPath('nutrition_mode', 'balanced_weight_management')
            ->assertJsonPath('diet_mode_enabled', true);

        $this->assertSame($femalePlan->json('maintenance_calories'), $femalePlan->json('recommended_calories'));
        $this->assertSame($malePlan->json('maintenance_calories'), $malePlan->json('recommended_calories'));
        $maleRecipes = collect($malePlan->json('meal_plan'))->flatten(1)->pluck('recipe')->filter();
        $this->assertStringContainsString('Menu seimbang remaja', $maleRecipes->first()['title']);

        $fractionalAgePlan = $this->postJson('/api/meal-prep/generate', $this->planPayload($store, [
            'sport' => 'normal',
            'sex' => 'female',
            'weight' => 22.3 * ((165 / 100) ** 2),
            'age' => 13.5,
            'height' => 165,
        ]))->assertOk()
            ->assertJsonPath('bmi_assessment.category', 'overweight')
            ->assertJsonPath('bmi_assessment.age_group', 'adolescent');
    }

    public function test_underweight_adults_get_balanced_support_recipes_instead_of_a_weight_loss_diet(): void
    {
        $store = $this->createPlannerStore();

        $response = $this->postJson('/api/meal-prep/generate', $this->planPayload($store, [
            'sport' => 'normal',
            'sex' => 'female',
            'weight' => 45,
            'age' => 25,
            'height' => 165,
        ]))->assertOk()
            ->assertJsonPath('bmi_assessment.category', 'underweight')
            ->assertJsonPath('nutrition_mode', 'balanced_weight_support')
            ->assertJsonPath('diet_mode_enabled', false);

        $this->assertGreaterThan($response->json('maintenance_calories'), $response->json('recommended_calories'));
        $recipes = collect($response->json('meal_plan'))->flatten(1)->pluck('recipe')->filter();
        $this->assertStringContainsString('Menu dukungan berat badan', $recipes->first()['title']);
        $this->assertContains('90 g beras mentah', collect($recipes->first()['ingredients'])->pluck('quantity')->all());
    }

    public function test_bodybuilding_is_exempt_from_automatic_bmi_diet_selection(): void
    {
        $store = $this->createPlannerStore();
        Meal::create([
            'name' => 'Menu binaraga',
            'seller_name' => 'Dapur Atlet',
            'price' => 30000,
            'calories' => 650,
            'carbs' => 50,
            'type' => 'lunch',
            'sport_segments' => ['binaraga'],
            'is_available' => true,
        ]);

        $response = $this->postJson('/api/meal-prep/generate', $this->planPayload($store, [
            'sport' => 'binaraga',
            'weight' => 82,
            'age' => 30,
            'height' => 165,
            'muscle_groups' => ['Dada'],
        ]))->assertOk()
            ->assertJsonPath('bmi_assessment.category', 'exempt')
            ->assertJsonPath('nutrition_mode', 'sport_performance')
            ->assertJsonPath('diet_mode_enabled', false);

        $recipes = collect($response->json('meal_plan'))->flatten(1)->pluck('recipe')->filter();
        $this->assertStringNotContainsString('Diet pengelolaan berat', $recipes->first()['title']);

        $this->getJson('/api/meal-prep/umkm-menus?sport=binaraga&sex=female&weight=82&age=30&height=165')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Menu binaraga');
    }

    public function test_bodybuilding_gets_a_higher_daily_protein_target_and_portion_than_other_sports(): void
    {
        $store = $this->createPlannerStore();
        $payload = $this->planPayload($store);

        $bodybuilding = $this->postJson('/api/meal-prep/generate', array_merge($payload, [
            'sport' => 'binaraga',
            'muscle_groups' => ['Dada'],
        ]))->assertOk();
        $runner = $this->postJson('/api/meal-prep/generate', array_merge($payload, ['sport' => 'runner']))->assertOk();
        $cycling = $this->postJson('/api/meal-prep/generate', array_merge($payload, ['sport' => 'cycling']))->assertOk();
        $normal = $this->postJson('/api/meal-prep/generate', array_merge($payload, ['sport' => 'normal']))->assertOk();

        $this->assertGreaterThan($runner->json('protein_target'), $bodybuilding->json('protein_target'));
        $this->assertGreaterThan($cycling->json('protein_target'), $bodybuilding->json('protein_target'));
        $this->assertGreaterThan($normal->json('protein_target'), $bodybuilding->json('protein_target'));
        $bodybuildingRecipe = collect($bodybuilding->json('meal_plan'))->flatten(1)->firstWhere('recipe')['recipe'];
        $this->assertSame('150 g', collect($bodybuildingRecipe['ingredients'])->firstWhere('category', 'Protein')['quantity']);
    }

    public function test_excluded_foods_are_removed_from_plan_ingredients(): void
    {
        $store = Store::create([
            'name' => 'Toko Uji',
            'address' => 'Surabaya',
            'latitude' => -7.28,
            'longitude' => 112.79,
        ]);
        foreach ([
            ['Beras', 'Karbohidrat', 5000],
            ['Dada Ayam', 'Protein', 5000],
            ['Tempe', 'Protein', 6000],
            ['Brokoli', 'Sayuran', 5000],
        ] as [$name, $category, $price]) {
            StoreProduct::create([
                'store_id' => $store->id,
                'product_name' => $name,
                'price' => $price,
                'category' => $category,
                'is_available' => true,
            ]);
        }

        $response = $this->postJson('/api/meal-prep/generate', [
            'store_id' => $store->id,
            'budget' => 90000,
            'sport' => 'cycling',
            'weight' => 60,
            'age' => 25,
            'height' => 165,
            'sex' => 'female',
            'equipment' => ['kompor'],
            'muscle_groups' => [],
            'excluded_foods' => "ayam\nsusu",
            'start_date' => now()->toDateString(),
        ])->assertOk();

        $this->assertSame(
            ['Beras'],
            collect($response->json('shopping_list.Karbohidrat'))->pluck('name')->all(),
        );
        $this->assertSame(
            ['Tempe'],
            collect($response->json('shopping_list.Protein'))->pluck('name')->all(),
        );
        $this->assertNotContains('Dada Ayam', collect($response->json('meal_plan'))->flatten(1)->pluck('menu')->flatten()->all());
    }

    public function test_catalog_seeds_all_stores_with_distinct_brand_and_price_variants(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Store::count());
        $this->assertSame(1008, StoreProduct::count());
        foreach (Store::all() as $store) {
            $this->assertSame(252, $store->products()->count());
            $this->assertSame(252, $store->products()->whereNotNull('reference_sku')->distinct('reference_sku')->count('reference_sku'));
        }

        $catalogProducts = StoreProduct::all()->groupBy('reference_sku');
        $this->assertCount(252, $catalogProducts);

        foreach ($catalogProducts as $products) {
            $this->assertCount(4, $products);
            $this->assertCount(4, $products->pluck('brand')->unique());
            $this->assertCount(4, $products->pluck('price')->unique());
            $this->assertCount(4, $products->pluck('store_id')->unique());
        }

        $superIndo = Store::where('name', 'Superindo Pahlawan (Pusat)')->firstOrFail();
        $superIndoProduct = StoreProduct::where('reference_sku', 'SI-0001')
            ->get()
            ->firstWhere('store_id', $superIndo->id);
        $this->assertNotNull($superIndoProduct);
        $this->assertSame('Super Indo', $superIndoProduct->reference_source);
        $this->assertSame('Buah & Sayur', $superIndoProduct->catalog_category);
        $this->assertSame('Apel Fuji', $superIndoProduct->product_name);

        $admin = User::where('email', 'admin@planner.com')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.stores.products', $superIndo))
            ->assertOk()
            ->assertSee('SI-0001')
            ->assertSee('Super Indo')
            ->assertSee('Buah &amp; Sayur', false);

        $this->get(route('admin.stores.products.edit', [$superIndo, $superIndoProduct]))
            ->assertOk()
            ->assertSee('Reference SKU')
            ->assertSee('Catalog category')
            ->assertSee('Reference source');
    }

    public function test_daily_recipes_rotate_affordable_animal_proteins_without_misclassifying_bayam(): void
    {
        $this->seed(DatabaseSeeder::class);
        $store = Store::where('name', 'Superindo Pahlawan (Pusat)')->firstOrFail();
        $store->products()->where('reference_sku', 'SI-0017')->update(['category' => 'Protein']);
        $store->products()->where('reference_sku', 'SI-0084')->update(['category' => 'Protein']);

        $response = $this->postJson('/api/meal-prep/generate', $this->planPayload($store, [
            'budget' => 150000,
        ]))->assertOk();

        $proteins = collect($response->json('shopping_list.Protein'))->pluck('name');
        $meals = collect($response->json('meal_plan'))->flatten(1);
        $mealProteins = $meals->map(fn (array $meal) => collect($meal['recipe']['ingredients'])
            ->firstWhere('category', 'Protein')['name']);

        $this->assertLessThanOrEqual(150000, $response->json('total_cost'));
        $this->assertNotContains('Bayam', $proteins->all());
        $this->assertCount(3, $proteins->unique());
        $this->assertSame(3, $mealProteins->unique()->count());
        $this->assertContains('Hati sapi', $proteins->all());
        $this->assertContains('Dada ayam fillet', $proteins->all());
        $this->assertContains('Paha ayam tanpa tulang', $proteins->all());
    }

    public function test_weekly_plan_generates_many_distinct_recipes_from_store_products(): void
    {
        $this->seed(DatabaseSeeder::class);
        $store = Store::where('name', 'Hokky Supermarket Graha Family (Barat)')->firstOrFail();

        $response = $this->postJson('/api/meal-prep/generate', [
            'store_id' => $store->id,
            'budget' => 1000000,
            'is_subscribed' => 1,
            'sport' => 'cycling',
            'weight' => 60,
            'age' => 25,
            'height' => 165,
            'sex' => 'female',
            'equipment' => ['kompor', 'knife', 'measuring_tools', 'food_storage'],
            'muscle_groups' => [],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
        ])->assertOk();

        $meals = collect($response->json('meal_plan'))->flatMap(fn (array $day) => array_values($day));
        $recipes = $meals->pluck('recipe')->filter();
        $this->assertCount(21, $recipes);
        $this->assertCount(21, $recipes->pluck('title')->unique());
        $firstRecipe = $recipes->first();
        $this->assertSame(['kompor', 'knife', 'measuring_tools', 'food_storage'], $firstRecipe['equipment']);
        $this->assertStringContainsString('kompor dan wajan', implode(' ', $firstRecipe['steps']));
        $this->assertStringContainsString('Takar', $firstRecipe['steps'][0]);
        $this->assertStringContainsString('wadah makanan', $firstRecipe['steps'][array_key_last($firstRecipe['steps'])]);

        $storeProductNames = $store->products()->pluck('product_name')->all();
        foreach ($recipes as $recipe) {
            foreach ($recipe['ingredients'] as $ingredient) {
                $this->assertContains($ingredient['name'], $storeProductNames);
            }
        }
    }

    public function test_weekly_plan_applies_the_full_budget_to_each_day_and_varies_daily_shopping(): void
    {
        $this->seed(DatabaseSeeder::class);
        $store = Store::where('name', 'Superindo Pahlawan (Pusat)')->firstOrFail();

        $response = $this->postJson('/api/meal-prep/generate', array_merge(
            $this->planPayload($store, ['budget' => 150000]),
            [
                'is_subscribed' => 1,
                'start_date' => '2026-10-12',
                'end_date' => '2026-10-18',
            ],
        ))->assertOk();

        $dailyShopping = $response->json('daily_shopping_lists');
        $this->assertCount(7, $dailyShopping);
        $this->assertSame(150000, $response->json('daily_budget'));
        $this->assertSame(1050000, $response->json('total_budget'));
        $this->assertSame(1050000, $response->json('total_cost') + $response->json('remaining_budget'));
        $this->assertSame(
            (int) collect($dailyShopping)->sum('total_cost'),
            (int) $response->json('total_cost'),
        );

        $proteinLists = [];
        foreach ($dailyShopping as $day => $shoppingDay) {
            $this->assertLessThanOrEqual(150000, $shoppingDay['total_cost']);
            $this->assertSame(150000, $shoppingDay['total_cost'] + $shoppingDay['remaining_budget']);
            $proteinLists[] = collect($shoppingDay['items']['Protein'])->pluck('name')->all();
            $dailyProductNames = collect($shoppingDay['items'])->flatten(1)->pluck('name')->all();
            foreach ($response->json("meal_plan.$day") as $meal) {
                foreach ($meal['recipe']['ingredients'] as $ingredient) {
                    $this->assertContains($ingredient['name'], $dailyProductNames);
                }
            }
        }
        $this->assertGreaterThan(1, count(array_unique(array_map('serialize', $proteinLists))));
    }

    public function test_air_fryer_recipes_only_select_compatible_carbohydrates(): void
    {
        $store = Store::create([
            'name' => 'Toko Uji',
            'address' => 'Surabaya',
            'latitude' => -7.28,
            'longitude' => 112.79,
        ]);
        foreach ([
            ['Beras', 'Karbohidrat'],
            ['Kentang', 'Karbohidrat'],
            ['Dada Ayam', 'Protein'],
            ['Brokoli', 'Sayuran'],
        ] as [$name, $category]) {
            StoreProduct::create([
                'store_id' => $store->id,
                'product_name' => $name,
                'price' => 5000,
                'category' => $category,
                'is_available' => true,
            ]);
        }

        $response = $this->postJson('/api/meal-prep/generate', [
            'store_id' => $store->id,
            'budget' => 90000,
            'sport' => 'runner',
            'weight' => 60,
            'age' => 25,
            'height' => 165,
            'sex' => 'female',
            'equipment' => ['air_fryer'],
            'muscle_groups' => [],
            'start_date' => now()->toDateString(),
        ])->assertOk();

        $this->assertSame(
            ['Kentang'],
            collect($response->json('shopping_list.Karbohidrat'))->pluck('name')->all(),
        );
        $recipes = collect($response->json('meal_plan'))->flatten(1)->filter(fn (array $meal) => isset($meal['recipe']));
        $this->assertNotEmpty($recipes);
        $this->assertStringContainsString('air fryer', implode(' ', $recipes->first()['recipe']['steps']));
    }

    public function test_bodybuilding_plan_requires_a_target_muscle_group(): void
    {
        $store = Store::create([
            'name' => 'Toko Uji',
            'address' => 'Surabaya',
            'latitude' => -7.28,
            'longitude' => 112.79,
        ]);

        $this->postJson('/api/meal-prep/generate', [
            'store_id' => $store->id,
            'budget' => 90000,
            'sport' => 'binaraga',
            'weight' => 60,
            'age' => 25,
            'height' => 165,
            'sex' => 'female',
            'equipment' => ['kompor'],
            'start_date' => now()->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('muscle_groups');
    }

    public function test_plan_requires_at_least_one_cooking_appliance(): void
    {
        $store = Store::create([
            'name' => 'Toko Uji',
            'address' => 'Surabaya',
            'latitude' => -7.28,
            'longitude' => 112.79,
        ]);

        $this->postJson('/api/meal-prep/generate', [
            'store_id' => $store->id,
            'budget' => 90000,
            'sport' => 'runner',
            'weight' => 60,
            'age' => 25,
            'height' => 165,
            'sex' => 'female',
            'equipment' => ['knife', 'food_storage'],
            'start_date' => now()->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('equipment');
    }

    public function test_umkm_menus_are_filtered_by_sport_segment(): void
    {
        Meal::create([
            'name' => 'Menu Cycling',
            'seller_name' => 'Dapur Lokal',
            'price' => 25000,
            'calories' => 600,
            'carbs' => 80,
            'type' => 'lunch',
            'sport_segments' => ['cycling'],
            'is_available' => true,
        ]);
        Meal::create([
            'name' => 'Menu Binaraga',
            'seller_name' => 'Dapur Lokal',
            'price' => 30000,
            'calories' => 550,
            'carbs' => 40,
            'type' => 'dinner',
            'sport_segments' => ['binaraga'],
            'is_available' => true,
        ]);

        $this->getJson('/api/meal-prep/umkm-menus?sport=cycling')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Menu Cycling')
            ->assertJsonPath('data.0.seller_name', 'Dapur Lokal')
            ->assertJsonPath('data.0.carbs', 80);
    }

    public function test_umkm_menus_respect_food_restrictions_and_require_ingredient_details(): void
    {
        Meal::create([
            'name' => 'Menu udang',
            'seller_name' => 'Dapur Lokal',
            'ingredients' => "Nasi\nUdang",
            'instructions' => 'Masak dan sajikan.',
            'price' => 25000,
            'calories' => 600,
            'carbs' => 80,
            'type' => 'lunch',
            'sport_segments' => ['cycling'],
            'is_available' => true,
        ]);
        Meal::create([
            'name' => 'Menu ayam',
            'seller_name' => 'Dapur Lokal',
            'ingredients' => "Nasi\nAyam",
            'instructions' => 'Masak dan sajikan.',
            'price' => 25000,
            'calories' => 600,
            'carbs' => 80,
            'type' => 'lunch',
            'sport_segments' => ['cycling'],
            'is_available' => true,
        ]);
        Meal::create([
            'name' => 'Menu tanpa detail bahan',
            'seller_name' => 'Dapur Lokal',
            'price' => 25000,
            'calories' => 600,
            'carbs' => 80,
            'type' => 'lunch',
            'sport_segments' => ['cycling'],
            'is_available' => true,
        ]);

        $this->getJson('/api/meal-prep/umkm-menus?sport=cycling&excluded_foods=seafood')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Menu ayam');
    }

    public function test_admin_can_register_an_umkm_menu_for_a_sport_segment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/recipes', [
            'name' => 'Rice bowl runner',
            'seller_name' => 'Dapur Lari',
            'description' => 'Menu makan setelah berlari.',
            'ingredients' => "Nasi\nAyam\nSayuran",
            'instructions' => "Masak nasi.\nPanggang ayam.\nSajikan dengan sayuran.",
            'price' => 28000,
            'calories' => 520,
            'carbs' => 65,
            'type' => 'lunch',
            'sport_segments' => ['runner'],
            'is_available' => 1,
        ])->assertRedirect(route('admin.recipes.index'));

        $meal = Meal::where('seller_name', 'Dapur Lari')->firstOrFail();
        $this->assertSame(['runner'], $meal->sport_segments);
        $this->assertSame(65, $meal->carbs);
    }

    public function test_admin_can_register_an_umkm_menu_for_normal_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/recipes', [
            'name' => 'Menu aktivitas normal',
            'seller_name' => 'Dapur Seimbang',
            'description' => 'Menu harian seimbang.',
            'ingredients' => "Nasi\nTempe\nSayuran",
            'instructions' => "Masak nasi.\nPanggang tempe.\nSajikan dengan sayuran.",
            'price' => 25000,
            'calories' => 500,
            'carbs' => 60,
            'type' => 'lunch',
            'sport_segments' => ['normal'],
            'is_available' => 1,
        ])->assertRedirect(route('admin.recipes.index'));

        $meal = Meal::where('seller_name', 'Dapur Seimbang')->firstOrFail();
        $this->assertSame(['normal'], $meal->sport_segments);
    }

    private function createPlannerStore(): Store
    {
        $store = Store::create([
            'name' => 'Toko BMI Uji',
            'address' => 'Surabaya',
            'latitude' => -7.28,
            'longitude' => 112.79,
        ]);

        foreach ([
            ['Beras', 'Karbohidrat'],
            ['Tempe', 'Protein'],
            ['Brokoli', 'Sayuran'],
        ] as [$name, $category]) {
            StoreProduct::create([
                'store_id' => $store->id,
                'product_name' => $name,
                'price' => 5000,
                'category' => $category,
                'is_available' => true,
            ]);
        }

        return $store;
    }

    private function planPayload(Store $store, array $overrides = []): array
    {
        return array_merge([
            'store_id' => $store->id,
            'budget' => 90000,
            'sport' => 'cycling',
            'weight' => 60,
            'age' => 25,
            'height' => 165,
            'sex' => 'female',
            'equipment' => ['kompor'],
            'muscle_groups' => [],
            'start_date' => now()->toDateString(),
        ], $overrides);
    }
}
