<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\ConnectionController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CaseStudyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/work/{slug}', CaseStudyController::class)->name('work.show');
Route::get('/services/{slug}', ServiceController::class)->name('services.show');

Route::get('/resume', [ResumeController::class, 'show'])->name('resume');
Route::get('/resume.pdf', [ResumeController::class, 'pdf'])->name('resume.pdf');
Route::get('/resume.json', [ResumeController::class, 'json'])->name('resume.json');
Route::get('/keron-lewis.vcf', [ResumeController::class, 'vcard'])->name('vcard');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contact.store');

Route::get('/book', [BookingController::class, 'show'])->name('booking.show');
Route::post('/book', [BookingController::class, 'store'])
    ->middleware('throttle:6,10')
    ->name('booking.store');

// The private page behind a testimonial link; only someone holding the token can reach it.
Route::get('/testimonial/{testimonial:token}', [TestimonialController::class, 'create'])->name('testimonials.create');
Route::post('/testimonial/{testimonial:token}', [TestimonialController::class, 'store'])
    ->middleware('throttle:10,10')
    ->name('testimonials.store');

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

        Route::get('messages', [MessageController::class, 'index'])->name('messages');
        Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
        Route::patch('messages/{message}/unread', [MessageController::class, 'markUnread'])->name('messages.unread');
        Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

        Route::get('testimonials', [AdminTestimonialController::class, 'index'])->name('testimonials');
        Route::post('testimonials', [AdminTestimonialController::class, 'store'])->name('testimonials.store');
        Route::patch('testimonials/{testimonial}/approve', [AdminTestimonialController::class, 'approve'])->name('testimonials.approve');
        Route::patch('testimonials/{testimonial}/unpublish', [AdminTestimonialController::class, 'unpublish'])->name('testimonials.unpublish');
        Route::delete('testimonials/{testimonial}', [AdminTestimonialController::class, 'destroy'])->name('testimonials.destroy');

        Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings');
        Route::put('bookings/settings', [AdminBookingController::class, 'update'])->name('bookings.update');
        Route::patch('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');

        Route::get('connection', [ConnectionController::class, 'edit'])->name('connection');
        Route::put('connection', [ConnectionController::class, 'update'])->name('connection.update');
        Route::delete('connection', [ConnectionController::class, 'destroy'])->name('connection.destroy');
    });
});
