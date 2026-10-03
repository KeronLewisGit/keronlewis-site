<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ConnectionController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/resume', [ResumeController::class, 'show'])->name('resume');
Route::get('/resume.pdf', [ResumeController::class, 'pdf'])->name('resume.pdf');
Route::get('/resume.json', [ResumeController::class, 'json'])->name('resume.json');
Route::get('/keron-lewis.vcf', [ResumeController::class, 'vcard'])->name('vcard');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contact.store');

Route::view('/privacy', 'privacy')->name('privacy');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'show'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/', [AnalyticsController::class, 'index'])->name('analytics');
        Route::post('analytics/refresh', [AnalyticsController::class, 'refresh'])->name('analytics.refresh');

        Route::get('connection', [ConnectionController::class, 'edit'])->name('connection');
        Route::put('connection', [ConnectionController::class, 'update'])->name('connection.update');
        Route::delete('connection', [ConnectionController::class, 'destroy'])->name('connection.destroy');
    });
});
