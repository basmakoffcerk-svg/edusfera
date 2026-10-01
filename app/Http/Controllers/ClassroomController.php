<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassroomChatMessage;
use App\Models\ClassroomFile;
use App\Models\ClassroomNote;
use App\Models\ClassroomSession;
use App\Models\DiagnosticAttempt;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\ProgressSnapshot;
use App\Models\SkillGap;
use App\Models\StudentGoal;
use App\Services\Classroom\AiService;
use App\Services\Classroom\LiveKitService;
use App\Services\ClassroomService;
use App\Services\Payment\PaymentService;
use App\Support\Formatters;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassroomController extends Controller
{
    public function __construct(private readonly ClassroomService $classroomService) {}

    public function show(Lesson $lesson, Request $request): View
    {
        $user = $request->user();

        if ($lesson->status === Lesson::STATUS_PENDING) {
            abort(403, 'Занятие ожидает подтверждения репетитором. Сначала подтвердите заявку в панели управления.');
        }

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому виртуальному классу.');
        }

        if (! config('classroom.enabled', false)) {
            $lesson->loadMissing('tutor', 'student');

            return view('classroom.maintenance', [
                'lesson' => $lesson,
                'user' => $user,
            ]);
        }

        $session = $this->classroomService->openClassroom($lesson, $user);
        $token = $this->classroomService->generateMediaToken($session, $user);

        $lesson->loadMissing('tutor.tutorProfile', 'student');

        $studentId = $lesson->student_id;

        $goals = StudentGoal::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $lesson->tutor_id)
            ->where('status', 'active')
            ->latest('id')
            ->get();

        $progress = ProgressSnapshot::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $lesson->tutor_id)
            ->latest('snapshot_date')
            ->limit(5)
            ->get();

        $homework = HomeworkAssignment::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $lesson->tutor_id)
            ->latest('id')
            ->limit(10)
            ->get();

        $skillGaps = SkillGap::query()
            ->where('student_id', $studentId)
            ->where('status', 'open')
            ->latest('id')
            ->get();

        $liveKitService = app(LiveKitService::class);
        $liveKitConfigured = $liveKitService->isConfigured();
        $liveKitToken = $liveKitConfigured ? $liveKitService->generateToken($lesson, $user) : null;
        $liveKitWsUrl = $liveKitConfigured ? $liveKitService->getWsUrl() : null;

        $joinToken = substr(hash_hmac('sha256', 'classroom_join_'.$lesson->id, (string) config('app.key')), 0, 16);
        $studentInviteUrl = route('classroom.join', ['lesson' => $lesson->id, 'token' => $joinToken]);

        $requestedRole = (string) $request->query('role');
        $isTutor = ($user->id === $lesson->tutor_id || $user->isAdmin()) && ($requestedRole !== 'student');
        $userRole = $isTutor ? 'tutor' : 'student';

        $jitsiSecret = substr(hash_hmac('sha256', 'jitsi_room_'.$lesson->id.'_'.$session->room_id, (string) config('app.key')), 0, 12);
        $jitsiRoomName = 'edusfera_'.$lesson->id.'_'.$jitsiSecret;
        $jitsiDomain = (string) config('classroom.jitsi_domain', 'meet.jit.si');

        return view('classroom.show', [
            'lesson' => $lesson,
            'session' => $session,
            'token' => $token,
            'mediaServerUrl' => $this->classroomService->getMediaServerUrl(),
            'iceServers' => $this->classroomService->getIceServers(),
            'liveKitConfigured' => $liveKitConfigured,
            'liveKitToken' => $liveKitToken,
            'liveKitWsUrl' => $liveKitWsUrl,
            'jitsiDomain' => $jitsiDomain,
            'jitsiRoomName' => $jitsiRoomName,
            'studentInviteUrl' => $studentInviteUrl,
            'userRole' => $userRole,
            'goals' => $goals,
            'progress' => $progress,
            'homework' => $homework,
            'skillGaps' => $skillGaps,
            'aiChatHistory' => $session->meta['ai_chat_history'] ?? [],
        ]);
    }

    public function join(Lesson $lesson, Request $request): RedirectResponse
    {
        $expectedToken = substr(hash_hmac('sha256', 'classroom_join_'.$lesson->id, (string) config('app.key')), 0, 16);
        $providedToken = (string) $request->query('token');

        if (! hash_equals($expectedToken, $providedToken)) {
            abort(403, 'Недействительная ссылка приглашения на урок.');
        }

        if ($lesson->status === Lesson::STATUS_CANCELLED) {
            abort(403, 'Этот урок был отменен.');
        }

        $student = $lesson->student;
        if (! $student) {
            abort(404, 'Ученик для данного урока не найден.');
        }

        $currentUser = $request->user();
        if ($currentUser && ($currentUser->id === $lesson->tutor_id || $currentUser->isAdmin())) {
            return redirect()->route('classroom.show', ['lesson' => $lesson, 'role' => 'student']);
        }

        Auth::login($student, true);
        $request->session()->regenerate();

        return redirect()->route('classroom.show', $lesson);
    }

    public function end(Lesson $lesson, Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id) {
            abort(403, 'Только преподаватель может завершить урок.');
        }

        $session = $lesson->activeClassroom;

        if ($session) {
            $this->classroomService->endClassroom($session);
        }

        if ($lesson->status === Lesson::STATUS_CONFIRMED && ($lesson->hasStarted() || ($lesson->end_time?->isPast() ?? false))) {
            $lesson->update(['status' => Lesson::STATUS_COMPLETED]);

            // Завершение урока должно запускать сеттлмент (перевод доли
            // репетитора из pending в available + захват холда в шлюзе).
            // Раньше статус менялся напрямую, урок выпадал из планировщика
            // lessons:complete (он выбирает только CONFIRMED), и деньги
            // репетитора зависали в pending навсегда.
            try {
                app(PaymentService::class)->settleCompletedLesson($lesson->fresh());
            } catch (\Throwable $e) {
                Log::warning('Classroom lesson settlement failed: '.$e->getMessage(), [
                    'lesson_id' => $lesson->id,
                ]);
            }
        }

        return redirect()->route('filament.admin.resources.lessons.index')->with('success', 'Виртуальный класс завершён.');
    }

    public function storeNote(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $validated = $request->validate([
            // Фронтенд отправляет { text }, API-контракт — content: принимаем оба.
            'content' => ['nullable', 'string', 'required_without:text'],
            'text' => ['nullable', 'string', 'required_without:content'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        $session = $lesson->activeClassroom;

        if (! $session) {
            throw ValidationException::withMessages([
                'session' => 'Нет активной сессии виртуального класса.',
            ]);
        }

        $note = ClassroomNote::query()->create([
            'classroom_session_id' => $session->id,
            'author_id' => $user->id,
            'content' => trim((string) ($validated['content'] ?? $validated['text'])),
            'is_shared' => (bool) ($validated['is_shared'] ?? false),
        ]);

        return response()->json([
            'success' => true,
            'note' => $note->load('author'),
        ], 201);
    }

    public function uploadFile(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,png,jpg,jpeg,gif', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/png,image/jpeg,image/gif'],
        ]);

        $session = $lesson->activeClassroom;

        if (! $session) {
            throw ValidationException::withMessages([
                'session' => 'Нет активной сессии виртуального класса.',
            ]);
        }

        $uploadedFile = $request->file('file');
        $storagePath = $uploadedFile->store("classroom-files/{$session->id}", 'local');

        $file = ClassroomFile::query()->create([
            'classroom_session_id' => $session->id,
            'uploaded_by' => $user->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'path' => $storagePath,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size_bytes' => $uploadedFile->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'file' => $file->load('uploader'),
        ], 201);
    }

    public function downloadFile(Lesson $lesson, ClassroomFile $file, Request $request): StreamedResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $sessionIds = $lesson->classroomSessions()->pluck('id');

        if (! $sessionIds->contains($file->classroom_session_id)) {
            abort(404, 'Файл не найден.');
        }

        if (! Storage::disk('local')->exists($file->path)) {
            abort(404, 'Файл не найден.');
        }

        // L3: strip newlines to prevent Content-Disposition header injection
        $safeName = str_replace(["\r", "\n"], '', $file->original_name);
        $safeName = preg_replace('/[^\w.\-]/', '_', $safeName);

        return Storage::disk('local')->download($file->path, $safeName, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    public function submitReport(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id) {
            abort(403, 'Только преподаватель может отправлять отчёт.');
        }

        $validated = $request->validate([
            'summary' => ['required', 'string'],
            'focus' => ['required', 'string'],
            // Фронтенд отправляет next_steps (множественное), API — next_step.
            'next_step' => ['nullable', 'string', 'required_without:next_steps'],
            'next_steps' => ['nullable', 'string'],
            'homework_summary' => ['nullable', 'string'],
            'score' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $nextStep = trim((string) ($validated['next_step'] ?? $validated['next_steps'] ?? ''));

        $lesson->update([
            'tutor_report_summary' => trim($validated['summary']),
            'tutor_report_focus' => trim($validated['focus']),
            'tutor_next_step' => $nextStep,
            'tutor_homework_summary' => isset($validated['homework_summary']) && trim($validated['homework_summary']) !== ''
                ? trim($validated['homework_summary'])
                : null,
            'tutor_report_score' => $validated['score'] ?? null,
            'tutor_reported_at' => now('UTC'),
        ]);

        return response()->json([
            'success' => true,
            'lesson' => $lesson->fresh(),
        ]);
    }

    public function getNotes(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json([]);
        }

        // Приватные заметки (is_shared=false) видит только их автор и
        // репетитор. Раньше студент получал ВСЕ заметки, включая личные
        // заметки репетитора о занятии.
        $isTutor = $user->id === $lesson->tutor_id;

        $notes = ClassroomNote::query()
            ->where('classroom_session_id', $session->id)
            ->when(! $isTutor, function ($query) use ($user): void {
                $query->where(function ($inner) use ($user): void {
                    $inner->where('is_shared', true)->orWhere('author_id', $user->id);
                });
            })
            ->latest('id')
            ->get()
            ->map(fn ($note) => [
                'id' => $note->id,
                'text' => $note->content,
                'time' => $note->created_at->setTimezone(config('app.timezone'))->format('H:i'),
            ]);

        return response()->json($notes);
    }

    public function getFiles(Lesson $lesson, Request $request): JsonResponse
    {
        if (! $this->classroomService->canAccess($lesson, $request->user())) {
            abort(403);
        }

        $sessionIds = $lesson->classroomSessions()->pluck('id');
        if ($sessionIds->isEmpty()) {
            return response()->json([]);
        }

        $files = ClassroomFile::query()
            ->whereIn('classroom_session_id', $sessionIds)
            ->latest('id')
            ->get()
            ->map(fn ($file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'size' => Formatters::formatBytes($file->size_bytes),
                'url' => route('classroom.files.download', ['lesson' => $lesson->id, 'file' => $file->id]),
            ]);

        return response()->json($files);
    }

    public function getChat(Lesson $lesson, Request $request): JsonResponse
    {
        if (! $this->classroomService->canAccess($lesson, $request->user())) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json([]);
        }

        $messages = ClassroomChatMessage::query()
            ->where('classroom_session_id', $session->id)
            ->oldest('id')
            ->get()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'userId' => $msg->sender_id,
                'userName' => $msg->sender->name ?? 'User',
                'text' => $msg->message,
                'time' => $msg->created_at->setTimezone(config('app.timezone'))->format('H:i'),
            ]);

        return response()->json($messages);
    }

    public function storeChat(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json(['error' => 'No active session'], 400);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $message = ClassroomChatMessage::query()->create([
            'classroom_session_id' => $session->id,
            'sender_id' => $user->id,
            'message' => trim($validated['message']),
        ]);

        return response()->json([
            'id' => $message->id,
            'userId' => $user->id,
            'userName' => $user->name,
            'text' => $message->message,
            'time' => $message->created_at->setTimezone(config('app.timezone'))->format('H:i'),
        ], 201);
    }

    public function getHomework(Lesson $lesson, Request $request): JsonResponse
    {
        if (! $this->classroomService->canAccess($lesson, $request->user())) {
            abort(403);
        }

        $homework = HomeworkAssignment::query()
            ->where('lesson_id', $lesson->id)
            ->latest('id')
            ->get()
            ->map(fn ($hw) => [
                'id' => $hw->id,
                'title' => $hw->title,
                'due_date' => $hw->due_at ? $hw->due_at->format('Y-m-d') : 'Нет',
                'status' => $hw->status === 'completed' ? 'done' : 'pending',
            ]);

        return response()->json($homework);
    }

    public function assignHomework(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id) {
            abort(403, 'Только преподаватель может назначать домашнее задание.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            // Фронтенд отправляет description и due_date (YYYY-MM-DD),
            // API-контракт — instructions и due_at (datetime). Принимаем оба.
            'instructions' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        $instructions = trim((string) ($validated['instructions'] ?? $validated['description'] ?? ''));
        $dueAt = $validated['due_at'] ?? null;

        if ($dueAt === null && isset($validated['due_date'])) {
            // Дата без времени — дедлайн до конца указанного дня.
            $dueAt = Carbon::parse($validated['due_date'])->setTime(23, 59, 0);
        }

        $homework = HomeworkAssignment::query()->create([
            'lesson_id' => $lesson->id,
            'student_id' => $lesson->student_id,
            'tutor_id' => $lesson->tutor_id,
            'title' => trim($validated['title']),
            'instructions' => $instructions !== '' ? $instructions : null,
            'source' => 'tutor',
            'status' => 'assigned',
            'assigned_at' => now('UTC'),
            'due_at' => $dueAt,
        ]);

        return response()->json([
            'success' => true,
            'homework' => $homework,
        ], 201);
    }

    public function studentProfile(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id) {
            abort(403, 'Только преподаватель может просматривать профиль ученика.');
        }

        $studentId = $lesson->student_id;
        $tutorId = $lesson->tutor_id;

        $goals = StudentGoal::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->where('status', 'active')
            ->latest('id')
            ->get();

        $progress = ProgressSnapshot::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->latest('snapshot_date')
            ->limit(10)
            ->get();

        $skillGaps = SkillGap::query()
            ->where('student_id', $studentId)
            ->where('status', 'open')
            ->latest('id')
            ->get();

        $homework = HomeworkAssignment::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->latest('id')
            ->limit(10)
            ->get();

        $diagnostic = DiagnosticAttempt::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->latest('taken_at')
            ->first();

        return response()->json([
            'goals' => $goals,
            'progress' => $progress,
            'skillGaps' => $skillGaps,
            'homework' => $homework,
            'lastDiagnostic' => $diagnostic,
        ]);
    }

    public function saveWhiteboardState(string $roomId, Request $request): JsonResponse
    {
        // M2: Use dedicated internal_secret with timing-safe comparison.
        // Never reuse jwt_secret for auth — it's for JWT signing only.
        $authHeader = $request->header('Authorization', '');
        $internalSecret = config('classroom.internal_secret');

        // empty(), а не === '': при null (env не задан) строгое сравнение
        // пропускало дальше, и 'Bearer ' с пустым секретом проходил проверку.
        if (! is_string($internalSecret) || $internalSecret === '' || ! hash_equals('Bearer '.$internalSecret, $authHeader)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $session = ClassroomSession::query()->where('room_id', $roomId)->first();
        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $state = $request->json()->all();
        if (empty($state)) {
            return response()->json(['success' => true]);
        }

        $this->classroomService->saveWhiteboardState($session, $state);

        return response()->json(['success' => true]);
    }

    public function chatAi(Lesson $lesson, Request $request, AiService $aiService): JsonResponse
    {
        $user = $request->user();
        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json(['error' => 'No active session'], 400);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['nullable', 'array', 'max:30'],
            'history.*.role' => ['nullable', 'string', 'in:user,assistant,model'],
            'history.*.text' => ['nullable', 'string', 'max:3000'],
            'history.*.content' => ['nullable', 'string', 'max:3000'],
        ]);

        $lesson->loadMissing('tutor.tutorProfile', 'student');

        $sessionHistory = is_array($session->meta['ai_chat_history'] ?? null)
            ? $session->meta['ai_chat_history']
            : [];

        // Priority to incoming history, fallback to session meta history
        $history = ! empty($validated['history']) ? $validated['history'] : $sessionHistory;

        $result = $aiService->chat(
            $validated['message'],
            $session->room_id,
            $lesson,
            $user,
            $history
        );

        // Update persistent conversation history in session meta
        $userMsgId = 'msg-'.(string) Str::uuid();
        $aiMsgId = 'msg-'.(string) Str::uuid();

        $sessionHistory[] = [
            'id' => $userMsgId,
            'role' => 'user',
            'text' => $validated['message'],
            'timestamp' => now('UTC')->toIso8601String(),
        ];

        $sessionHistory[] = [
            'id' => $aiMsgId,
            'role' => 'assistant',
            'text' => $result['reply'],
            'actions' => $result['actions'] ?? [],
            'created_entities' => $result['created_entities'] ?? [],
            'timestamp' => now('UTC')->toIso8601String(),
        ];

        if (count($sessionHistory) > 30) {
            $sessionHistory = array_slice($sessionHistory, -30);
        }

        $meta = $session->meta ?? [];
        $meta['ai_chat_history'] = $sessionHistory;
        $session->meta = $meta;
        $session->save();

        return response()->json([
            'reply' => $result['reply'],
            'actions' => $result['actions'] ?? [],
            'created_entities' => $result['created_entities'] ?? [],
            'history' => $sessionHistory,
        ]);
    }

    public function updateMeetingLink(Lesson $lesson, Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id && ! $user->isAdmin()) {
            abort(403, 'Только репетитор может указать ссылку на видеовстречу.');
        }

        $validated = $request->validate([
            'meeting_link' => ['required', 'url', 'max:500'],
        ]);

        $lesson->update([
            'meeting_link' => $validated['meeting_link'],
        ]);

        return back()->with('status', 'Ссылка на видеовстречу успешно сохранена!');
    }

    public function sendSignal(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:offer,answer,candidate,candidates,hangup,ping,hello,ready,restart,screenshare-state,wb-action,wb_broadcast,chat-message'],
            'payload' => ['nullable'],
            'client_id' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'in:tutor,student'],
        ]);

        $payload = $validated['payload'] ?? null;
        if (is_array($payload) && isset($payload['sdp']) && is_string($payload['sdp'])) {
            $lines = preg_split("/\r\n|\r|\n/", trim($payload['sdp']));
            $filtered = array_filter(array_map('trim', $lines), fn ($l) => $l !== '');
            $payload['sdp'] = implode("\r\n", $filtered)."\r\n";
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $nowMs = (int) (microtime(true) * 1000);
        $clientId = $validated['client_id'] ?? $request->header('X-Client-ID') ?? ('user_'.$user->id);
        $role = $validated['role'] ?? (($user->id === $lesson->tutor_id || $user->isAdmin()) ? 'tutor' : 'student');

        $newSignal = [
            'id' => (string) Str::uuid(),
            'sender_id' => $user->id,
            'client_id' => $clientId,
            'sender_role' => $role,
            'type' => $validated['type'],
            'payload' => $payload,
            'created_at_ms' => $nowMs,
        ];

        $cacheKey = 'classroom_signals_'.$lesson->id;
        $lockKey = 'lock_classroom_signals_'.$lesson->id;

        $updateSignals = static function () use ($cacheKey, &$newSignal, $nowMs): bool {
            $signals = Cache::get($cacheKey, []);
            if (! is_array($signals)) {
                $signals = [];
            }

            // On ICE restart, purge stale signals so renegotiation starts clean
            if ($newSignal['type'] === 'restart') {
                $signals = [];
            }

            // Find current maximum seq
            $lastSeq = 0;
            foreach ($signals as $item) {
                if (isset($item['seq']) && (int) $item['seq'] > $lastSeq) {
                    $lastSeq = (int) $item['seq'];
                }
            }
            $newSignal['seq'] = $lastSeq + 1;

            // Retain only signals from last 2 minutes to prevent unbounded growth
            $signals = array_values(array_filter($signals, static function (array $item) use ($nowMs): bool {
                return ($nowMs - ((int) ($item['created_at_ms'] ?? 0))) < 120000;
            }));

            $signals[] = $newSignal;
            Cache::put($cacheKey, $signals, now()->addHours(2));

            return true;
        };

        try {
            // Non-blocking fast lock acquisition (max 1s lock duration) to prevent PHP-FPM starvation and 503 errors
            $acquired = Cache::lock($lockKey, 1)->get($updateSignals);
            if (! $acquired) {
                $updateSignals();
            }
        } catch (\Throwable) {
            $updateSignals();
        }

        return response()->json([
            'status' => 'ok',
            'signal_id' => $newSignal['id'],
            'seq' => $newSignal['seq'] ?? null,
            'server_time' => $nowMs,
        ]);
    }

    public function getSignals(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $clientId = $request->query('client_id') ?? $request->header('X-Client-ID');
        $sinceSeq = $request->has('since_seq') ? (int) $request->query('since_seq') : null;
        $since = (int) $request->query('since', 0);
        $nowMs = (int) (microtime(true) * 1000);

        // Strict role resolution: tutor only listens to student, student only listens to tutor
        // Respects role override when tutor/admin is testing student perspective via ?role=student
        $requestedRole = (string) ($request->query('role') ?? $request->header('X-Client-Role'));
        $isTutorRole = ($user->id === $lesson->tutor_id || $user->isAdmin()) && ($requestedRole !== 'student');
        $userRole = $isTutorRole ? 'tutor' : 'student';
        $expectedPeerRole = ($userRole === 'tutor') ? 'student' : 'tutor';

        $cacheKey = 'classroom_signals_'.$lesson->id;
        $signals = Cache::get($cacheKey, []);
        if (! is_array($signals)) {
            $signals = [];
        }

        $maxSeq = 0;
        foreach ($signals as $s) {
            if (isset($s['seq']) && (int) $s['seq'] > $maxSeq) {
                $maxSeq = (int) $s['seq'];
            }
        }

        $minTimestamp = ($since > 0) ? ($since - 1500) : ($nowMs - 60000);

        // Return only signals from the counterpart participant
        $incoming = array_values(array_filter($signals, static function (array $item) use ($user, $clientId, $userRole, $expectedPeerRole, $sinceSeq, $minTimestamp): bool {
            // 1. NEVER receive own signals by user ID
            if ((int) ($item['sender_id'] ?? 0) === (int) $user->id) {
                return false;
            }

            // 2. NEVER receive own signals by client ID
            if (! empty($clientId) && ! empty($item['client_id']) && $item['client_id'] === $clientId) {
                return false;
            }

            // 3. STRICT 1-on-1 counterpart role filtering:
            // Tutor MUST ONLY receive signals from student.
            // Student MUST ONLY receive signals from tutor.
            if (! empty($item['sender_role'])) {
                if ($item['sender_role'] === $userRole) {
                    return false;
                }
                if ($item['sender_role'] !== $expectedPeerRole) {
                    return false;
                }
            }

            // 4. Sequence filtering (strictly monotonic)
            if ($sinceSeq !== null && $sinceSeq >= 0) {
                return (int) ($item['seq'] ?? 0) > $sinceSeq;
            }

            // 5. Fallback to timestamp filtering with grace window
            return (int) ($item['created_at_ms'] ?? 0) > $minTimestamp;
        }));

        return response()->json([
            'signals' => $incoming,
            'server_time' => $nowMs,
            'max_seq' => $maxSeq,
        ]);
    }

    public function getWhiteboardState(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        $session = $lesson->activeClassroom ?? ClassroomSession::query()
            ->where('lesson_id', $lesson->id)
            ->latest('id')
            ->first();

        return response()->json([
            'state' => $session?->whiteboard_state ?? [],
        ]);
    }

    public function saveWhiteboardStateHttp(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        $session = $lesson->activeClassroom ?? ClassroomSession::query()
            ->where('lesson_id', $lesson->id)
            ->latest('id')
            ->first();

        if ($session) {
            $state = $request->input('state', []);
            if (is_array($state)) {
                $this->classroomService->saveWhiteboardState($session, $state);
            }
        }

        return response()->json(['success' => true]);
    }

    public function syncWhiteboard(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $elements = $request->input('elements', []);
        $version = (int) $request->input('version', (int) (microtime(true) * 1000));
        $clientId = (string) $request->input('client_id', '');
        $isLocked = $request->has('is_locked') ? (bool) $request->input('is_locked') : null;

        $cacheKey = 'classroom_wb_'.$lesson->id;
        $prev = Cache::get($cacheKey, []);

        $state = [
            'elements' => is_array($elements) ? $elements : [],
            'version' => $version,
            'sender_id' => $user->id,
            'client_id' => $clientId,
            'is_locked' => $isLocked !== null ? $isLocked : ($prev['is_locked'] ?? false),
            'updated_at_ms' => (int) (microtime(true) * 1000),
        ];

        Cache::put($cacheKey, $state, now()->addHours(6));

        // Periodic database persistence (debounced to avoid thrashing MySQL)
        $dbCacheKey = 'classroom_wb_dbsave_'.$lesson->id;
        if (! Cache::has($dbCacheKey)) {
            Cache::put($dbCacheKey, true, 5);
            $session = $lesson->activeClassroom ?? ClassroomSession::query()
                ->where('lesson_id', $lesson->id)
                ->latest('id')
                ->first();
            if ($session && is_array($elements)) {
                $session->update(['whiteboard_state' => $elements]);
            }
        }

        return response()->json([
            'status' => 'ok',
            'version' => $version,
            'server_time' => $state['updated_at_ms'],
        ]);
    }

    public function pollWhiteboard(Lesson $lesson, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому уроку.');
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $sinceVersion = (int) $request->query('since_version', 0);
        $clientId = (string) $request->query('client_id', '');
        $cacheKey = 'classroom_wb_'.$lesson->id;

        // 1. Initial check (fast-path)
        $current = Cache::get($cacheKey);
        if ($current && isset($current['version'])) {
            if ($sinceVersion === 0 || ($current['version'] > $sinceVersion && ($current['client_id'] ?? '') !== $clientId)) {
                return response()->json([
                    'has_update' => true,
                    'state' => $current,
                ]);
            }
        } elseif ($sinceVersion === 0) {
            // Fallback to database for initial load if cache is empty
            $session = $lesson->activeClassroom ?? ClassroomSession::query()
                ->where('lesson_id', $lesson->id)
                ->latest('id')
                ->first();
            $dbElements = $session?->whiteboard_state ?? [];
            if (! empty($dbElements)) {
                $state = [
                    'elements' => $dbElements,
                    'version' => (int) (microtime(true) * 1000),
                    'sender_id' => null,
                    'client_id' => 'db_init',
                    'is_locked' => false,
                    'updated_at_ms' => (int) (microtime(true) * 1000),
                ];
                Cache::put($cacheKey, $state, now()->addHours(6));

                return response()->json([
                    'has_update' => true,
                    'state' => $state,
                ]);
            }
        }

        // 2. Long-poll: hold up to 2 seconds checking every 100ms (prevents PHP-FPM worker starvation)
        $startTime = microtime(true);
        while ((microtime(true) - $startTime) < 2.0) {
            usleep(100000); // 100ms
            $current = Cache::get($cacheKey);
            if ($current && isset($current['version']) && $current['version'] > $sinceVersion && ($current['client_id'] ?? '') !== $clientId) {
                return response()->json([
                    'has_update' => true,
                    'state' => $current,
                ]);
            }
        }

        return response()->json([
            'has_update' => false,
            'version' => $sinceVersion,
        ]);
    }
}
