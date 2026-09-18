<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MealPrepController extends Controller
{
    public function index()
    {
        return view('planner', ['meals' => Meal::all()]);
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

    public function generatePlan(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'budget' => 'required|numeric|min:25000|max:1000000',
            'is_subscribed' => 'nullable|boolean',
            'is_diet' => 'nullable|boolean',
            'weight' => 'required_if:is_diet,1|nullable|numeric|min:20|max:300',
            'age' => 'required_if:is_diet,1|nullable|integer|min:13|max:100',
            'height' => 'required_if:is_diet,1|nullable|numeric|min:100|max:230',
            'sex' => 'required_if:is_diet,1|nullable|in:male,female',
            'start_date' => 'required|date',
            'end_date' => 'required_if:is_subscribed,1|nullable|date|after_or_equal:start_date',
        ]);

        $isSubscribed = Auth::check() ? (bool) Auth::user()->is_subscribed : (bool) ($data['is_subscribed'] ?? $request->session()->get('is_subscribed', false));
        $isDiet = (bool) ($data['is_diet'] ?? false);
        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = $isSubscribed ? Carbon::parse($data['end_date'])->startOfDay() : $startDate->copy();
        $calendarDays = $startDate->diff($endDate)->days + 1;
        $days = $isSubscribed ? $calendarDays : 1;
        $bmi = $isDiet ? round($data['weight'] / (($data['height'] / 100) ** 2), 1) : null;
        $bmr = $isDiet
            ? (10 * $data['weight']) + (6.25 * $data['height']) - (5 * $data['age']) + ($data['sex'] === 'male' ? 5 : -161)
            : null;
        $maintenanceCalories = $isDiet ? round($bmr * 1.2) : null;
        $dailyCalories = $isDiet ? max($data['sex'] === 'male' ? 1500 : 1200, $maintenanceCalories - min(500, max(300, round($maintenanceCalories * .15)))) : null;
        $maxCarbs = $dailyCalories ? round(($dailyCalories * .45) / 4) : null;
        $inventory = ['Karbohidrat' => [], 'Protein' => [], 'Sayuran' => []];
        $totalCost = 0;
        $nutrition = ['Karbohidrat' => ['calories' => 250, 'carbs' => 45], 'Protein' => ['calories' => 220, 'carbs' => 3], 'Sayuran' => ['calories' => 80, 'carbs' => 12]];
        $itemsByCategory = collect(['Karbohidrat', 'Protein', 'Sayuran'])->mapWithKeys(fn ($category) => [$category => StoreProduct::where('store_id', $data['store_id'])->where('category', $category)->where('is_available', true)->get()]);
        $buyTarget = $isSubscribed
            ? min(6, max(3, (int) floor($data['budget'] / 150000)))
            : min(4, max(2, (int) floor($data['budget'] / 60000)));

        foreach ($itemsByCategory as $category => $items) {
            $availableItems = $items
                ->filter(fn ($item) => $item->price <= $data['budget'])
                ->shuffle()
                ->values();
            foreach ($availableItems->take($buyTarget) as $item) {
                if ($totalCost + $item->price > $data['budget']) continue;
                $inventory[$category][] = [
                    'name' => $item->product_name,
                    'package' => $this->packageLabel($category, $item->product_name),
                    'price' => (float) $item->price,
                    'image_url' => $this->productImage($category),
                    'calories' => $nutrition[$category]['calories'],
                    'carbs' => $nutrition[$category]['carbs'],
                ];
                $totalCost += $item->price;
            }
        }

        $mealPlan = [];
        $mealTypes = ['Breakfast', 'Lunch', 'Dinner'];
        $dailyPlanCalories = 0;
        $dailyPlanCarbs = 0;
        $shuffledInventory = collect($inventory)->map(fn ($items) => collect($items)->shuffle());
        $mealIndex = 0;
        $usedRecipes = ['Breakfast' => [], 'Lunch' => [], 'Dinner' => []];
        for ($day = 1; $day <= $days; $day++) {
            $dateLabel = $startDate->copy()->addDays($day - 1)->format('D, d M Y');
            foreach ($mealTypes as $type) {
                if (collect($inventory)->contains(fn ($items) => empty($items))) {
                    $mealPlan[$dateLabel][$type] = 'Budget terlalu rendah untuk menyusun menu sehat';
                    continue;
                }
                $selected = $shuffledInventory->map(fn ($items) => $items->get($mealIndex % $items->count()));
                $mealCalories = $selected->sum('calories');
                $mealCarbs = $selected->sum('carbs');
                if ($isDiet) {
                    $mealCalories = round($mealCalories * .7);
                    $mealCarbs = round($mealCarbs * .7);
                }
                $recipeIngredients = $selected->map(fn ($item, $category) => [
                    'name' => $item['name'],
                    'quantity' => $this->portionFor($category, $item['name']),
                ])->values()->all();
                $recipe = $this->recipeFor($type, $recipeIngredients, $usedRecipes[$type], $isDiet);
                $usedRecipes[$type][] = $recipe['title'];
                $mealPlan[$dateLabel][$type] = [
                    'menu' => $selected->pluck('name')->values(),
                    'calories' => $mealCalories,
                    'carbs' => $mealCarbs,
                    'image_url' => $this->recipeImage($type),
                    'recipe' => $recipe,
                ];
                $mealIndex++;
                if ($day === 1) { $dailyPlanCalories += $mealCalories; $dailyPlanCarbs += $mealCarbs; }
            }
        }

        return response()->json([
            'status' => 'success',
            'subscription_status' => $isSubscribed ? 'Weekly Plan' : 'Daily Plan',
            'shopping_list' => $inventory,
            'total_cost' => $totalCost,
            'remaining_budget' => $data['budget'] - $totalCost,
            'total_calories' => $dailyPlanCalories * $days,
            'daily_calories' => $dailyPlanCalories,
            'daily_carbs' => $dailyPlanCarbs,
            'max_carbs_per_day' => $maxCarbs,
            'bmi' => $bmi,
            'maintenance_calories' => $maintenanceCalories,
            'recommended_calories' => $dailyCalories,
            'weight_goal' => $bmi >= 25 ? 'Weight-loss target' : 'Healthy maintenance target',
            'diet' => $isDiet,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'meal_plan' => $mealPlan,
        ]);
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

    private function recipeFor(string $type, array $ingredients, array $usedTitles = [], bool $isDiet = false): array
    {
        $recipes = [
            'Breakfast' => [
                ['title' => 'Savory protein breakfast bowl', 'steps' => ['Masak karbohidrat sampai matang dan lembut.', 'Tumis protein dengan sedikit minyak dan bumbu.', 'Tambahkan sayuran, lalu sajikan dalam satu mangkuk.']],
                ['title' => 'Simple breakfast stir-fry', 'steps' => ['Potong semua bahan menjadi ukuran kecil.', 'Tumis protein dan karbohidrat sampai harum.', 'Masukkan sayuran terakhir agar tetap renyah.']],
                ['title' => 'Warm balanced breakfast plate', 'steps' => ['Siapkan karbohidrat sesuai takaran satu porsi.', 'Masak protein hingga matang sempurna.', 'Sajikan dengan sayuran segar atau kukus.']],
            ],
            'Lunch' => [
                ['title' => 'Healthy lunch rice bowl', 'steps' => ['Masak karbohidrat sampai matang.', 'Panggang atau tumis protein dengan bumbu pilihan.', 'Susun protein, karbohidrat, dan sayuran dalam mangkuk.']],
                ['title' => 'Colorful lunch plate', 'steps' => ['Rebus atau kukus sayuran selama 3 sampai 5 menit.', 'Masak protein sampai tidak berwarna merah muda.', 'Sajikan bersama karbohidrat dan sayuran.']],
                ['title' => 'Quick balanced lunch', 'steps' => ['Siapkan semua bahan sesuai takaran.', 'Tumis protein bersama karbohidrat hingga tercampur.', 'Tambahkan sayuran dan masak sebentar sebelum disajikan.']],
            ],
            'Dinner' => [
                ['title' => 'Light dinner bowl', 'steps' => ['Kukus sayuran hingga sedikit lunak.', 'Masak protein dengan api sedang sampai matang.', 'Sajikan dengan karbohidrat dalam porsi ringan.']],
                ['title' => 'Simple nourishing dinner', 'steps' => ['Masak karbohidrat sesuai takaran.', 'Panggang atau tumis protein tanpa terlalu banyak minyak.', 'Tambahkan sayuran dan bumbui secukupnya.']],
                ['title' => 'Easy one-pan dinner', 'steps' => ['Panaskan wajan dan masak protein terlebih dahulu.', 'Masukkan karbohidrat, lalu aduk sampai hangat.', 'Tambahkan sayuran terakhir dan sajikan.']],
            ],
        ];
        $recipes['Breakfast'] = array_merge($recipes['Breakfast'], [
            ['title' => 'Protein veggie scramble', 'steps' => ['Masak protein sampai matang.', 'Tambahkan sayuran dan aduk sebentar.', 'Sajikan dengan karbohidrat sesuai porsi.']],
            ['title' => 'Creamy oat power bowl', 'steps' => ['Masak oat dengan air sampai lembut.', 'Tambahkan protein yang sudah matang.', 'Sajikan bersama sayuran segar.']],
            ['title' => 'Quick morning grain bowl', 'steps' => ['Hangatkan karbohidrat yang sudah dimasak.', 'Tambahkan protein dan sayuran yang telah dipotong.', 'Bumbui ringan lalu sajikan.']],
            ['title' => 'Fresh breakfast veggie plate', 'steps' => ['Kukus sayuran hingga matang.', 'Masak protein menggunakan sedikit minyak.', 'Sajikan dengan karbohidrat dalam satu piring.']],
        ]);
        $recipes['Lunch'] = array_merge($recipes['Lunch'], [
            ['title' => 'Protein veggie power bowl', 'steps' => ['Masak karbohidrat sesuai takaran.', 'Panggang protein hingga matang.', 'Tambahkan sayuran dan sajikan dalam mangkuk.']],
            ['title' => 'Colorful grain stir-fry', 'steps' => ['Tumis protein sampai harum.', 'Masukkan karbohidrat dan aduk rata.', 'Tambahkan sayuran terakhir agar tetap segar.']],
            ['title' => 'Simple wholesome lunch', 'steps' => ['Kukus sayuran dan sisihkan.', 'Masak protein dengan bumbu sederhana.', 'Susun semua bahan menjadi satu porsi makan.']],
            ['title' => 'Everyday balanced bowl', 'steps' => ['Siapkan karbohidrat yang telah matang.', 'Tumis protein dan sayuran secara terpisah.', 'Gabungkan lalu sajikan hangat.']],
        ]);
        $recipes['Dinner'] = array_merge($recipes['Dinner'], [
            ['title' => 'Lean protein veggie bowl', 'steps' => ['Masak protein hingga matang tanpa terlalu banyak minyak.', 'Kukus sayuran agar tetap ringan.', 'Sajikan dengan karbohidrat secukupnya.']],
            ['title' => 'Comforting warm dinner', 'steps' => ['Hangatkan karbohidrat sesuai porsi.', 'Masak protein dengan api sedang.', 'Tambahkan sayuran dan bumbui secukupnya.']],
            ['title' => 'Low-fuss dinner plate', 'steps' => ['Potong sayuran dan protein seperlunya.', 'Masak protein lalu tambahkan sayuran.', 'Sajikan dengan karbohidrat yang sudah matang.']],
            ['title' => 'Garden protein supper', 'steps' => ['Kukus sayuran hingga renyah lembut.', 'Panggang protein hingga matang merata.', 'Sajikan bersama karbohidrat dalam porsi seimbang.']],
        ]);
        if ($isDiet) {
            $recipes = [
                'Breakfast' => [
                    ['title' => 'Low-calorie veggie omelette', 'steps' => ['Gunakan sedikit minyak atau wajan anti lengket.', 'Masak protein bersama sayuran sampai matang.', 'Sajikan dengan karbohidrat dalam porsi kecil.']],
                    ['title' => 'Light protein breakfast bowl', 'steps' => ['Gunakan karbohidrat sesuai takaran diet.', 'Masak protein dengan cara kukus atau panggang.', 'Tambahkan sayuran tanpa saus tinggi kalori.']],
                    ['title' => 'Fresh lean breakfast plate', 'steps' => ['Kukus sayuran hingga matang.', 'Panggang protein tanpa lemak berlebih.', 'Sajikan dengan karbohidrat secukupnya.']],
                ],
                'Lunch' => [
                    ['title' => 'Lean protein salad bowl', 'steps' => ['Cuci dan potong sayuran.', 'Panggang protein tanpa banyak minyak.', 'Sajikan dengan sedikit karbohidrat dan bumbu ringan.']],
                    ['title' => 'Light balanced lunch', 'steps' => ['Kukus sayuran untuk menjaga teksturnya.', 'Masak protein dengan api sedang.', 'Batasi karbohidrat sesuai takaran.']],
                    ['title' => 'Low-calorie power plate', 'steps' => ['Siapkan karbohidrat dalam porsi kecil.', 'Panggang atau kukus protein.', 'Isi setengah piring dengan sayuran.']],
                ],
                'Dinner' => [
                    ['title' => 'Light protein dinner bowl', 'steps' => ['Kukus sayuran sampai renyah lembut.', 'Panggang protein tanpa minyak berlebih.', 'Gunakan karbohidrat sedikit.']],
                    ['title' => 'Low-carb veggie dinner', 'steps' => ['Tumis sayuran dengan sedikit air atau minyak.', 'Masak protein sampai matang sempurna.', 'Gunakan karbohidrat sesuai porsi diet.']],
                    ['title' => 'Lean one-pan supper', 'steps' => ['Gunakan wajan anti lengket.', 'Masak protein dan sayuran tanpa saus manis.', 'Tambahkan karbohidrat secukupnya.']],
                ],
            ];
        }
        $options = collect($recipes[$type] ?? $recipes['Dinner'])
            ->reject(fn ($recipe) => in_array($recipe['title'], $usedTitles, true))
            ->values()
            ->all();
        if (empty($options)) $options = $recipes[$type] ?? $recipes['Dinner'];
        $recipe = $options[array_rand($options)];

        return [
            'title' => $recipe['title'],
            'ingredients' => $ingredients,
            'servings' => 1,
            'steps' => $recipe['steps'],
        ];
    }

    private function portionFor(string $category, string $productName): string
    {
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