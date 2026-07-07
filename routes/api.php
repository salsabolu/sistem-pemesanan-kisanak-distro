<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\PesananController;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/produk', [ProdukController::class, 'index']);
Route::get('/produk/{produk}', [ProdukController::class, 'show']);
Route::get('/kategori', [KategoriController::class, 'index']);
Route::get('/kategori/{kategori}', [KategoriController::class, 'show']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth User
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Pesanan (User only sees their own, handled in controller)
    Route::get('/pesanan', [PesananController::class, 'index']);
    Route::post('/pesanan', [PesananController::class, 'store']);
    Route::get('/pesanan/{pesanan}', [PesananController::class, 'show']);
    // Update and Delete pesanan can also be done by user (like canceling) or admin, but for now we expose it
    Route::put('/pesanan/{pesanan}', [PesananController::class, 'update']);
    Route::delete('/pesanan/{pesanan}', [PesananController::class, 'destroy']);

    // Desain Khusus User / Sistem Kustomisasi
    Route::get('/desain', [\App\Http\Controllers\Api\DesainController::class, 'index']);
    Route::post('/desain', [\App\Http\Controllers\Api\DesainController::class, 'store']);
    Route::get('/desain/{desain}', [\App\Http\Controllers\Api\DesainController::class, 'show']);
    Route::put('/desain/{desain}', [\App\Http\Controllers\Api\DesainController::class, 'update']);
    Route::delete('/desain/{desain}', [\App\Http\Controllers\Api\DesainController::class, 'destroy']);

    // Admin Only Routes
    Route::middleware('role:admin')->group(function () {
        // Produk CUD
        Route::post('/produk', [ProdukController::class, 'store']);
        Route::put('/produk/{produk}', [ProdukController::class, 'update']);
        Route::delete('/produk/{produk}', [ProdukController::class, 'destroy']);

        // Kategori CUD
        Route::post('/kategori', [KategoriController::class, 'store']);
        Route::put('/kategori/{kategori}', [KategoriController::class, 'update']);
        Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy']);
    });
});
