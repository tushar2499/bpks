<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CustomerCareController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JourneyController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BlinkController;
use App\Http\Controllers\BuyController;
use App\Http\Controllers\CallbackController;
use App\Http\Controllers\MyTicketController;
use App\Http\Controllers\TicketImageController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

// ─── Cache Clear (token-protected) ──────────────────────────────────────────
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('event:clear');
    if (function_exists('opcache_reset')) opcache_reset();
    return response()->json(['status' => 'ok', 'cleared' => ['cache','config','route','view','event','opcache']]);
})->name('cache.clear.public');

// ─── Public Buy Flow ────────────────────────────────────────────────────────
Route::get('/', [BuyController::class, 'index'])->name('buy.index');
Route::post('/buy', [BuyController::class, 'initiate'])->name('buy.initiate')
    ->middleware('throttle:10,1');  // 10 req/min per IP
Route::get('/buy/success', [BuyController::class, 'success'])->name('buy.success');
Route::get('/ticket/download', [TicketImageController::class, 'download'])->name('ticket.download');
Route::get('/ticket/download-pdf', [TicketImageController::class, 'downloadPdf'])->name('ticket.download-pdf');
Route::get('/ticket/download-all-pdf', [TicketImageController::class, 'downloadAllPdf'])->name('ticket.download-all-pdf');
Route::get('/my-ticket', [MyTicketController::class, 'show'])->name('my-ticket.show');
Route::post('/my-ticket', [MyTicketController::class, 'find'])->name('my-ticket.find')
    ->middleware('throttle:3,60');

// ─── Blink Notify (Blink POSTs here after successful charge) ─────────────────
Route::post('/api/blink-notify', [CallbackController::class, 'blinkNotify'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('blink.notify');

// ─── Blink (Banglalink OTP) Flow ─────────────────────────────────────────────
Route::get('/blink/otp/{txnRef}',     [BlinkController::class, 'showOtpPage'])->name('blink.otp');
Route::post('/blink/otp/{txnRef}',    [BlinkController::class, 'submitOtp'])->name('blink.otp.submit');
Route::get('/blink/waiting/{txnRef}', [BlinkController::class, 'showWaitingPage'])->name('blink.waiting');
Route::get('/blink/status/{txnRef}',  [BlinkController::class, 'pollStatus'])->name('blink.status');
Route::post('/blink/resend/{txnRef}', [BlinkController::class, 'resendOtp'])->name('blink.resend');

// ─── SMS Delivery Notify (Robi calls this with delivery status) ──────────────
Route::post('/sms-notify/{smsLogId}', [CallbackController::class, 'smsNotify'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('sms.notify');

// ─── Operator DCB Callbacks (no CSRF) ────────────────────────────────────────
Route::prefix('callback')->name('callback.')->withoutMiddleware([VerifyCsrfToken::class])->group(function () {
    // Robi WAP consent redirect (GET — browser is redirected back by Robi)
    Route::get('robi-consent/{txnRef}', [CallbackController::class, 'robiConsent'])->name('robi-consent');

    // GP DOB consent redirect (GET — browser is redirected back by Telenor, 3 possible statuses)
    Route::get('gp/{txnRef}/{status}', [CallbackController::class, 'gpCallback'])
        ->where('status', 'ok|deny|error')
        ->name('gp-consent');

    // GP recharge-and-buy callback (after POL1000 recharge flow)
    Route::get('gp-recharge/{txnRef}/{status}', [CallbackController::class, 'gpRechargeCallback'])
        ->where('status', 'ok|deny|error')
        ->name('gp-recharge'); 

    // Async POST callbacks from operators
    Route::post('robi',         [CallbackController::class, 'robi'])->name('robi');
    Route::post('grameenphone', [CallbackController::class, 'grameenphone'])->name('grameenphone');
    Route::post('banglalink',   [CallbackController::class, 'banglalink'])->name('banglalink');
    Route::post('blink',        [CallbackController::class, 'blinkNotify'])->name('blink');

});

// ─── Admin ──────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/generate', [TicketController::class, 'generateForm'])->name('tickets.generate');
        Route::post('tickets/generate', [TicketController::class, 'generate'])->name('tickets.generate.post');
        Route::post('tickets/release-stuck', [TicketController::class, 'releaseStuck'])->name('tickets.release-stuck');
        Route::post('tickets/bulk-delete', [TicketController::class, 'bulkDelete'])->name('tickets.bulk-delete');
        Route::post('tickets/{ticket}/sell', [TicketController::class, 'sell'])->name('tickets.sell');
        Route::delete('tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/csv', [ReportController::class, 'exportCsv'])->name('reports.csv');
        Route::get('reports/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
        Route::get('reports/sms', [ReportController::class, 'smsReport'])->name('reports.sms');
        Route::post('reports/sms/{transaction}/retry', [ReportController::class, 'retrySms'])->name('reports.sms.retry');
        Route::get('reports/daily', [ReportController::class, 'dailyReport'])->name('reports.daily');
        Route::get('reports/daily-detail', [ReportController::class, 'dailyDetail'])->name('reports.daily-detail');

        Route::get('journey', [JourneyController::class, 'index'])->name('journey.index');

        Route::get('customer-care', [CustomerCareController::class, 'index'])->name('customer-care.index');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('cache/clear', function () {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            return redirect()->back()->with('success', 'All caches cleared.');
        })->name('cache.clear');
    });
});
