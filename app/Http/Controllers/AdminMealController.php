<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminMealController extends Controller
{
    public function index()
    {
        return view('admin.recipes.index', ['meals' => Meal::latest()->get()]);
    }

    public function create()
    {
        return view('admin.recipes.form', ['meal' => new Meal()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image_path'] = $request->file('image')?->store('recipes', 'public');
        Meal::create($data);

        return redirect()->route('admin.recipes.index')->with('success', 'Recipe berhasil ditambahkan.');
    }

    public function edit(Meal $recipe)
    {
        return view('admin.recipes.form', ['meal' => $recipe]);
    }

    public function update(Request $request, Meal $recipe)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($recipe->image_path);
            $data['image_path'] = $request->file('image')->store('recipes', 'public');
        }
        $recipe->update($data);

        return redirect()->route('admin.recipes.index')->with('success', 'Recipe berhasil diperbarui.');
    }

    public function destroy(Meal $recipe)
    {
        Storage::disk('public')->delete($recipe->image_path);
        $recipe->delete();

        return back()->with('success', 'Recipe berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'ingredients' => ['required', 'string', 'max:5000'],
            'instructions' => ['required', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'calories' => ['required', 'integer', 'min:0', 'max:10000'],
            'type' => ['required', 'in:breakfast,lunch,dinner'],
            'is_available' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $data['is_available'] = $request->boolean('is_available');
        return $data;
    }
}
