<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\WarnaController;
use App\Http\Controllers\UkuranController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\DasborController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/katalog', [ProdukController::class, 'katalog'])->name('katalog');

Route::get('/galeri', function () {
    return Inertia::render('Galeri');
})->name('galeri');

Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang');
Route::post('/keranjang/checkout', [KeranjangController::class, 'checkout'])->name('keranjang.checkout');

Route::get('/katalog/produk/{produk}', [ProdukController::class, 'show'])->name('produk.detail');

Route::middleware('auth')->group(function () {
    Route::get('/profil/riwayat-pesanan', [ProfilController::class, 'riwayatPesanan'])->name('profil.riwayat-pesanan');
    Route::get('/profil/edit', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil/edit', [ProfilController::class, 'update'])->name('profil.update');
    Route::get('/profil/ubah-kata-sandi', [ProfilController::class, 'editPassword'])->name('profil.ubah-kata-sandi');
    Route::put('/profil/ubah-kata-sandi', [ProfilController::class, 'updatePassword'])->name('profil.update-password');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DasborController::class, 'index'])->name('dashboard');
    Route::get('/dasbor', [DasborController::class, 'index'])->name('dasbor');
});

Route::get('/produksi/stok-menipis', [ProdukController::class, 'stokMenipis'])->name('produksi.stok-menipis');

// Pembayaran
Route::get('/produksi/konfirmasi-pembayaran', [PembayaranController::class, 'index'])->name('produksi.konfirmasi-pembayaran');
Route::post('/produksi/pembayaran', [PembayaranController::class, 'store'])->name('produksi.pembayaran.store');
Route::put('/produksi/pembayaran/{pembayaran}', [PembayaranController::class, 'update'])->name('produksi.pembayaran.update');
Route::post('/produksi/pembayaran/{pembayaran}/bukti', [PembayaranController::class, 'uploadBukti'])->name('produksi.pembayaran.upload-bukti');
Route::patch('/produksi/pembayaran/{pembayaran}/status', [PembayaranController::class, 'updateStatus'])->name('produksi.pembayaran.update-status');
Route::delete('/produksi/pembayaran/{pembayaran}', [PembayaranController::class, 'destroy'])->name('produksi.pembayaran.destroy');

// Pesanan
Route::get('/pesanan/daftar-pesanan', [PesananController::class, 'index'])->name('pesanan.daftar-pesanan');
Route::get('/produksi/pesanan-baru', [PesananController::class, 'pesananBaru'])->name('produksi.pesanan-baru');
Route::get('/produksi/antrean-produksi', [PesananController::class, 'antreanProduksi'])->name('produksi.antrean-produksi');
Route::post('/pesanan/pesanan', [PesananController::class, 'store'])->name('pesanan.store');
Route::put('/pesanan/pesanan/{pesanan}', [PesananController::class, 'update'])->name('pesanan.update');
Route::patch('/pesanan/pesanan/{pesanan}/status', [PesananController::class, 'updateStatus'])->name('pesanan.update-status');
Route::patch('/pesanan/pesanan/{pesanan}/prioritas', [PesananController::class, 'updatePrioritas'])->name('pesanan.update-prioritas');
Route::delete('/pesanan/pesanan/{pesanan}', [PesananController::class, 'destroy'])->name('pesanan.destroy');

// Master - Kategori
Route::get('/master/kategori', [KategoriController::class, 'index'])->name('master.kategori');
Route::post('/master/kategori', [KategoriController::class, 'store'])->name('master.kategori.store');
Route::put('/master/kategori/{kategori}', [KategoriController::class, 'update'])->name('master.kategori.update');
Route::delete('/master/kategori/{kategori}', [KategoriController::class, 'destroy'])->name('master.kategori.destroy');

// Master - Warna
Route::get('/master/warna', [WarnaController::class, 'index'])->name('master.warna');
Route::post('/master/warna', [WarnaController::class, 'store'])->name('master.warna.store');
Route::put('/master/warna/{warna}', [WarnaController::class, 'update'])->name('master.warna.update');
Route::delete('/master/warna/{warna}', [WarnaController::class, 'destroy'])->name('master.warna.destroy');

// Master - Ukuran
Route::get('/master/ukuran', [UkuranController::class, 'index'])->name('master.ukuran');
Route::post('/master/ukuran', [UkuranController::class, 'store'])->name('master.ukuran.store');
Route::put('/master/ukuran/{ukuran}', [UkuranController::class, 'update'])->name('master.ukuran.update');
Route::delete('/master/ukuran/{ukuran}', [UkuranController::class, 'destroy'])->name('master.ukuran.destroy');

// Master - Produk
Route::get('/master/produk', [ProdukController::class, 'index'])->name('master.produk');
Route::post('/master/produk', [ProdukController::class, 'store'])->name('master.produk.store');
Route::put('/master/produk/{produk}', [ProdukController::class, 'update'])->name('master.produk.update');
Route::delete('/master/produk/{produk}', [ProdukController::class, 'destroy'])->name('master.produk.destroy');

Route::get('welcome', function () {
    return Inertia::render('Welcome');
})->middleware(['auth', 'verified'])->name('welcome');

require __DIR__.'/settings.php';
