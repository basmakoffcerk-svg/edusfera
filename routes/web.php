<?php

declare(strict_types=1);

use App\Http\Controllers\AccountSwitcherController;
use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\AlfaBankWebhookController;
use App\Http\Controllers\Auth\AuroraAuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\LessonBookingController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\Payment\AlfaBankHostedCheckoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SitemapController;
use App\Services\MultiAccountService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/platform', function () {
    return view('home');
})->name('platform');

Route::redirect('/promo', '/', 301);
Route::any('/promo/{any}', function () {
    return redirect('/', 301);
})->where('any', '.*');

// 301 Redirects for Search Engines (Fixes Google Search Console 404s)
Route::redirect('/index.html', '/', 301);
Route::redirect('/index.php', '/', 301);
Route::redirect('/privacy-policy.html', '/privacy-policy', 301);
Route::redirect('/offer.html', '/offer', 301);
Route::redirect('/refund-policy.html', '/refund-policy', 301);
Route::redirect('/contacts.html', '/contacts', 301);
Route::redirect('/payment-security.html', '/payment-security', 301);

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/login', [AuroraAuthController::class, 'showAuthPage'])->name('login');
Route::get('/register', [AuroraAuthController::class, 'showAuthPage'])->name('register');
Route::get('/auth', [AuroraAuthController::class, 'showAuthPage'])->name('auth');
Route::get('/admin/login', [AuroraAuthController::class, 'showAuthPage'])->name('filament.admin.auth.login');
Route::get('/site-admin/login', [AuroraAuthController::class, 'showAuthPage'])->name('filament.site-admin.auth.login');

// ─── OAuth (Google) ───
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

Route::post('/api/auth/login', [AuroraAuthController::class, 'login'])
    ->middleware('throttle:web.auth');
Route::post('/api/auth/register', [AuroraAuthController::class, 'register'])
    ->middleware('throttle:web.auth');
Route::post('/api/auth/forgot-password', [AuroraAuthController::class, 'sendResetCode'])
    ->middleware('throttle:5,1');
Route::post('/api/auth/verify-reset-code', [AuroraAuthController::class, 'verifyResetCode'])
    ->middleware('throttle:10,1');
Route::post('/api/auth/reset-password', [AuroraAuthController::class, 'resetPassword'])
    ->middleware('throttle:15,1');
Route::post('/api/subscription/init-alfa-sdk', [AuroraAuthController::class, 'initSubscriptionAlfaSdk'])
    ->middleware('throttle:15,1');

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
Route::view('/about', 'about')->name('about');
Route::redirect('/about-us', '/about', 301);

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
Route::post('/checkout/{lesson}/promo/apply', [CheckoutController::class, 'applyPromo'])
    ->middleware(['auth', 'throttle:15,1'])
    ->name('checkout.promo.apply');
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

// ─── Alfa-Bank Hosted Payment Page (UAT Sandbox / Test Mock) ───
Route::get('/payments/alfabank/hosted-test', [AlfaBankHostedCheckoutController::class, 'show'])
    ->name('payments.alfabank.hosted-test');

// ─── News Portal ───
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

// ─── Virtual Classroom ───
Route::get('/classroom/{lesson}/join', [ClassroomController::class, 'join'])
    ->name('classroom.join');

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
        ->withoutMiddleware([ThrottleRequests::class, 'throttle:120,1'])
        ->name('classroom.chat.get');
    Route::post('/classroom/{lesson}/chat', [ClassroomController::class, 'storeChat'])
        ->withoutMiddleware([ThrottleRequests::class, 'throttle:120,1'])
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
    Route::post('/classroom/{lesson}/meeting-link', [ClassroomController::class, 'updateMeetingLink'])
        ->middleware('throttle:30,1')
        ->name('classroom.meeting-link');
    Route::get('/classroom/{lesson}/signal', [ClassroomController::class, 'getSignals'])
        ->withoutMiddleware([ThrottleRequests::class, 'throttle:120,1'])
        ->name('classroom.signal.get');
    Route::post('/classroom/{lesson}/signal', [ClassroomController::class, 'sendSignal'])
        ->withoutMiddleware([ThrottleRequests::class, 'throttle:120,1'])
        ->name('classroom.signal.send');
    Route::get('/classroom/{lesson}/whiteboard', [ClassroomController::class, 'getWhiteboardState'])
        ->withoutMiddleware([ThrottleRequests::class, 'throttle:120,1'])
        ->name('classroom.whiteboard.get');
    Route::post('/classroom/{lesson}/whiteboard', [ClassroomController::class, 'saveWhiteboardStateHttp'])
        ->withoutMiddleware([ThrottleRequests::class, 'throttle:120,1'])
        ->name('classroom.whiteboard.save');
    Route::post('/classroom/{lesson}/whiteboard/sync', [ClassroomController::class, 'syncWhiteboard'])
        ->withoutMiddleware([ThrottleRequests::class, 'throttle:120,1'])
        ->name('classroom.whiteboard.sync');
    Route::get('/classroom/{lesson}/whiteboard/poll', [ClassroomController::class, 'pollWhiteboard'])
        ->withoutMiddleware([ThrottleRequests::class, 'throttle:120,1'])
        ->name('classroom.whiteboard.poll');
    Route::post('/admin/ai-copilot/chat', [AiCopilotController::class, 'chat'])
        ->middleware('throttle:30,1')
        ->name('admin.ai-copilot.chat');
    Route::post('/admin/ai-copilot/lesson-plan', [AiCopilotController::class, 'generateLessonPlan'])
        ->middleware('throttle:20,1')
        ->name('admin.ai-copilot.lesson-plan');
    Route::post('/admin/ai-copilot/quiz', [AiCopilotController::class, 'generateQuiz'])
        ->middleware('throttle:20,1')
        ->name('admin.ai-copilot.quiz');
    Route::post('/admin/ai-copilot/solve-task', [AiCopilotController::class, 'solveTask'])
        ->middleware('throttle:20,1')
        ->name('admin.ai-copilot.solve-task');
});

// ─── Internal API ───
Route::post('/api/internal/classroom/{roomId}/whiteboard', [ClassroomController::class, 'saveWhiteboardState'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->middleware('throttle:60,1')
    ->name('internal.classroom.whiteboard');

// ─── PWA Badging & Push Notifications ────────
Route::get('/api/pwa/badge-count', [PwaController::class, 'badgeCount'])
    ->middleware('throttle:60,1')
    ->name('pwa.badge');
Route::post('/api/pwa/subscribe', [PwaController::class, 'subscribe'])->middleware('auth')->name('pwa.subscribe');
