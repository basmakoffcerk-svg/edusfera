<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_valid_json_and_contains_pwa_fields(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $content = file_get_contents($manifestPath);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertSame('Edusfera', $data['short_name']);
        $this->assertSame('standalone', $data['display']);
        $this->assertSame('#7D39EB', $data['theme_color']);
        $this->assertNotEmpty($data['icons']);
        $this->assertNotEmpty($data['shortcuts']);
    }

    public function test_sw_and_offline_assets_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));
        $this->assertFileExists(public_path('icons/icon-192x192.png'));
        $this->assertFileExists(public_path('icons/icon-512x512.png'));
        $this->assertFileExists(public_path('icons/icon-maskable-192x192.png'));
        $this->assertFileExists(public_path('icons/icon-maskable-512x512.png'));
    }

    public function test_badge_count_returns_zero_for_unauthenticated_user(): void
    {
        $response = $this->getJson('/api/pwa/badge-count');

        $response->assertOk();
        $response->assertJson([
            'count' => 0,
            'unread_messages' => 0,
            'today_lessons' => 0,
        ]);
    }

    public function test_badge_count_returns_correct_unread_messages_and_lessons(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $tutor = User::factory()->create(['role' => 'tutor']);

        $conversation = Conversation::create([
            'tutor_id' => $tutor->id,
            'student_id' => $user->id,
            'status' => 'active',
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $tutor->id,
            'message' => 'Привет! Готов к уроку?',
            'is_read' => false,
        ]);

        Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $user->id,
            'start_time' => now()->startOfDay()->addHours(14),
            'end_time' => now()->startOfDay()->addHours(15),
            'duration_minutes' => 60,
            'price' => 50,
            'platform_commission' => 10,
            'net_amount' => 40,
            'status' => Lesson::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($user)->getJson('/api/pwa/badge-count');

        $response->assertOk();
        $response->assertJson([
            'count' => 2,
            'unread_messages' => 1,
            'today_lessons' => 1,
        ]);
    }

    public function test_push_subscription_requires_auth_and_validates(): void
    {
        $this->postJson('/api/pwa/subscribe', [])->assertUnauthorized();

        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->postJson('/api/pwa/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/sample-token',
            'keys' => [
                'p256dh' => 'sample-p256dh-key',
                'auth' => 'sample-auth-key',
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/sample-token',
            'public_key' => 'sample-p256dh-key',
            'auth_token' => 'sample-auth-key',
        ]);
    }

    public function test_public_pages_include_pwa_meta(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('manifest.json', false);
        $response->assertSee('EdusferaHaptics', false);
    }
}
