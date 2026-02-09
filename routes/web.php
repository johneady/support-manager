<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('faq', 'faq')->name('faq');
Route::view('privacy-policy', 'privacy-policy')->name('privacy-policy');
Route::view('terms-of-service', 'terms-of-service')->name('terms-of-service');
Route::get('faq/{faq}', function (\App\Models\Faq $faq) {
    abort_unless($faq->is_published, 404);

    return view('faq-show', ['faq' => $faq]);
})->name('faq.show');

Route::get('invitation/{token}', \App\Livewire\Auth\AcceptInvitation::class)
    ->middleware('throttle:invitation')
    ->name('invitation.accept');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('tickets', 'tickets.index')->name('tickets.index');
    Route::view('tickets/create', 'tickets.create')->name('tickets.create');
    Route::view('tickets/{ticket}', 'tickets.show')->whereNumber('ticket')->name('tickets.show');

    Route::middleware('admin')->group(function () {
        Route::get('health', HealthController::class)->name('health');
        Route::view('tickets/queue', 'tickets.admin-queue')->name('tickets.queue');
        Route::view('tickets/all', 'tickets.all')->name('tickets.all');
        Route::view('admin/users', 'admin.users')->name('admin.users');
        Route::view('admin/categories', 'admin.categories')->name('admin.categories');
        Route::view('admin/faqs', 'admin.faqs')->name('admin.faqs');
        Route::view('admin/faqs/create', 'admin.faqs-create')->name('admin.faqs.create');
        Route::view('admin/faqs/{faq}/edit', 'admin.faqs-edit')->name('admin.faqs.edit');
    });
});

require __DIR__.'/settings.php';
