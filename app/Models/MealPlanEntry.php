<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlanEntry extends Model
{
    protected $fillable = ['user_id', 'store_id', 'meal_date', 'meal_type', 'title', 'recipe', 'ingredients', 'calories', 'carbs', 'image_url'];

    protected $casts = ['meal_date' => 'date', 'recipe' => 'array', 'ingredients' => 'array', 'calories' => 'integer', 'carbs' => 'integer'];

    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
}
