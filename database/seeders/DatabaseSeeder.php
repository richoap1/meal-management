<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use App\Services\PlannerProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::updateOrCreate(['email' => 'admin@planner.com'], [
            'name' => 'Administrator',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        User::updateOrCreate(['email' => 'user@planner.com'], [
            'name' => 'Regular User',
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
                'name' => 'Transmart rungkut (Timur)',
                'address' => 'Jl. Raya Kalirungkut No.23-25, Kali Rungkut, Surabaya',
                'latitude' => -7.319545,
                'longitude' => 112.768875,
            ],
            [
                'name' => 'Papaya Fresh Market Margorejo (Selatan)',
                'address' => 'Jl. Raya Margerejo Indah No. 60-68, Margorejo, Surabaya',
                'latitude' => -7.315278,
                'longitude' => 112.738611,
            ],
        ];

        $catalogPath = database_path('seeders/data/store-products.json');
        if (! is_file($catalogPath)) {
            throw new RuntimeException('The bundled store product catalog could not be found.');
        }

        $catalog = json_decode(file_get_contents($catalogPath), true, flags: JSON_THROW_ON_ERROR);
        $categoryResolver = app(PlannerProductCategory::class);

        foreach ($stores as $storeData) {
            $store = Store::updateOrCreate(['name' => $storeData['name']], $storeData);
            $this->seedProducts($store, $catalog, $categoryResolver);
        }
    }

    private function seedProducts(Store $store, array $catalog, PlannerProductCategory $categoryResolver): void
    {
        $storeName = mb_strtolower($store->name);
        $storeKey = match (true) {
            str_contains($storeName, 'superindo') => 'super_indo',
            str_contains($storeName, 'hokky') => 'hokky',
            str_contains($storeName, 'transmart') => 'transmart',
            str_contains($storeName, 'papaya') => 'papaya',
            default => throw new RuntimeException("No reference catalog pricing strategy is configured for {$store->name}."),
        };
        $priceFactors = ['super_indo' => 1.12, 'hokky' => 1.20, 'transmart' => 1.35, 'papaya' => 1.50];
        $priceOffsets = ['super_indo' => 0, 'hokky' => 100, 'transmart' => 200, 'papaya' => 300];
        $brandPrefixes = ['super_indo' => 'Super Indo', 'hokky' => 'Hokky', 'transmart' => 'Transmart', 'papaya' => 'Papaya'];

        foreach (['super_indo', 'hokky'] as $source) {
            foreach ($catalog[$source] as $referenceProduct) {
                $isNativeStore = $storeKey === $source;
                $product = [
                    'product_name' => $referenceProduct['product_name'],
                    'reference_sku' => $referenceProduct['sku'],
                    'catalog_category' => $referenceProduct['catalog_category'],
                    'reference_source' => $referenceProduct['reference_source'],
                    'brand' => $isNativeStore
                        ? $referenceProduct['brand']
                        : $brandPrefixes[$storeKey].' '.$referenceProduct['brand'],
                    'package' => $referenceProduct['package'],
                    'subcategory' => $referenceProduct['subcategory'],
                    'price' => $isNativeStore
                        ? $referenceProduct['price']
                        : ((int) round(($referenceProduct['price'] * $priceFactors[$storeKey]) / 100) * 100)
                            + $priceOffsets[$storeKey],
                    'category' => $categoryResolver->resolve(
                        $referenceProduct['catalog_category'],
                        $referenceProduct['subcategory'],
                        $referenceProduct['product_name'],
                        'Lainnya',
                    ),
                    'is_available' => true,
                ];

                $store->products()->updateOrCreate(
                    ['reference_sku' => $referenceProduct['sku']],
                    $product,
                );
            }
        }
    }
}
