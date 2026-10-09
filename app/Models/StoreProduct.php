<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreProduct extends Model
{
    protected $fillable = [
        'store_id',
        'product_name',
        'reference_sku',
        'catalog_category',
        'reference_source',
        'brand',
        'package',
        'subcategory',
        'price',
        'category',
        'image_path',
        'is_available',
    ];

    protected $casts = ['price' => 'float', 'is_available' => 'boolean'];
}
