<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('product_name');
            $table->string('package')->nullable()->after('brand');
            $table->string('subcategory')->nullable()->after('package');
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropColumn(['brand', 'package', 'subcategory']);
        });
    }
};
