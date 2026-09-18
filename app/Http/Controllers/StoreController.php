<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function findNearest(Request $request)
    {
        // 1. Validasi input koordinat dari perangkat user
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        $userLat = $request->latitude;
        $userLng = $request->longitude;

        // 2. Query ke database dengan Rumus Haversine (Konstanta 6371 untuk Kilometer)
        $stores = Store::select('stores.*')
            ->selectRaw("
                ( 6371 * acos( cos( radians(?) ) *
                  cos( radians( latitude ) ) *
                  cos( radians( longitude ) - radians(?) ) +
                  sin( radians(?) ) *
                  sin( radians( latitude ) ) )
                ) AS distance
            ", [$userLat, $userLng, $userLat])
            ->having('distance', '<', 20) // Batasi pencarian maksimum radius 20 km
            ->orderBy('distance', 'asc')  // Urutkan dari yang terdekat
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $stores
        ]);
    }
}