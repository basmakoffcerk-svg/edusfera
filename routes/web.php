<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\LessonBookingController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\AccountSwitcherController;
use App\Http\Controllers\ClassroomController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/for-tutors', function () {
    return view('for-tutors');
})->name('for-tutors');

Route::view('/offer', 'legal.offer')->name('legal.offer');
Route::view('/refund-policy', 'legal.refund-policy')->name('legal.refund');
Route::view('/privacy-policy', 'legal.privacy-policy')->name('legal.privacy');
Route::view('/contacts', 'legal.contacts')->name('contacts');

Route::post('/logout', function (Request $request) {
    app(\App\Services\MultiAccountService::class)->clearAll();
    \Filament\Facades\Filament::auth()->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::match(['get', 'post'], '/account/switch/{userId}', [AccountSwitcherController::class, 'switch'])
    ->name('account.switch');
Route::match(['get', 'post'], '/account/add', [AccountSwitcherController::class, 'addAccount'])
    ->name('account.add');

Route::get('/tutors', [CatalogController::class, 'index'])->name('tutors.index');
Route::get('/tutors/{tutor}', [CatalogController::class, 'show'])->name('tutors.show');
Route::post('/tutors/{tutor}/book', [LessonBookingController::class, 'store'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('tutors.book');
Route::get('/checkout/{lesson}', [CheckoutController::class, 'show'])
    ->middleware('auth')
    ->name('checkout.show');
Route::post('/checkout/{lesson}/pay', [CheckoutController::class, 'pay'])
    ->middleware(['auth', 'throttle:5,1'])
    ->name('checkout.pay');
Route::get('/checkout/{lesson}/success', [CheckoutController::class, 'success'])
    ->middleware('auth')
    ->name('checkout.success');
Route::get('/checkout/{lesson}/calendar.ics', [CheckoutController::class, 'calendar'])
    ->middleware('auth')
    ->name('checkout.calendar');
Route::post('/tutors/{tutor}/conversation', [ConversationController::class, 'startWithTutor'])
    ->middleware('auth')
    ->name('tutors.conversation');
Route::post('/lessons/{lesson}/conversation', [ConversationController::class, 'startFromLesson'])
    ->middleware('auth')
    ->name('lessons.conversation');
Route::post('/payments/webhook', PaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('payments.webhook');

// ─── Virtual Classroom ───
Route::middleware('auth')->group(function (): void {
    Route::get('/classroom/{lesson}', [ClassroomController::class, 'show'])
        ->name('classroom.show');
    Route::post('/classroom/{lesson}/end', [ClassroomController::class, 'end'])
        ->name('classroom.end');
    Route::get('/classroom/{lesson}/notes', [ClassroomController::class, 'getNotes'])
        ->name('classroom.notes.get');
    Route::post('/classroom/{lesson}/notes', [ClassroomController::class, 'storeNote'])
        ->name('classroom.notes.store');
        
    Route::get('/classroom/{lesson}/files', [ClassroomController::class, 'getFiles'])
        ->name('classroom.files.get');
    Route::post('/classroom/{lesson}/files', [ClassroomController::class, 'uploadFile'])
        ->name('classroom.files.upload');
        
    Route::get('/classroom/{lesson}/chat', [ClassroomController::class, 'getChat'])
        ->name('classroom.chat.get');
    Route::post('/classroom/{lesson}/chat', [ClassroomController::class, 'storeChat'])
        ->name('classroom.chat.store');
        
    Route::get('/classroom/{lesson}/homework', [ClassroomController::class, 'getHomework'])
        ->name('classroom.homework.get');
    Route::get('/classroom/{lesson}/files/{file}/download', [ClassroomController::class, 'downloadFile'])
        ->name('classroom.files.download');
    Route::post('/classroom/{lesson}/report', [ClassroomController::class, 'submitReport'])
        ->name('classroom.report');
    Route::post('/classroom/{lesson}/homework', [ClassroomController::class, 'assignHomework'])
        ->name('classroom.homework');
    Route::get('/classroom/{lesson}/student-profile', [ClassroomController::class, 'studentProfile'])
        ->name('classroom.student-profile');
});

// ─── Internal API ───
Route::post('/api/internal/classroom/{roomId}/whiteboard', [ClassroomController::class, 'saveWhiteboardState'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('internal.classroom.whiteboard');
