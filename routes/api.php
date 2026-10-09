<?php

use App\Http\Controllers\MealPrepController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/stores/nearest', [StoreController::class, 'findNearest']);
    Route::get('/meal-prep/umkm-menus', [MealPrepController::class, 'umkmMenus']);
    Route::post('/meal-prep/generate', [MealPrepController::class, 'generatePlan']);
    Route::get('/membership', [MembershipController::class, 'show']);
    Route::post('/membership', [MembershipController::class, 'update']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
