<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'seller_name',
        'description',
        'ingredients',
        'instructions',
        'price',
        'calories',
        'carbs',
        'image_path',
        'type',
        'sport_segments',
        'is_available',
    ];

    protected $casts = [
        'calories' => 'integer',
        'carbs' => 'integer',
        'price' => 'float',
        'sport_segments' => 'array',
        'is_available' => 'boolean',
    ];
}
