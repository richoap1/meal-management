<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\StoreProduct;
use App\Services\BodyMassIndexAssessment;
use App\Services\PlannerProductCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MealPrepController extends Controller
{
    public function index()
    {
        return view('planner');
    }

    public function getEvents()
    {
        $events = auth()->user()->scheduledMeals()->get()->map(fn ($meal) => [
            'id' => $meal->pivot->id,
            'meal_id' => $meal->id,
            'title' => $meal->name,
            'start' => $meal->pivot->scheduled_date,
            'allDay' => true,
            'extendedProps' => ['calories' => $meal->calories ?? 0, 'color' => $meal->color ?? 'blue'],
        ]);

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['meal_id' => 'required|integer', 'date' => 'required|date']);
        auth()->user()->scheduledMeals()->attach($data['meal_id'], ['scheduled_date' => $data['date']]);

        return response()->json(['status' => 'success']);
    }

    public function generatePlan(Request $request, BodyMassIndexAssessment $bmiAssessment, PlannerProductCategory $productCategory)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'budget' => 'required|numeric|min:25000|max:1000000',
            'is_subscribed' => 'nullable|boolean',
            'sport' => 'required|in:binaraga,cycling,runner,normal',
            'weight' => 'required|numeric|min:20|max:300',
            'age' => 'required|numeric|min:13|max:100',
            'height' => 'required|numeric|min:100|max:230',
            'sex' => 'required|in:male,female',
            'muscle_groups' => 'exclude_unless:sport,binaraga|required|array|min:1',
            'muscle_groups.*' => 'in:Dada,Bahu,Lengan,Perut,Punggung,Glutes,Kaki',
            'equipment' => 'required|array|min:1',
            'equipment.*' => 'in:kompor,rice_cooker,oven,air_fryer,steamer,blender,knife,measuring_tools,food_storage',
            'excluded_foods' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'end_date' => 'required_if:is_subscribed,1|nullable|date|after_or_equal:start_date',
        ]);
        if (! array_intersect($data['equipment'], ['kompor', 'rice_cooker', 'oven', 'air_fryer', 'steamer'])) {
            throw ValidationException::withMessages([
                'equipment' => 'Pilih minimal satu alat masak utama yang tersedia di rumah.',
            ]);
        }

        $isSubscribed = Auth::check() ? (bool) Auth::user()->is_subscribed : (bool) ($data['is_subscribed'] ?? $request->session()->get('is_subscribed', false));
        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = $isSubscribed ? Carbon::parse($data['end_date'] ?? $data['start_date'])->startOfDay() : $startDate->copy();
        $calendarDays = $startDate->diff($endDate)->days + 1;
        $days = $isSubscribed ? $calendarDays : 1;
        $bmi = $bmiAssessment->assess(
            (float) $data['weight'],
            (float) $data['age'],
            (float) $data['height'],
            $data['sex'],
            $data['sport'],
        );
        $bmr = (10 * $data['weight']) + (6.25 * $data['height']) - (5 * $data['age']) + ($data['sex'] === 'male' ? 5 : -161);
        $activityFactor = match ($data['sport']) {
            'binaraga' => 1.55,
            'cycling' => 1.725,
            'runner' => 1.65,
            'normal' => 1.2,
        };
        $maintenanceCalories = (int) round($bmr * $activityFactor);
        $calorieAdjustment = match ($bmi['nutrition_mode']) {
            'weight_management' => 0.9,
            'balanced_weight_support' => $bmi['age_group'] === 'adult' ? 1.05 : 1,
            default => 1,
        };
        $dailyCalories = (int) round($maintenanceCalories * $calorieAdjustment);
        $carbohydrateRatio = match ($data['sport']) {
            'binaraga' => .45,
            'cycling' => .60,
            'runner' => .55,
            'normal' => .50,
        };
        $proteinRatio = match ($data['sport']) {
            'binaraga' => .35,
            'cycling' => .15,
            'runner' => .20,
            'normal' => .20,
        };
        $maxCarbs = (int) round(($dailyCalories * $carbohydrateRatio) / 4);
        $proteinTarget = (int) round(($dailyCalories * $proteinRatio) / 4);
        $inventory = ['Karbohidrat' => [], 'Protein' => [], 'Sayuran' => []];
        $totalCost = 0;
        $nutrition = match ($bmi['nutrition_mode']) {
            'weight_management' => ['Karbohidrat' => ['calories' => 190, 'carbs' => 35], 'Protein' => ['calories' => 220, 'carbs' => 3], 'Sayuran' => ['calories' => 120, 'carbs' => 18]],
            'balanced_weight_support' => ['Karbohidrat' => ['calories' => 290, 'carbs' => 52], 'Protein' => ['calories' => 250, 'carbs' => 3], 'Sayuran' => ['calories' => 80, 'carbs' => 12]],
            default => ['Karbohidrat' => ['calories' => 250, 'carbs' => 45], 'Protein' => ['calories' => 220, 'carbs' => 3], 'Sayuran' => ['calories' => 80, 'carbs' => 12]],
        };
        $excludedTerms = $this->excludedFoodTerms($data['excluded_foods'] ?? '');
        $availableProducts = StoreProduct::where('store_id', $data['store_id'])
            ->where('is_available', true)
            ->get()
            ->reject(fn (StoreProduct $product) => $this->isExcludedFood($product, $excludedTerms))
            ->filter(fn (StoreProduct $product) => $product->price <= $data['budget']);
        $itemsByCategory = collect(array_keys($inventory))->mapWithKeys(fn (string $category) => [
            $category => $availableProducts
                ->filter(fn (StoreProduct $product) => $productCategory->resolve(
                    $product->catalog_category ?? '',
                    $product->subcategory ?? '',
                    $product->product_name,
                    $product->category,
                ) === $category)
                ->filter(fn (StoreProduct $product) => $category !== 'Karbohidrat' || $this->canCookCarbohydrate($product, $data['equipment']))
                ->sortBy('price')
                ->values(),
        ]);
        $buyTarget = $isSubscribed
            ? min(6, max(3, (int) floor($data['budget'] / 50000)))
            : min(6, max(3, (int) floor($data['budget'] / 50000)));

        $mealPlan = [];
        $dailyShoppingLists = [];
        $mealTypes = ['Breakfast', 'Lunch', 'Dinner'];
        $totalCost = 0;
        $totalCalories = 0;
        $totalCarbs = 0;
        for ($day = 1; $day <= $days; $day++) {
            $dateLabel = $startDate->copy()->addDays($day - 1)->format('D, d M Y');
            $inventory = ['Karbohidrat' => [], 'Protein' => [], 'Sayuran' => []];
            $dayTotalCost = 0;
            $selectedProductIds = [];
            $addProduct = function (StoreProduct $product, string $category) use (&$inventory, &$dayTotalCost, &$selectedProductIds, $nutrition): void {
                $inventory[$category][] = [
                    'name' => $product->product_name,
                    'brand' => $product->brand,
                    'package' => $product->package ?: $this->packageLabel($category, $product->product_name),
                    'price' => (float) $product->price,
                    'image_url' => $this->productImage($category),
                    'calories' => $nutrition[$category]['calories'],
                    'carbs' => $nutrition[$category]['carbs'],
                ];
                $dayTotalCost += $product->price;
                $selectedProductIds[] = $product->id;
            };

            foreach ($itemsByCategory as $category => $items) {
                $cheapestItem = $items->first();
                if ($cheapestItem && $dayTotalCost + $cheapestItem->price <= $data['budget']) {
                    $addProduct($cheapestItem, $category);
                }
            }

            do {
                $addedProduct = false;
                foreach ($itemsByCategory as $category => $items) {
                    if (count($inventory[$category]) >= $buyTarget || $items->isEmpty()) {
                        continue;
                    }

                    $rotationOffset = ($day - 1) % $items->count();
                    $rotatedItems = $items->slice($rotationOffset)->concat($items->take($rotationOffset));
                    $nextItem = $rotatedItems->first(fn (StoreProduct $product) => ! in_array($product->id, $selectedProductIds, true)
                        && $dayTotalCost + $product->price <= $data['budget']);
                    if ($nextItem) {
                        $addProduct($nextItem, $category);
                        $addedProduct = true;
                    }
                }
            } while ($addedProduct);

            $dailyShoppingLists[$dateLabel] = [
                'budget' => (float) $data['budget'],
                'total_cost' => $dayTotalCost,
                'remaining_budget' => $data['budget'] - $dayTotalCost,
                'items' => $inventory,
            ];
            $totalCost += $dayTotalCost;

            $shuffledInventory = collect($inventory)->map(fn ($items) => collect($items)->shuffle());
            $categoryNames = array_keys($inventory);
            $selectionStrides = [];
            $stride = 1;
            foreach (['Protein', 'Sayuran', 'Karbohidrat'] as $category) {
                $selectionStrides[$category] = $stride;
                $stride *= max(1, $shuffledInventory[$category]->count());
            }
            $mealIndex = 0;
            $dayCalories = 0;
            $dayCarbs = 0;
            foreach ($mealTypes as $type) {
                $missingCategories = collect($inventory)->filter(fn (array $items) => empty($items))->keys();
                if ($missingCategories->isNotEmpty()) {
                    $mealPlan[$dateLabel][$type] = 'Belum ada bahan untuk kategori '.$missingCategories->implode(', ').' yang sesuai dengan pantangan dan anggaran.';

                    continue;
                }
                $selected = [];
                foreach ($categoryNames as $category) {
                    $items = $shuffledInventory[$category];
                    $selected[$category] = $items->get(intdiv($mealIndex, $selectionStrides[$category]) % $items->count());
                }
                $selected = collect($selected);
                $mealCalories = $selected->sum('calories');
                $mealCarbs = $selected->sum('carbs');
                $recipeIngredients = $selected->map(fn ($item, $category) => [
                    'category' => $category,
                    'name' => $item['name'],
                    'quantity' => $this->portionFor($category, $item['name'], $bmi['nutrition_mode'], $data['sport']),
                ])->values()->all();
                $recipe = $this->recipeFor($type, $recipeIngredients, $data['equipment'], $bmi['nutrition_mode']);
                $mealPlan[$dateLabel][$type] = [
                    'menu' => $selected->pluck('name')->values(),
                    'calories' => $mealCalories,
                    'carbs' => $mealCarbs,
                    'image_url' => $this->recipeImage($type),
                    'recipe' => $recipe,
                ];
                $mealIndex++;
                $dayCalories += $mealCalories;
                $dayCarbs += $mealCarbs;
            }
            $totalCalories += $dayCalories;
            $totalCarbs += $dayCarbs;
        }
        $totalBudget = $data['budget'] * $days;
        $dailyPlanCalories = (int) round($totalCalories / $days);
        $dailyPlanCarbs = (int) round($totalCarbs / $days);

        return response()->json([
            'status' => 'success',
            'subscription_status' => $isSubscribed ? 'Weekly Plan' : 'Daily Plan',
            'daily_budget' => (float) $data['budget'],
            'total_budget' => $totalBudget,
            'daily_shopping_lists' => $dailyShoppingLists,
            'shopping_list' => reset($dailyShoppingLists)['items'],
            'total_cost' => $totalCost,
            'remaining_budget' => $totalBudget - $totalCost,
            'total_calories' => $totalCalories,
            'daily_calories' => $dailyPlanCalories,
            'daily_carbs' => $dailyPlanCarbs,
            'max_carbs_per_day' => $maxCarbs,
            'recommended_calories' => $dailyCalories,
            'protein_target' => $proteinTarget,
            'activity_factor' => $activityFactor,
            'maintenance_calories' => $maintenanceCalories,
            'bmi_assessment' => $bmi,
            'nutrition_mode' => $bmi['nutrition_mode'],
            'diet_mode_enabled' => $bmi['automatic_diet'],
            'sport' => $data['sport'],
            'muscle_groups' => $data['muscle_groups'] ?? [],
            'excluded_foods' => $excludedTerms,
            'equipment' => $data['equipment'],
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'meal_plan' => $mealPlan,
        ]);
    }

    public function umkmMenus(Request $request, BodyMassIndexAssessment $bmiAssessment)
    {
        $data = $request->validate([
            'sport' => ['required', 'in:binaraga,cycling,runner,normal'],
            'excluded_foods' => ['nullable', 'string', 'max:1000'],
            'weight' => ['required_with:age,height,sex', 'numeric', 'min:20', 'max:300'],
            'age' => ['required_with:weight,height,sex', 'numeric', 'min:13', 'max:100'],
            'height' => ['required_with:weight,age,sex', 'numeric', 'min:100', 'max:230'],
            'sex' => ['required_with:weight,age,height', 'in:male,female'],
        ]);
        $excludedTerms = $this->excludedFoodTerms($data['excluded_foods'] ?? '');
        $hasBodyProfile = isset($data['weight'], $data['age'], $data['height'], $data['sex']);
        $bmi = $hasBodyProfile
            ? $bmiAssessment->assess((float) $data['weight'], (float) $data['age'], (float) $data['height'], $data['sex'], $data['sport'])
            : null;
        $hideUnverifiedMenus = $bmi !== null && in_array($bmi['nutrition_mode'], [
            'weight_management',
            'balanced_weight_management',
            'balanced_weight_support',
        ], true);

        if ($hideUnverifiedMenus) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'message' => 'Menu UMKM disembunyikan karena informasi gizi dan porsinya belum dapat diverifikasi sesuai mode plan otomatis.',
            ]);
        }

        $menus = Meal::query()
            ->where('is_available', true)
            ->whereNotNull('seller_name')
            ->where('seller_name', '!=', '')
            ->get()
            ->filter(function (Meal $meal) use ($data, $excludedTerms): bool {
                if (! in_array($data['sport'], $meal->sport_segments ?? [], true)) {
                    return false;
                }
                if (empty($excludedTerms)) {
                    return true;
                }
                if (trim((string) $meal->ingredients) === '') {
                    return false;
                }

                return ! $this->containsExcludedTerm(implode(' ', [
                    $meal->name,
                    $meal->ingredients,
                    $meal->description ?? '',
                ]), $excludedTerms);
            })
            ->values()
            ->map(fn (Meal $meal) => [
                'id' => $meal->id,
                'name' => $meal->name,
                'seller_name' => $meal->seller_name,
                'description' => $meal->description,
                'price' => $meal->price,
                'calories' => $meal->calories,
                'carbs' => $meal->carbs,
                'type' => $meal->type,
                'image_url' => $meal->image_path ? asset('storage/'.$meal->image_path) : $this->recipeImage(ucfirst($meal->type)),
                'ingredients' => preg_split('/\r\n|\r|\n/', $meal->ingredients ?: '', -1, PREG_SPLIT_NO_EMPTY),
                'instructions' => preg_split('/\r\n|\r|\n/', $meal->instructions ?: '', -1, PREG_SPLIT_NO_EMPTY),
            ]);

        return response()->json(['status' => 'success', 'data' => $menus]);
    }

    private function productImage(string $category): string
    {
        return match ($category) {
            'Protein' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=240&q=80',
            'Sayuran' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=240&q=80',
            default => 'https://images.unsplash.com/photo-1536304993881-ff6e9e1d4b8c?auto=format&fit=crop&w=240&q=80',
        };
    }

    private function recipeImage(string $type): string
    {
        return match ($type) {
            'Breakfast' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=640&q=80',
            'Lunch' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=640&q=80',
            default => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=640&q=80',
        };
    }

    private function recipeFor(string $type, array $ingredients, array $equipment, string $nutritionMode): array
    {
        $ingredientNames = collect($ingredients)->pluck('name')->all();
        $title = match ($type) {
            'Breakfast' => 'Sarapan bowl',
            'Lunch' => 'Lunch bowl',
            default => 'Dinner bowl',
        };

        return [
            'title' => $this->recipeTitlePrefix($nutritionMode).$title.': '.implode(' + ', $ingredientNames),
            'ingredients' => $ingredients,
            'servings' => 1,
            'steps' => $this->cookingSteps($equipment, $ingredients),
            'equipment' => $equipment,
            'nutrition_mode' => $nutritionMode,
        ];
    }

    private function recipeTitlePrefix(string $nutritionMode): string
    {
        return match ($nutritionMode) {
            'weight_management' => 'Diet pengelolaan berat · ',
            'balanced_weight_management' => 'Menu seimbang remaja · ',
            'balanced_weight_support' => 'Menu dukungan berat badan · ',
            default => '',
        };
    }

    private function cookingSteps(array $equipment, array $ingredients): array
    {
        $ingredientsByCategory = collect($ingredients)->keyBy('category');
        $carbohydrate = $ingredientsByCategory->get('Karbohidrat')['name'] ?? 'karbohidrat pilihan';
        $protein = $ingredientsByCategory->get('Protein')['name'] ?? 'protein pilihan';
        $vegetable = $ingredientsByCategory->get('Sayuran')['name'] ?? 'sayuran pilihan';
        $prepStep = in_array('knife', $equipment, true)
            ? 'Cuci dan potong '.$vegetable.' menggunakan pisau dan talenan.'
            : 'Cuci bahan dan siapkan '.$vegetable.' dalam ukuran saji.';
        if (in_array('measuring_tools', $equipment, true)) {
            $prepStep .= ' Takar setiap bahan sesuai porsi resep.';
        }
        $storageStep = in_array('food_storage', $equipment, true)
            ? ['Simpan bahan yang belum dimasak dalam wadah makanan yang tersedia.']
            : [];

        if (in_array('kompor', $equipment, true)) {
            return array_merge(
                [
                    $prepStep,
                    'Masak protein hingga matang menggunakan kompor dan wajan.',
                    'Masak '.$carbohydrate.' sesuai petunjuk kemasan, tumis '.$vegetable.
                        ' sampai matang, lalu sajikan bersama '.$protein.'.',
                ],
                $storageStep,
            );
        }
        if (in_array('rice_cooker', $equipment, true)) {
            return array_merge(
                [
                    $prepStep.' Masak '.$carbohydrate.' di rice cooker.',
                    'Masak '.$protein.' hingga matang di rice cooker.',
                    'Tambahkan '.$vegetable.' dan masak hingga matang, lalu sajikan.',
                ],
                $storageStep,
            );
        }
        if (in_array('oven', $equipment, true)) {
            return array_merge(
                [
                    $prepStep.' Panaskan oven sesuai petunjuk alat.',
                    'Panggang '.$carbohydrate.', '.$protein.', dan '.$vegetable.
                        ' hingga matang merata, lalu sajikan.',
                ],
                $storageStep,
            );
        }
        if (in_array('air_fryer', $equipment, true)) {
            return array_merge(
                [
                    $prepStep.' Bumbui '.$protein.' secukupnya.',
                    'Masak '.$carbohydrate.', '.$protein.', dan '.$vegetable.
                        ' di air fryer hingga matang, lalu sajikan.',
                ],
                $storageStep,
            );
        }

        return array_merge(
            [
                $prepStep.' Atur bahan dalam wadah tahan panas.',
                'Kukus '.$carbohydrate.', '.$protein.', dan '.$vegetable.
                    ' hingga matang, lalu sajikan hangat.',
            ],
            $storageStep,
        );
    }

    private function canCookCarbohydrate(StoreProduct $product, array $equipment): bool
    {
        if (array_intersect($equipment, ['kompor', 'rice_cooker'])) {
            return true;
        }

        $productName = mb_strtolower($product->product_name);

        foreach (['kentang', 'ubi', 'jagung'] as $airFryerFriendlyIngredient) {
            if (str_contains($productName, $airFryerFriendlyIngredient)) {
                return true;
            }
        }

        return false;
    }

    private function excludedFoodTerms(string $excludedFoods): array
    {
        $aliases = [
            'seafood' => ['seafood', 'ikan', 'udang', 'cumi', 'kerang', 'kepiting'],
            'makanan laut' => ['seafood', 'ikan', 'udang', 'cumi', 'kerang', 'kepiting'],
            'dairy' => ['susu', 'keju', 'yogurt', 'butter', 'dairy'],
            'susu' => ['susu', 'keju', 'yogurt', 'butter'],
            'laktosa' => ['susu', 'keju', 'yogurt', 'butter', 'laktosa'],
            'lactose' => ['susu', 'keju', 'yogurt', 'butter', 'lactose'],
            'gluten' => ['gluten', 'gandum', 'tepung', 'roti', 'pasta', 'mie'],
            'kacang' => ['kacang', 'almond', 'mete', 'kenari'],
            'nuts' => ['kacang', 'almond', 'mete', 'kenari'],
            'telur' => ['telur', 'egg'],
            'egg' => ['telur', 'egg'],
            'ayam' => ['ayam', 'chicken'],
            'chicken' => ['ayam', 'chicken'],
        ];

        return collect(preg_split('/[,;\r\n]+/', $excludedFoods) ?: [])
            ->map(fn (string $term) => mb_strtolower(trim($term)))
            ->filter()
            ->flatMap(fn (string $term) => $aliases[$term] ?? [$term])
            ->unique()
            ->values()
            ->all();
    }

    private function isExcludedFood(StoreProduct $product, array $excludedTerms): bool
    {
        $searchableText = mb_strtolower(implode(' ', array_filter([
            $product->product_name,
            $product->subcategory,
        ])));

        return $this->containsExcludedTerm($searchableText, $excludedTerms);
    }

    private function containsExcludedTerm(string $searchableText, array $excludedTerms): bool
    {
        $searchableText = mb_strtolower($searchableText);

        foreach ($excludedTerms as $term) {
            if (str_contains($searchableText, $term)) {
                return true;
            }
        }

        return false;
    }

    private function portionFor(string $category, string $productName, string $nutritionMode, string $sport): string
    {
        if ($sport === 'binaraga') {
            return match ($category) {
                'Karbohidrat' => str_contains(strtolower($productName), 'beras') ? '75 g beras mentah' : '100 g',
                'Protein' => str_contains(strtolower($productName), 'telur') ? '3 butir' : '150 g',
                'Sayuran' => '100 g',
                default => 'secukupnya',
            };
        }

        if ($nutritionMode === 'weight_management') {
            return match ($category) {
                'Karbohidrat' => str_contains(strtolower($productName), 'beras') ? '60 g beras mentah' : '80 g',
                'Protein' => str_contains(strtolower($productName), 'telur') ? '2 butir' : '120 g',
                'Sayuran' => '150 g',
                default => 'secukupnya',
            };
        }

        if ($nutritionMode === 'balanced_weight_support') {
            return match ($category) {
                'Karbohidrat' => str_contains(strtolower($productName), 'beras') ? '90 g beras mentah' : '120 g',
                'Protein' => str_contains(strtolower($productName), 'telur') ? '3 butir' : '120 g',
                'Sayuran' => '100 g',
                default => 'secukupnya',
            };
        }

        return match ($category) {
            'Karbohidrat' => str_contains(strtolower($productName), 'beras') ? '75 g beras mentah' : '100 g',
            'Protein' => str_contains(strtolower($productName), 'telur') ? '2 butir' : '100 g',
            'Sayuran' => '100 g',
            default => 'secukupnya',
        };
    }

    private function packageLabel(string $category, string $productName): string
    {
        return match ($category) {
            'Karbohidrat' => str_contains(strtolower($productName), 'beras') ? '1 kemasan 5 kg' : '1 kemasan',
            'Protein' => str_contains(strtolower($productName), 'telur') ? '1 kg' : '1 kemasan',
            'Sayuran' => '1 kemasan',
            default => '1 kemasan',
        };
    }
}
