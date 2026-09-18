<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->date('meal_date');
            $table->string('meal_type');
            $table->string('title');
            $table->json('recipe')->nullable();
            $table->json('ingredients')->nullable();
            $table->unsignedInteger('calories')->nullable();
            $table->unsignedInteger('carbs')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'meal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_entries');
    }
};
