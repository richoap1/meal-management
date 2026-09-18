<?php

namespace App\Http\Controllers;

use App\Models\Meal;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = collect($this->recipes());
        $databaseRecipes = Meal::where('is_available', true)->get()->map(fn (Meal $meal) => [
            'title' => $meal->name,
            'type' => ucfirst($meal->type),
            'mode' => 'Non-diet',
            'calories' => $meal->calories ?? 0,
            'image' => $meal->image_path ? asset('storage/' . $meal->image_path) : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=640&q=80',
            'ingredients' => preg_split('/\r\n|\r|\n/', $meal->ingredients ?: ''),
            'steps' => preg_split('/\r\n|\r|\n/', $meal->instructions ?: ''),
        ]);

        return view('recipes.index', ['recipes' => $recipes->merge($databaseRecipes)->all()]);
    }

    private function recipes(): array
    {
        $standardSteps = ['Siapkan dan cuci semua bahan.', 'Masak karbohidrat dan protein sampai matang.', 'Tambahkan sayuran, bumbui secukupnya, lalu sajikan.'];
        $lightSteps = ['Siapkan bahan sesuai takaran satu porsi.', 'Gunakan metode kukus atau panggang dengan sedikit minyak.', 'Sajikan hangat dan hindari saus tinggi gula.'];

        return [
            ['title' => 'Savory protein breakfast bowl', 'type' => 'Breakfast', 'mode' => 'Non-diet', 'calories' => 550, 'image' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['75 g beras atau kentang', '100 g protein pilihan', '100 g sayuran', 'Bawang putih dan lada secukupnya'], 'steps' => $standardSteps],
            ['title' => 'Simple breakfast stir-fry', 'type' => 'Breakfast', 'mode' => 'Non-diet', 'calories' => 520, 'image' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['100 g pasta atau jagung', '100 g telur atau tahu', '100 g sayuran', '1 sdt minyak'], 'steps' => $standardSteps],
            ['title' => 'Warm balanced breakfast plate', 'type' => 'Breakfast', 'mode' => 'Non-diet', 'calories' => 560, 'image' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['100 g karbohidrat', '100 g ayam atau ikan', '100 g sayuran', 'Bumbu pilihan'], 'steps' => $standardSteps],
            ['title' => 'Healthy lunch rice bowl', 'type' => 'Lunch', 'mode' => 'Non-diet', 'calories' => 600, 'image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['100 g nasi', '100 g ayam atau ikan', '100 g sayuran', 'Bawang putih, lada, dan kecap secukupnya'], 'steps' => $standardSteps],
            ['title' => 'Colorful lunch plate', 'type' => 'Lunch', 'mode' => 'Non-diet', 'calories' => 580, 'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['100 g kentang', '100 g protein pilihan', '150 g sayuran warna-warni', 'Jeruk nipis secukupnya'], 'steps' => $standardSteps],
            ['title' => 'Quick balanced lunch', 'type' => 'Lunch', 'mode' => 'Non-diet', 'calories' => 620, 'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['100 g pasta', '100 g protein pilihan', '100 g sayuran', '1 sdt minyak'], 'steps' => $standardSteps],
            ['title' => 'Light dinner bowl', 'type' => 'Dinner', 'mode' => 'Non-diet', 'calories' => 500, 'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['75 g karbohidrat', '100 g protein', '150 g sayuran', 'Bumbu ringan'], 'steps' => $standardSteps],
            ['title' => 'Low-calorie veggie omelette', 'type' => 'Breakfast', 'mode' => 'Diet', 'calories' => 385, 'image' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['2 butir telur', '150 g sayuran', '50 g ubi atau oat', 'Lada dan herbs'], 'steps' => $lightSteps],
            ['title' => 'Light protein breakfast bowl', 'type' => 'Breakfast', 'mode' => 'Diet', 'calories' => 360, 'image' => 'https://images.unsplash.com/photo-1490474418585-ba9_bad?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['50 g oat', '100 g tahu atau telur', '100 g sayuran', 'Bumbu tanpa gula'], 'steps' => $lightSteps],
            ['title' => 'Lean protein salad bowl', 'type' => 'Lunch', 'mode' => 'Diet', 'calories' => 420, 'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['100 g ayam atau ikan panggang', '200 g sayuran segar', '50 g jagung', 'Perasan jeruk nipis'], 'steps' => $lightSteps],
            ['title' => 'Low-calorie power plate', 'type' => 'Lunch', 'mode' => 'Diet', 'calories' => 440, 'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['75 g ubi', '100 g protein tanpa lemak', '200 g sayuran', 'Lada dan herbs'], 'steps' => $lightSteps],
            ['title' => 'Light protein dinner bowl', 'type' => 'Dinner', 'mode' => 'Diet', 'calories' => 390, 'image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['50 g beras atau quinoa', '100 g ikan atau tahu', '200 g sayuran', 'Bumbu rendah garam'], 'steps' => $lightSteps],
            ['title' => 'Low-carb veggie dinner', 'type' => 'Dinner', 'mode' => 'Diet', 'calories' => 350, 'image' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['100 g protein', '250 g sayuran', '50 g karbohidrat', 'Bawang putih dan lada'], 'steps' => $lightSteps],
            ['title' => 'Lean one-pan supper', 'type' => 'Dinner', 'mode' => 'Diet', 'calories' => 410, 'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=640&q=80', 'ingredients' => ['100 g ayam atau tahu', '200 g sayuran', '50 g ubi', '1 sdt minyak'], 'steps' => $lightSteps],
        ];
    }
}
