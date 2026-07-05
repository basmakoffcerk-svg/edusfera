<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassroomFile;
use App\Models\ClassroomNote;
use App\Models\DiagnosticAttempt;
use App\Models\HomeworkAssignment;
use App\Support\Formatters;
use App\Models\Lesson;
use App\Models\ProgressSnapshot;
use App\Models\SkillGap;
use App\Models\StudentGoal;
use App\Services\ClassroomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function end(Lesson $lesson, Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if ($user->id !== $lesson->tutor_id) {
            abort(403, 'Только преподаватель может завершить урок.');
        }

        $session = $lesson->activeClassroom;

        if ($session) {
            $this->classroomService->endClassroom($session);
        }

        if ($lesson->status === Lesson::STATUS_CONFIRMED) {
            $lesson->update(['status' => Lesson::STATUS_COMPLETED]);
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
            'content' => ['required', 'string'],
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
            'content' => trim($validated['content']),
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

        $session = $lesson->activeClassroom;

        if (! $session || $file->classroom_session_id !== $session->id) {
            abort(404, 'Файл не найден.');
        }

        $filePath = storage_path("app/{$file->path}");

        $realPath = realpath($filePath);
        $storageBase = realpath(storage_path('app'));

        if ($realPath === false || $storageBase === false || ! str_starts_with($realPath, $storageBase)) {
            abort(404, 'Файл не найден.');
        }

        if (! file_exists($filePath)) {
            abort(404, 'Файл не найден.');
        }

        // L3: strip newlines to prevent Content-Disposition header injection
        $safeName = str_replace(["\r", "\n"], '', $file->original_name);
        $safeName = preg_replace('/[^\w.\-]/', '_', $safeName);

        return response()->streamDownload(function () use ($filePath): void {
            readfile($filePath);
        }, $safeName, [
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
            'next_step' => ['required', 'string'],
            'homework_summary' => ['nullable', 'string'],
            'score' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $lesson->update([
            'tutor_report_summary' => trim($validated['summary']),
            'tutor_report_focus' => trim($validated['focus']),
            'tutor_next_step' => trim($validated['next_step']),
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
        if (! $this->classroomService->canAccess($lesson, $request->user())) {
            abort(403);
        }

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json([]);
        }

        $notes = ClassroomNote::query()
            ->where('classroom_session_id', $session->id)
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

        $session = $lesson->activeClassroom;
        if (! $session) {
            return response()->json([]);
        }

        $files = ClassroomFile::query()
            ->where('classroom_session_id', $session->id)
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

        $messages = \App\Models\ClassroomChatMessage::query()
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

        $message = \App\Models\ClassroomChatMessage::query()->create([
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
            'instructions' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date', 'after:now'],
        ]);

        $homework = HomeworkAssignment::query()->create([
            'lesson_id' => $lesson->id,
            'student_id' => $lesson->student_id,
            'tutor_id' => $lesson->tutor_id,
            'title' => trim($validated['title']),
            'instructions' => isset($validated['instructions']) ? trim($validated['instructions']) : null,
            'source' => 'tutor',
            'status' => 'assigned',
            'assigned_at' => now('UTC'),
            'due_at' => $validated['due_at'] ?? null,
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

        if ($internalSecret === '' || ! hash_equals('Bearer '.$internalSecret, $authHeader)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $session = \App\Models\ClassroomSession::query()->where('room_id', $roomId)->first();
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

    public function chatAi(Lesson $lesson, Request $request, \App\Services\Classroom\AiService $aiService): JsonResponse
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
