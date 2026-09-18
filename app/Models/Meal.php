<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'ingredients',
        'instructions',
        'price',
        'calories',
        'image_path',
        'type',
        'is_available',
    ];

    protected $casts = ['calories' => 'integer', 'price' => 'float', 'is_available' => 'boolean'];
}