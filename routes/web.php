<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SppgController;
use App\Http\Controllers\StockItemController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
Route::patch('/purchase-orders/{id}/status', [PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.status.update');
Route::patch('/purchase-orders/{id}/suppliers', [PurchaseOrderController::class, 'updateSuppliers'])->name('purchase-orders.suppliers.update');
Route::patch('/purchase-orders/{id}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
Route::delete('/purchase-orders/{id}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
Route::get('/purchase-orders/{id}/preview', [PurchaseOrderController::class, 'preview'])->name('purchase-orders.preview');
Route::get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
Route::get('/purchase-orders/{id}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');

Route::get('/surat-jalan', [DeliveryNoteController::class, 'index'])->name('surat-jalan.index');
Route::patch('/surat-jalan/{id}', [DeliveryNoteController::class, 'update'])->name('surat-jalan.update');
Route::patch('/surat-jalan/{id}/preview', [DeliveryNoteController::class, 'previewFromForm'])->name('surat-jalan.preview.form');
Route::get('/surat-jalan/{id}/preview', [DeliveryNoteController::class, 'preview'])->name('surat-jalan.preview');
Route::get('/surat-jalan/{id}', [DeliveryNoteController::class, 'show'])->name('surat-jalan.show');

Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('/invoices/{id}/create', [InvoiceController::class, 'create'])->name('invoices.create');
Route::post('/invoices/{id}', [InvoiceController::class, 'store'])->name('invoices.store');
Route::post('/invoices/{id}/add-item', [InvoiceController::class, 'addItem'])->name('invoices.add-item');
Route::patch('/invoices/{id}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status.update');
Route::get('/invoices/{id}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');

Route::get('/master-stok', [StockItemController::class, 'index'])->name('master-stok.index');
Route::get('/master-stok/create', [StockItemController::class, 'create'])->name('master-stok.create');
Route::post('/master-stok', [StockItemController::class, 'store'])->name('master-stok.store');
Route::get('/master-stok/{id}', [StockItemController::class, 'show'])->name('master-stok.show');
Route::get('/master-stok/{id}/edit', [StockItemController::class, 'edit'])->name('master-stok.edit');
Route::patch('/master-stok/{id}', [StockItemController::class, 'update'])->name('master-stok.update');
Route::delete('/master-stok/{id}', [StockItemController::class, 'destroy'])->name('master-stok.destroy');

Route::get('/master-sppg', [SppgController::class, 'index'])->name('master-sppg.index');
Route::get('/master-sppg/create', [SppgController::class, 'create'])->name('master-sppg.create');
Route::post('/master-sppg', [SppgController::class, 'store'])->name('master-sppg.store');
Route::get('/master-sppg/{id}/edit', [SppgController::class, 'edit'])->name('master-sppg.edit');
Route::patch('/master-sppg/{id}', [SppgController::class, 'update'])->name('master-sppg.update');
Route::delete('/master-sppg/{id}', [SppgController::class, 'destroy'])->name('master-sppg.destroy');

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
