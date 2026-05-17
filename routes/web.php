<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JourneyController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\BuyController;
use App\Http\Controllers\CallbackController;
use App\Http\Controllers\MyTicketController;
use App\Http\Controllers\TicketImageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

// ─── Public Buy Flow ────────────────────────────────────────────────────────
Route::get('/', [BuyController::class, 'index'])->name('buy.index');
Route::post('/buy', [BuyController::class, 'initiate'])->name('buy.initiate')
    ->middleware('throttle:10,1');  // 10 req/min per IP
Route::get('/buy/success', [BuyController::class, 'success'])->name('buy.success');
Route::get('/ticket/download', [TicketImageController::class, 'download'])->name('ticket.download');
Route::get('/my-ticket', [MyTicketController::class, 'show'])->name('my-ticket.show');
Route::post('/my-ticket', [MyTicketController::class, 'find'])->name('my-ticket.find')
    ->middleware('throttle:3,60');

// ─── SMS Delivery Notify (Robi calls this with delivery status) ──────────────
Route::post('/sms-notify/{smsLogId}', [CallbackController::class, 'smsNotify'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('sms.notify');

// ─── Operator DCB Callbacks (no CSRF) ────────────────────────────────────────
Route::prefix('callback')->name('callback.')->withoutMiddleware([VerifyCsrfToken::class])->group(function () {
    // Robi WAP consent redirect (GET — browser is redirected back by Robi)
    Route::get('robi-consent/{txnRef}', [CallbackController::class, 'robiConsent'])->name('robi-consent');

    // Async POST callbacks from operators
    Route::post('robi',         [CallbackController::class, 'robi'])->name('robi');
    Route::post('grameenphone', [CallbackController::class, 'grameenphone'])->name('grameenphone');
    Route::post('banglalink',   [CallbackController::class, 'banglalink'])->name('banglalink');
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
        Route::post('tickets/bulk-delete', [TicketController::class, 'bulkDelete'])->name('tickets.bulk-delete');
        Route::post('tickets/{ticket}/sell', [TicketController::class, 'sell'])->name('tickets.sell');
        Route::delete('tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/csv', [ReportController::class, 'exportCsv'])->name('reports.csv');
        Route::get('reports/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
        Route::get('reports/sms', [ReportController::class, 'smsReport'])->name('reports.sms');
        Route::post('reports/sms/{transaction}/retry', [ReportController::class, 'retrySms'])->name('reports.sms.retry');

        Route::get('journey', [JourneyController::class, 'index'])->name('journey.index');
    });
});
