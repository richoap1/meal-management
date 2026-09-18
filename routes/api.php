<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\MealPrepController;
use App\Http\Controllers\MembershipController;

Route::middleware('web')->group(function () {
    Route::post('/stores/nearest', [StoreController::class, 'findNearest']);
    Route::post('/meal-prep/generate', [MealPrepController::class, 'generatePlan']);
    Route::get('/membership', [MembershipController::class, 'show']);
    Route::post('/membership', [MembershipController::class, 'update']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

