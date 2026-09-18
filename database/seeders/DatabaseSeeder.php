<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

    User::create([
            'name' => 'Administrator',
            'email' => 'admin@planner.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Dummy User Biasa
        User::create([
            'name' => 'Regular User',
            'email' => 'user@planner.com',
            'password' => Hash::make('user123'),
            'role' => 'user',
        ]);
        $stores = [
            [
                'name' => 'Superindo Pahlawan (Pusat)',
                'address' => 'Jl. Pahlawan No. 43, Alun-alun Contong, Surabaya',
                'latitude' => -7.265556,
                'longitude' => 112.738333,
            ],
            [
                'name' => 'Hokky Supermarket Graha Family (Barat)',
                'address' => 'Kawasan Graha Family, Pradahkalikendal, Dukuhpakis, Surabaya',
                'latitude' => -7.288231,
                'longitude' => 112.677519,
            ],
            [
                'name' => "Transmart rungkut (Timur)",
                'address' => 'Jl. Raya Kalirungkut No.23-25, Kali Rungkut, Surabaya',
                'latitude' => -7.319545,
                'longitude' => 112.768875,
            ],
            [
                'name' => 'Papaya Fresh Market Margorejo (Selatan)',
                'address' => 'Jl. Raya Margerejo Indah No. 60-68, Margorejo, Surabaya',
                'latitude' => -7.315278,
                'longitude' => 112.738611,
            ]
        ];

        foreach ($stores as $storeData) {
            $store = Store::create($storeData);
            $this->seedProducts($store->id);
        }
    }

    private function seedProducts($storeId)
    {
        $priceMod = rand(-2000,3000);

        $products = [
            ['product_name' => 'Telur 1kg', 'price' => 25000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Dada Ayam 500g', 'price' => 30000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Beras 5kg', 'price' => 60000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Kentang 1kg', 'price' => 15000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Wortel 1kg', 'price' => 12000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
            ['product_name' => 'Brokoli 500g', 'price' => 20000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],

            ['product_name' => 'Ikan Salmon 500g', 'price' => 80000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Pasta 500g', 'price' => 25000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Bayam 1kg', 'price' => 10000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
            ['product_name' => 'Daging Sapi 500g', 'price' => 70000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Jagung 1kg', 'price' => 15000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Kacang Panjang 1kg', 'price' => 12000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],

            ['product_name' => 'Tahu 500g', 'price' => 10000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Roti Gandum 1 pack', 'price' => 20000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Selada 1 ikat', 'price' => 8000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
            ['product_name' => 'Daging Ayam 1kg', 'price' => 40000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Ubi Jalar 1kg', 'price' => 15000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Kangkung 1 ikat', 'price' => 7000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
            ['product_name' => 'Ikan Tuna 500g', 'price' => 60000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Jagung Manis 1kg', 'price' => 20000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Tomat 1kg', 'price' => 10000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],

            ['product_name' => 'Daging Kambing 500g', 'price' => 75000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Bihun 500g', 'price' => 20000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Paprika 1kg', 'price' => 25000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
            ['product_name' => 'Ikan Lele 500g', 'price' => 30000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Mie Instan 1 pack', 'price' => 3500, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Buncis 1kg', 'price' => 15000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
            ['product_name' => 'Daging Bebek 500g', 'price' => 65000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Kentang Manis 1kg', 'price' => 18000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Terong 1kg', 'price' => 12000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
            ['product_name' => 'Oat 500g', 'price' => 28000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Quinoa 500g', 'price' => 45000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Ubi Ungu 1kg', 'price' => 17000 + $priceMod, 'category' => 'Karbohidrat', 'is_available' => true],
            ['product_name' => 'Udang 500g', 'price' => 50000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Tempe 500g', 'price' => 12000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Kacang Merah 500g', 'price' => 18000 + $priceMod, 'category' => 'Protein', 'is_available' => true],
            ['product_name' => 'Mentimun 1kg', 'price' => 10000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
            ['product_name' => 'Jamur Tiram 250g', 'price' => 14000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
            ['product_name' => 'Kol 1kg', 'price' => 11000 + $priceMod, 'category' => 'Sayuran', 'is_available' => true],
        ];

        foreach ($products as $product) {
            StoreProduct::create([
                'store_id' => $storeId,
                'product_name' => $product['product_name'],
                'price' => $product['price'],
                'category' => $product['category'],
                'is_available' => $product['is_available'],
            ]);
        }
    }
}
