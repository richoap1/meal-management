<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('longitude');
        });

        Schema::table('store_products', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('store_products', fn (Blueprint $table) => $table->dropColumn('image_path'));
        Schema::table('stores', fn (Blueprint $table) => $table->dropColumn('image_path'));
    }
};
