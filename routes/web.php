<?php

use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

// Guest Routes (khusus yang belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes (khusus admin yang sudah login)
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // TODO 4: Route CRUD buku
    Route::resource('buku', BukuController::class);

    // Route CRUD anggota
    Route::resource('anggota', AnggotaController::class)
        ->parameters(['anggota' => 'anggota']);

    // Route peminjaman dan pengembalian
    Route::resource('peminjaman', PeminjamanController::class);
    Route::put('/peminjaman/{peminjaman}/kembali', [PeminjamanController::class, 'kembali'])
        ->name('peminjaman.kembali');
});