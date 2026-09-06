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
use App\Services\ClassroomService;
use App\Services\Payment\PaymentService;
use App\Support\Formatters;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassroomController extends Controller
{
    public function __construct(private readonly ClassroomService $classroomService) {}

    public function show(Lesson $lesson, Request $request): View
    {
        $user = $request->user();

        if (! $this->classroomService->canAccess($lesson, $user)) {
            abort(403, 'У вас нет доступа к этому виртуальному классу.');
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

        return view('classroom.show', [
            'lesson' => $lesson,
            'session' => $session,
            'token' => $token,
            'mediaServerUrl' => $this->classroomService->getMediaServerUrl(),
            'iceServers' => $this->classroomService->getIceServers(),
            'goals' => $goals,
            'progress' => $progress,
            'homework' => $homework,
            'skillGaps' => $skillGaps,
        ]);
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

        return redirect()->back()->with('success', 'Виртуальный класс завершён.');
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
        if (! $this->classroomService->canAccess($lesson, $request->user())) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json(['error' => 'No active session'], 400);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $reply = $aiService->chat($validated['message'], $session->room_id);

        return response()->json([
            'reply' => $reply,
        ]);
    }
}
