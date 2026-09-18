<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('stores', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Nama Swalayan (misal: Superindo, Indomaret)
        $table->string('address');
        $table->decimal('latitude', 10, 8); // Untuk perhitungan jarak
        $table->decimal('longitude', 11, 8);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
