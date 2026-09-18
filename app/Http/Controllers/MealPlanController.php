<?php

namespace App\Http\Controllers;

use App\Models\MealPlanEntry;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MealPlanController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function events(Request $request)
    {
        return response()->json($request->user()->mealPlanEntries()->with('store')->get()->map(fn (MealPlanEntry $entry) => [
            'id' => $entry->id,
            'title' => $entry->title,
            'start' => $entry->meal_date->toDateString(),
            'allDay' => true,
            'extendedProps' => $this->details($entry),
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['nullable', 'exists:stores,id'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.date' => ['required', 'date'],
            'entries.*.type' => ['required', 'string', 'max:30'],
            'entries.*.title' => ['required', 'string', 'max:160'],
            'entries.*.recipe' => ['nullable', 'array'],
            'entries.*.ingredients' => ['nullable', 'array'],
            'entries.*.calories' => ['nullable', 'integer', 'min:0'],
            'entries.*.carbs' => ['nullable', 'integer', 'min:0'],
            'entries.*.image_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $store = $data['store_id'] ? Store::find($data['store_id']) : null;
        foreach ($data['entries'] as $entry) {
            $request->user()->mealPlanEntries()->create([
                'store_id' => $store?->id,
                'meal_date' => Carbon::parse($entry['date'])->toDateString(),
                'meal_type' => $entry['type'],
                'title' => $entry['title'],
                'recipe' => $entry['recipe'] ?? null,
                'ingredients' => $entry['ingredients'] ?? null,
                'calories' => $entry['calories'] ?? null,
                'carbs' => $entry['carbs'] ?? null,
                'image_url' => $entry['image_url'] ?? null,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    public function update(Request $request, MealPlanEntry $entry)
    {
        abort_unless($entry->user_id === $request->user()->id, 404);
        $data = $request->validate(['date' => ['required', 'date']]);
        $entry->update(['meal_date' => Carbon::parse($data['date'])->toDateString()]);
        return response()->json(['status' => 'success']);
    }

    public function destroy(Request $request, MealPlanEntry $entry)
    {
        abort_unless($entry->user_id === $request->user()->id, 404);
        $entry->delete();
        return response()->json(['status' => 'success']);
    }

    private function details(MealPlanEntry $entry): array
    {
        return ['type' => $entry->meal_type, 'calories' => $entry->calories, 'carbs' => $entry->carbs, 'recipe' => $entry->recipe, 'ingredients' => $entry->ingredients, 'image_url' => $entry->image_url, 'store' => $entry->store?->only(['name', 'address', 'latitude', 'longitude'])];
    }
}
