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
    Schema::create('store_products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('store_id')->constrained()->onDelete('cascade'); // Relasi ke tabel stores
        $table->string('product_name'); // Nama bahan (misal: Telur 1kg, Dada Ayam)
        $table->decimal('price', 10, 2); // Harga di swalayan tersebut
        $table->string('category'); // Sayuran, Daging, Karbohidrat, dll
        $table->boolean('is_available')->default(true);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_products');
    }
};
