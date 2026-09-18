<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->text('ingredients')->nullable()->after('description');
            $table->text('instructions')->nullable()->after('ingredients');
            $table->unsignedInteger('calories')->nullable()->after('price');
            $table->string('image_path')->nullable()->after('calories');
        });
    }

    public function down(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->dropColumn(['ingredients', 'instructions', 'calories', 'image_path']);
        });
    }
};
