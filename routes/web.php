<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('faq', 'faq')->name('faq');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('tickets', 'tickets.index')->name('tickets.index');
    Route::view('tickets/create', 'tickets.create')->name('tickets.create');
    Route::view('tickets/queue', 'tickets.admin-queue')->name('tickets.queue');
    Route::view('tickets/{ticket}', 'tickets.show')->name('tickets.show');
});

require __DIR__.'/settings.php';
