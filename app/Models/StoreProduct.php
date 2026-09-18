<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreProduct extends Model
{
    protected $fillable = [
        'store_id',
        'product_name',
        'price',
        'category',
        'image_path',
        'is_available',
    ];

    protected $casts = ['price' => 'float', 'is_available' => 'boolean'];
}
