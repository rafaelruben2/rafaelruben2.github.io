<?php

use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

// Semua pengguna yang login boleh melihat daftar (read-only untuk non-admin).
Route::get('/pengguna', [UserManagementController::class, 'index'])->name('users.index');

// Hanya admin yang boleh tambah / ubah / hapus.
Route::middleware('admin')->group(function (): void {
    Route::get('/pengguna/tambah', [UserManagementController::class, 'create'])->name('users.create');
    Route::post('/pengguna', [UserManagementController::class, 'store'])->name('users.store');
    Route::get('/pengguna/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/pengguna/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/pengguna/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
});
