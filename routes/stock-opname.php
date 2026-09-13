<?php

use App\Http\Controllers\StockOpname\ApprovalController;
use App\Http\Controllers\StockOpname\ImportController;
use App\Http\Controllers\StockOpname\ItemController;
use App\Http\Controllers\StockOpname\ReportController;
use App\Http\Controllers\StockOpname\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('opname')->group(function (): void {
    // Session routes
    Route::get('/create', [SessionController::class, 'create'])->name('opname.create');
    Route::get('/sessions', [SessionController::class, 'index'])->name('opname.sessions.index');
    Route::get('/audit-log', [SessionController::class, 'audit'])->name('opname.audit');
    Route::post('/sessions', [SessionController::class, 'store'])->name('opname.sessions.store');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('opname.sessions.show');
    Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('opname.sessions.update');
    Route::post('/sessions/{session}/cancel', [SessionController::class, 'cancel'])->name('opname.sessions.cancel');
    Route::post('/sessions/{session}/close', [SessionController::class, 'close'])->name('opname.sessions.close');

    // Item routes
    Route::post('/items/{item}/reconcile', [ItemController::class, 'reconcile'])->name('opname.items.reconcile');
    Route::post('/items/{item}/verify', [ApprovalController::class, 'verifyItem'])->name('opname.items.verify');

    // Approval routes
    Route::post('/sessions/{session}/review', [ApprovalController::class, 'review'])->name('opname.sessions.review');
    Route::post('/sessions/{session}/approve', [ApprovalController::class, 'approve'])->name('opname.sessions.approve');

    // Import/Export routes
    Route::post('/import', [ImportController::class, 'import'])->name('opname.import');
    Route::get('/sessions/{session}/export', [ImportController::class, 'export'])->name('opname.sessions.export');

    // Report routes
    Route::get('/sessions/{session}/report', [ReportController::class, 'report'])->name('opname.sessions.report');
});
