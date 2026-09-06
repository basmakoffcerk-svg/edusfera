<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_uncompromised_password(): void
    {
        Livewire::test(\App\Filament\Pages\Auth\Register::class)
            ->fillForm([
                'role' => 'student',
                'name' => 'Иван Иванов',
                'email' => 'student@edusfera.by',
                'phone' => '+375291112233',
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
                'terms' => true,
            ])
            ->call('register')
            ->assertHasFormErrors(['password']);
    }

    public function test_login_rate_limiter_blocks_repeated_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'user@edusfera.by',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $component = Livewire::test(\App\Filament\Pages\Auth\Login::class);

        // Perform 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $component->fillForm([
                'email' => 'user@edusfera.by',
                'password' => 'WrongPassword!'.$i,
            ])->call('authenticate');
        }

        // 6th attempt should trigger rate limiter / TooManyRequests notification
        $component->fillForm([
            'email' => 'user@edusfera.by',
            'password' => 'WrongPassword!999',
        ])
        ->call('authenticate')
        ->assertNotified();
    }
}
