<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\Meal;
// use App\Models\Meal; // Buka komentar ini jika model Meal sudah ada
// use App\Models\Store; // Buka komentar ini jika model Store sudah ada

class AdminController extends Controller
{
    public function index()
    {
        // Mengambil data statistik dasar untuk ditampilkan di dashboard
        $stats = [
            'total_users' => User::count(),
            'total_meals' => Meal::count(),
            'total_stores' => Store::count(),
            'total_products' => StoreProduct::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}