<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPrepPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_the_multistep_planner(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/planner')
            ->assertOk()
            ->assertSee('Pilih cabang olahraga')
            ->assertSee('Cycling')
            ->assertSee('Pilih bagian otot')
            ->assertSee('Peralatan di rumah')
            ->assertSee('Anggaran')
            ->assertSee('Menu UMKM untuk olahraga Anda');
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
}
