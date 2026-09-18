<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminStoreController extends Controller
{
    public function index()
    {
        return view('admin.stores.index', ['stores' => Store::withCount('products')->latest()->get()]);
    }

    public function create()
    {
        return view('admin.stores.form', ['store' => new Store()]);
    }

    public function store(Request $request)
    {
        $data = $this->storeData($request);
        $data['image_path'] = $request->file('image')?->store('stores', 'public');
        Store::create($data);

        return redirect()->route('admin.stores.index')->with('success', 'Toko berhasil ditambahkan.');
    }

    public function edit(Store $store)
    {
        return view('admin.stores.form', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $data = $this->storeData($request);
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($store->image_path);
            $data['image_path'] = $request->file('image')->store('stores', 'public');
        }
        $store->update($data);

        return redirect()->route('admin.stores.index')->with('success', 'Toko berhasil diperbarui.');
    }

    public function destroy(Store $store)
    {
        Storage::disk('public')->delete($store->image_path);
        $store->delete();

        return back()->with('success', 'Toko berhasil dihapus.');
    }

    public function products(Store $store)
    {
        return view('admin.stores.products', ['store' => $store, 'products' => $store->products()->latest()->get()]);
    }

    public function createProduct(Store $store)
    {
        return view('admin.stores.product-form', ['store' => $store, 'product' => new StoreProduct()]);
    }

    public function storeProduct(Request $request, Store $store)
    {
        $data = $this->productData($request);
        $data['store_id'] = $store->id;
        $data['image_path'] = $request->file('image')?->store('products', 'public');
        StoreProduct::create($data);

        return redirect()->route('admin.stores.products', $store)->with('success', 'Produk berhasil ditambahkan.');
    }

    public function editProduct(Store $store, StoreProduct $product)
    {
        abort_unless($product->store_id === $store->id, 404);
        return view('admin.stores.product-form', compact('store', 'product'));
    }

    public function updateProduct(Request $request, Store $store, StoreProduct $product)
    {
        abort_unless($product->store_id === $store->id, 404);
        $data = $this->productData($request);
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        $product->update($data);

        return redirect()->route('admin.stores.products', $store)->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroyProduct(Store $store, StoreProduct $product)
    {
        abort_unless($product->store_id === $store->id, 404);
        Storage::disk('public')->delete($product->image_path);
        $product->delete();

        return back()->with('success', 'Produk berhasil dihapus.');
    }

    private function storeData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    private function productData(Request $request): array
    {
        return $request->validate([
            'product_name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'in:Karbohidrat,Protein,Sayuran'],
            'is_available' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]) + ['is_available' => $request->boolean('is_available')];
    }
}
