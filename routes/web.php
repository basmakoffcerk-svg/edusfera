<?php

declare(strict_types=1);

use App\Http\Controllers\AccountSwitcherController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\LessonBookingController;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $promoPath = public_path('promo/index.html');
    if (file_exists($promoPath)) {
        return response()->file($promoPath);
    }

    return view('home');
})->name('home');

Route::get('/platform', function () {
    return view('home');
})->name('platform');

use App\Http\Controllers\Auth\AuroraAuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\AlfaBankWebhookController;
use App\Services\MultiAccountService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

Route::get('/login', [AuroraAuthController::class, 'showAuthPage'])->name('login');
Route::get('/register', [AuroraAuthController::class, 'showAuthPage'])->name('register');
Route::get('/auth', [AuroraAuthController::class, 'showAuthPage'])->name('auth');
Route::get('/admin/login', [AuroraAuthController::class, 'showAuthPage'])->name('filament.admin.auth.login');
Route::get('/site-admin/login', [AuroraAuthController::class, 'showAuthPage'])->name('filament.site-admin.auth.login');

// ─── OAuth (Google & Yandex) ───
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

Route::post('/api/auth/login', [AuroraAuthController::class, 'login'])
    ->middleware('throttle:web.auth');
Route::post('/api/auth/register', [AuroraAuthController::class, 'register'])
    ->middleware('throttle:web.auth');
Route::post('/api/subscription/confirm-plan', [AuroraAuthController::class, 'confirmPlan']);
Route::post('/api/subscription/init-alfa-sdk', [AuroraAuthController::class, 'initSubscriptionAlfaSdk']);

Route::get('/for-tutors', function () {
    return view('for-tutors');
})->name('for-tutors');

// ─── Public Diagnostic (entry point before registration) ───
Route::get('/diagnostic', [DiagnosticController::class, 'show'])
    ->name('diagnostic.show');
Route::get('/diagnostic/questions', [DiagnosticController::class, 'questions'])
    ->name('diagnostic.questions');
Route::post('/diagnostic/submit', [DiagnosticController::class, 'submitStep'])
    ->middleware('throttle:30,1')
    ->name('diagnostic.submit');
Route::post('/diagnostic/answers', [DiagnosticController::class, 'submitAnswers'])
    ->middleware('throttle:30,1')
    ->name('diagnostic.answers');
Route::get('/diagnostic/result', [DiagnosticController::class, 'finish'])
    ->name('diagnostic.finish');

Route::view('/offer', 'legal.offer')->name('legal.offer');
Route::view('/refund-policy', 'legal.refund-policy')->name('legal.refund');
Route::view('/privacy-policy', 'legal.privacy-policy')->name('legal.privacy');
Route::view('/payment-security', 'legal.payment-security')->name('legal.payment-security');
Route::view('/contacts', 'legal.contacts')->name('contacts');

Route::post('/logout', function (Request $request) {
    app(MultiAccountService::class)->clearAll();
    Filament::auth()->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::post('/account/switch/{userId}', [AccountSwitcherController::class, 'switch'])
    ->middleware('auth')
    ->name('account.switch');
Route::match(['get', 'post'], '/account/add', [AccountSwitcherController::class, 'addAccount'])
    ->middleware('auth')
    ->name('account.add');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/tutors', [CatalogController::class, 'index'])->name('tutors.index');
Route::get('/tutors/{tutor}', [CatalogController::class, 'show'])->name('tutors.show');
Route::get('/tutor/{tutor}', [CatalogController::class, 'show'])->name('tutor.show');
Route::post('/tutors/{tutor}/book', [LessonBookingController::class, 'store'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('tutors.book');
Route::post('/tutor/{tutor}/book', [LessonBookingController::class, 'store'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('tutor.book');
Route::get('/checkout/{lesson}', [CheckoutController::class, 'show'])
    ->middleware('auth')
    ->name('checkout.show');
Route::post('/checkout/{lesson}/pay', [CheckoutController::class, 'pay'])
    ->middleware(['auth', 'throttle:checkout.pay'])
    ->name('checkout.pay');
Route::post('/checkout/{lesson}/alfa-sdk-init', [CheckoutController::class, 'initAlfaSdk'])
    ->middleware(['auth', 'throttle:checkout.pay'])
    ->name('checkout.alfa-sdk.init');
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
Route::post('/payments/alfabank/webhook', AlfaBankWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('payments.alfabank.webhook');
Route::post('/webhooks/alfabank', AlfaBankWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.alfabank');
Route::post('/api/v1/payments/alfabank/webhook', AlfaBankWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('api.payments.alfabank.webhook');
Route::post('/payments/webpay/webhook', AlfaBankWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('payments.webpay.webhook');
Route::post('/webhooks/webpay', AlfaBankWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.webpay');

// ─── News Portal ───
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

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
    Route::post('/classroom/{lesson}/ai-chat', [ClassroomController::class, 'chatAi'])
        ->middleware('throttle:15,1')
        ->name('classroom.ai-chat');
});

// ─── Internal API ───
Route::post('/api/internal/classroom/{roomId}/whiteboard', [ClassroomController::class, 'saveWhiteboardState'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('internal.classroom.whiteboard');
