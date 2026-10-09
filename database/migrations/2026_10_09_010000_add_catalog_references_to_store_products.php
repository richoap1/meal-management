<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->string('reference_sku')->nullable();
            $table->string('catalog_category')->nullable();
            $table->string('reference_source')->nullable();
            $table->unique(['store_id', 'reference_sku']);
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'reference_sku']);
            $table->dropColumn(['reference_sku', 'catalog_category', 'reference_source']);
        });
    }
};
