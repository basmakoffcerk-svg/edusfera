<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\MessagesPage;
use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_conversation_and_sends_message(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291111111',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292222222',
        ]);

        $service = app(ChatService::class);

        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);
        $result = $service->sendMessage($conversation->id, $student->id, 'Здравствуйте, хочу обсудить занятие.');

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertSame('Здравствуйте, хочу обсудить занятие.', $result['message']->message);
        $this->assertNull($result['warning']);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'is_read' => false,
        ]);
    }

    public function test_it_marks_messages_as_read(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375293333333',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375294444444',
        ]);

        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);
        $service->sendMessage($conversation->id, $student->id, 'Первое сообщение');
        $service->markAsRead($tutor->id, $conversation->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'is_read' => true,
        ]);
    }

    public function test_it_masks_contacts_before_first_booking(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375295555555',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375296666666',
        ]);

        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);

        $result = $service->sendMessage($conversation->id, $student->id, 'Напишите мне на +375291234567');

        $this->assertNotNull($result['warning']);
        $this->assertStringContainsString('автоматически сразу после бронирования первого', $result['warning']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'message' => 'Напишите мне на [контакты скрыты]',
        ]);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'is_system' => true,
            'message' => $result['warning'],
        ]);
    }

    public function test_it_allows_contacts_after_first_booking(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291010101',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292020202',
        ]);

        $conversation = Conversation::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'last_message_at' => now(),
        ]);

        Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '6.00',
            'net_amount' => '34.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $result = app(ChatService::class)->sendMessage($conversation->id, $student->id, 'Мой номер +375291234567');

        $this->assertNull($result['warning']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'message' => 'Мой номер +375291234567',
        ]);
    }

    public function test_it_still_masks_payment_details_after_contacts_are_unlocked(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291010101',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292020202',
        ]);

        $conversation = Conversation::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'last_message_at' => now(),
        ]);

        Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '6.00',
            'net_amount' => '34.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $result = app(ChatService::class)->sendMessage($conversation->id, $student->id, 'Номер карты: 9112 3800 2624 3678');

        $this->assertNotNull($result['warning']);
        $this->assertStringContainsString('Реквизиты для прямых переводов скрыты', $result['warning']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'message' => '[платежные данные скрыты]: [платежные данные скрыты]',
        ]);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'is_system' => true,
            'message' => $result['warning'],
        ]);
    }

    public function test_it_penalizes_tutor_after_three_contact_bypass_attempts(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'moderation@example.test',
        ]);

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291010101',
        ]);

        TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'headline' => 'Подготовка к ЦТ',
            'bio' => 'Опытный преподаватель',
            'subjects' => ['Математика'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'is_verified' => true,
            'verification_status' => 'approved',
            'legal_status' => 'npd',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292020202',
        ]);

        $conversation = app(ChatService::class)->getOrCreateConversation($tutor->id, $student->id);

        foreach (range(1, 3) as $attempt) {
            app(ChatService::class)->sendMessage($conversation->id, $tutor->id, 'Мой telegram t.me/example и телефон +375291234567');
        }

        $profile = $tutor->tutorProfile()->firstOrFail();

        $this->assertSame(3, $profile->contact_bypass_attempts);
        $this->assertNotNull($profile->search_penalized_until);
        $this->assertTrue($profile->search_penalized_until->isFuture());
        Notification::assertSentTo($admin, \App\Notifications\ChatBypassAttemptNotification::class);
    }

    public function test_messages_page_does_not_open_foreign_conversation(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375297777777',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298888888',
        ]);

        $stranger = User::factory()->create([
            'role' => 'student',
            'phone' => '+375299999999',
        ]);

        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);
        $service->sendMessage($conversation->id, $student->id, 'Секретное сообщение');

        Livewire::actingAs($stranger)
            ->test(MessagesPage::class, ['conversation' => $conversation->id])
            ->assertSet('conversationId', null)
            ->assertDontSee('Секретное сообщение');
    }

    public function test_viewing_tutor_profile_does_not_create_conversation_implicitly(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'is_verified' => true,
        ]);

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $profile = TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'headline' => 'Подготовка к ЦТ',
            'bio' => 'Опытный преподаватель',
            'subjects' => ['Математика'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'is_verified' => true,
            'verification_status' => 'approved',
            'legal_status' => 'npd',
        ]);

        $this->actingAs($student)
            ->get(route('tutors.show', $profile))
            ->assertOk();

        $this->assertDatabaseCount('conversations', 0);
    }
}
