<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MealPrepController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminStoreController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\AdminMealController;
use App\Http\Controllers\MealPlanController;

Route::get('/', function () {
    return view('welcome');
});

// Rute Autentikasi (Hanya bisa diakses jika belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Rute Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Rute User Biasa (Harus login)
Route::middleware(['auth'])->group(function () {
    Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
    // Planner & Meals
    Route::get('/planner', [MealPrepController::class, 'index'])->name('planner.index');
    Route::post('/planner', [MealPrepController::class, 'store'])->name('planner.store');
    
    // Tambahkan baris ini untuk endpoint FullCalendar
    Route::get('/planner/events', [MealPrepController::class, 'getEvents'])->name('planner.events');
    Route::get('/calendar', [MealPlanController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [MealPlanController::class, 'events'])->name('calendar.events');
    Route::post('/calendar/entries', [MealPlanController::class, 'store'])->name('calendar.entries.store');
    Route::patch('/calendar/entries/{entry}', [MealPlanController::class, 'update'])->name('calendar.entries.update');
    Route::delete('/calendar/entries/{entry}', [MealPlanController::class, 'destroy'])->name('calendar.entries.destroy');
    
    // Stores & Products
    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/{store}/products', [StoreController::class, 'show'])->name('stores.products');
    
    // Membership
    Route::get('/membership', [MembershipController::class, 'index'])->name('membership.index');
    Route::post('/membership/upgrade', [MembershipController::class, 'submit'])->name('membership.upgrade');
});

Route::middleware(['auth', 'is_admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::resource('recipes', AdminMealController::class)->except(['show'])->names('admin.recipes');
    Route::resource('stores', AdminStoreController::class)->except(['show'])->names('admin.stores');
    Route::get('/stores/{store}/products', [AdminStoreController::class, 'products'])->name('admin.stores.products');
    Route::get('/stores/{store}/products/create', [AdminStoreController::class, 'createProduct'])->name('admin.stores.products.create');
    Route::post('/stores/{store}/products', [AdminStoreController::class, 'storeProduct'])->name('admin.stores.products.store');
    Route::get('/stores/{store}/products/{product}/edit', [AdminStoreController::class, 'editProduct'])->name('admin.stores.products.edit');
    Route::put('/stores/{store}/products/{product}', [AdminStoreController::class, 'updateProduct'])->name('admin.stores.products.update');
    Route::delete('/stores/{store}/products/{product}', [AdminStoreController::class, 'destroyProduct'])->name('admin.stores.products.destroy');
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');
    Route::put('/payments/{payment}', [AdminPaymentController::class, 'update'])->name('admin.payments.update');
    Route::get('/payments/{payment}/proof', [AdminPaymentController::class, 'proof'])->name('admin.payments.proof');
});