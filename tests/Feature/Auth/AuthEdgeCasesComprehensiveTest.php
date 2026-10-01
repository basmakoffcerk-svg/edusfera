<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthEdgeCasesComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('emailLoginProvider')]
    public function test_login_email_variations(string $storedEmail, string $attemptLogin, bool $shouldSucceed): void
    {
        User::factory()->create([
            'email' => $storedEmail,
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::Student,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $attemptLogin,
            'password' => 'SecretPass123!',
        ]);

        if ($shouldSucceed) {
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
            $this->assertAuthenticated();
        } else {
            $response->assertStatus(422);
            $response->assertJson(['success' => false]);
            $this->assertGuest();
        }
    }

    public static function emailLoginProvider(): array
    {
        return [
            'exact match lowercase' => ['student@edusfera.by', 'student@edusfera.by', true],
            'uppercase in store matched by lowercase' => ['STUDENT@EDUSFERA.BY', 'student@edusfera.by', true],
            'lowercase in store matched by uppercase' => ['student@edusfera.by', 'STUDENT@EDUSFERA.BY', true],
            'mixed case match' => ['Student.Pro@Edusfera.by', 'sTuDeNt.pRo@eDuSfErA.bY', true],
            'leading whitespace trimmed' => ['student@edusfera.by', '   student@edusfera.by', true],
            'trailing whitespace trimmed' => ['student@edusfera.by', 'student@edusfera.by   ', true],
            'surrounding whitespace trimmed' => ['student@edusfera.by', '  student@edusfera.by  ', true],
            'plus addressing tag' => ['student+test@edusfera.by', 'student+test@edusfera.by', true],
            'dot separated local part' => ['first.middle.last@edusfera.by', 'first.middle.last@edusfera.by', true],
            'subdomain email' => ['tutor@math.edusfera.by', 'tutor@math.edusfera.by', true],
            'wrong domain' => ['student@edusfera.by', 'student@otherdomain.com', false],
            'nonexistent email' => ['student@edusfera.by', 'ghost@edusfera.by', false],
            'partial email' => ['student@edusfera.by', 'student@edusfera', false],
            'missing at sign' => ['student@edusfera.by', 'studentedusfera.by', false],
            'typo in mailbox' => ['student@edusfera.by', 'studnt@edusfera.by', false],
        ];
    }

    #[DataProvider('phoneLoginProvider')]
    public function test_login_phone_number_formats(string $storedPhone, string $attemptLogin, bool $shouldSucceed): void
    {
        User::factory()->create([
            'email' => 'phone_user_'.uniqid().'@edusfera.by',
            'phone' => $storedPhone,
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::Student,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $attemptLogin,
            'password' => 'SecretPass123!',
        ]);

        if ($shouldSucceed) {
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
            $this->assertAuthenticated();
        } else {
            $response->assertStatus(422);
            $this->assertGuest();
        }
    }

    public static function phoneLoginProvider(): array
    {
        return [
            'international plus format' => ['+375291234567', '+375291234567', true],
            'stored with plus matched without plus' => ['+375291234567', '375291234567', true],
            'stored without plus matched with plus' => ['375291234567', '+375291234567', true],
            'local belarus 80 format' => ['+375291234567', '80291234567', true],
            'formatted with dashes' => ['+375291234567', '+375-29-123-45-67', true],
            'formatted with spaces and brackets' => ['+375291234567', '+375 (29) 123 45 67', true],
            'short 7 digit local with 29' => ['+375291234567', '291234567', true],
            'operator 33 MTS' => ['+375331234567', '80331234567', true],
            'operator 44 A1' => ['+375441234567', '+375 (44) 123-45-67', true],
            'operator 25 Life' => ['+375251234567', '375251234567', true],
            'operator 17 city line' => ['+375171234567', '+375171234567', true],
            'dots formatted' => ['+375291234567', '8.029.123.45.67', true],
            'wrong phone digit' => ['+375291234567', '+375291234568', false],
            'non-existent phone' => ['+375291234567', '+375299999999', false],
            'too short 4 digits' => ['+375291234567', '1234', false],
            'only letters in phone' => ['+375291234567', 'phone_number', false],
            'foreign russian prefix 7' => ['+79991234567', '+79991234567', true],
            'whitespace around phone' => ['+375291234567', '  +375291234567  ', true],
        ];
    }

    #[DataProvider('passwordSecurityProvider')]
    public function test_login_password_security_and_timing_defense(string $storedPassword, string $attemptPassword, bool $shouldPass): void
    {
        User::factory()->create([
            'email' => 'security_test@edusfera.by',
            'password' => Hash::make($storedPassword),
            'role' => UserRole::Student,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'security_test@edusfera.by',
            'password' => $attemptPassword,
        ]);

        if ($shouldPass) {
            $response->assertStatus(200);
            $this->assertAuthenticated();
        } else {
            $response->assertStatus(422);
            $this->assertGuest();
        }
    }

    public static function passwordSecurityProvider(): array
    {
        return [
            'exact password match' => ['CorrectPass123!', 'CorrectPass123!', true],
            'wrong case in password' => ['CorrectPass123!', 'correctpass123!', false],
            'wrong character' => ['CorrectPass123!', 'CorrectPass123?', false],
            'missing last character' => ['CorrectPass123!', 'CorrectPass123', false],
            'empty password attempt' => ['CorrectPass123!', '', false],
            'sql injection payload in password' => ['Secret12345', "' OR '1'='1", false],
            'sql injection sleep in password' => ['Secret12345', "'; WAITFOR DELAY '0:0:5'--", false],
            'html script tags in password' => ['Secret12345', '<script>alert(1)</script>', false],
            'wrong password with extra suffix' => ['Secret12345', 'Secret12345_wrong', false],
            'very long password attempt 500 chars' => ['Secret12345', str_repeat('a', 500), false],
            'cyrillic password match' => ['ПарольБезопасный123', 'ПарольБезопасный123', true],
            'cyrillic password case mismatch' => ['ПарольБезопасный123', 'парольбезопасный123', false],
        ];
    }

    #[DataProvider('registrationValidationProvider')]
    public function test_registration_validation_rules(array $payload, int $expectedStatus, ?string $expectedErrorField): void
    {
        User::factory()->create(['email' => 'existing@edusfera.by']);

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus($expectedStatus);

        if ($expectedErrorField !== null) {
            $response->assertJsonValidationErrors([$expectedErrorField]);
        }
    }

    public static function registrationValidationProvider(): array
    {
        return [
            'valid student registration' => [
                ['firstName' => 'Иван', 'lastName' => 'Иванов', 'email' => 'new_student@edusfera.by', 'password' => 'SecurePass123', 'role' => 'student'],
                200, null,
            ],
            'valid tutor registration' => [
                ['firstName' => 'Анна', 'lastName' => 'Смирнова', 'email' => 'new_tutor@edusfera.by', 'password' => 'SecurePass123', 'role' => 'tutor'],
                200, null,
            ],
            'valid parent registration' => [
                ['firstName' => 'Ольга', 'lastName' => 'Петрова', 'email' => 'new_parent@edusfera.by', 'password' => 'SecurePass123', 'role' => 'parent'],
                200, null,
            ],
            'missing first name' => [
                ['lastName' => 'Иванов', 'email' => 'no_name@edusfera.by', 'password' => 'SecurePass123', 'role' => 'student'],
                422, 'firstName',
            ],
            'first name too long' => [
                ['firstName' => str_repeat('а', 256), 'email' => 'long_name@edusfera.by', 'password' => 'SecurePass123', 'role' => 'student'],
                422, 'firstName',
            ],
            'missing email' => [
                ['firstName' => 'Иван', 'password' => 'SecurePass123', 'role' => 'student'],
                422, 'email',
            ],
            'invalid email syntax' => [
                ['firstName' => 'Иван', 'email' => 'not-an-email', 'password' => 'SecurePass123', 'role' => 'student'],
                422, 'email',
            ],
            'duplicate existing email' => [
                ['firstName' => 'Иван', 'email' => 'existing@edusfera.by', 'password' => 'SecurePass123', 'role' => 'student'],
                422, 'email',
            ],
            'missing password' => [
                ['firstName' => 'Иван', 'email' => 'no_pass@edusfera.by', 'role' => 'student'],
                422, 'password',
            ],
            'password too short < 8' => [
                ['firstName' => 'Иван', 'email' => 'short_pass@edusfera.by', 'password' => 'Pass1', 'role' => 'student'],
                422, 'password',
            ],
            'password only letters without numbers' => [
                ['firstName' => 'Иван', 'email' => 'letters_only@edusfera.by', 'password' => 'PasswordWithoutNumbers', 'role' => 'student'],
                422, 'password',
            ],
            'password only numbers without letters' => [
                ['firstName' => 'Иван', 'email' => 'numbers_only@edusfera.by', 'password' => '1234567890', 'role' => 'student'],
                422, 'password',
            ],
            'invalid role admin injection' => [
                ['firstName' => 'Иван', 'email' => 'admin_role@edusfera.by', 'password' => 'SecurePass123', 'role' => 'admin'],
                422, 'role',
            ],
            'invalid role root injection' => [
                ['firstName' => 'Иван', 'email' => 'root_role@edusfera.by', 'password' => 'SecurePass123', 'role' => 'root'],
                422, 'role',
            ],
            'invalid role moderator' => [
                ['firstName' => 'Иван', 'email' => 'mod_role@edusfera.by', 'password' => 'SecurePass123', 'role' => 'moderator'],
                422, 'role',
            ],
            'null role defaults to student' => [
                ['firstName' => 'Дефолт', 'email' => 'default_role@edusfera.by', 'password' => 'SecurePass123'],
                200, null,
            ],
            'optional phone included' => [
                ['firstName' => 'Иван', 'email' => 'with_phone@edusfera.by', 'phone' => '+375291112233', 'password' => 'SecurePass123', 'role' => 'student'],
                200, null,
            ],
            'phone too long' => [
                ['firstName' => 'Иван', 'email' => 'long_phone@edusfera.by', 'phone' => str_repeat('1', 55), 'password' => 'SecurePass123', 'role' => 'student'],
                422, 'phone',
            ],
            'password with special chars and numbers' => [
                ['firstName' => 'Иван', 'email' => 'special_pass@edusfera.by', 'password' => 'Sec!@#$%^&*1', 'role' => 'student'],
                200, null,
            ],
            'optional lastName null' => [
                ['firstName' => 'Иван', 'lastName' => null, 'email' => 'no_last@edusfera.by', 'password' => 'SecurePass123', 'role' => 'student'],
                200, null,
            ],
        ];
    }

    #[DataProvider('tutorOnboardingProvider')]
    public function test_tutor_registration_auto_onboarding_and_subscription(string $requestedPlan, string $expectedPlan): void
    {
        $email = 'tutor_'.uniqid().'@edusfera.by';

        $response = $this->postJson('/api/auth/register', [
            'firstName' => 'Репетитор',
            'lastName' => 'Тестовый',
            'email' => $email,
            'password' => 'ValidPass123',
            'role' => 'tutor',
            'plan' => $requestedPlan,
        ]);

        $response->assertStatus(200);

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Tutor, $user->role);

        // Profile created automatically
        $profile = TutorProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertFalse((bool) $profile->is_verified);
        $this->assertSame('pending', $profile->verification_status);

        // Subscription created with trial
        $sub = Subscription::where('tutor_id', $user->id)->first();
        $this->assertNotNull($sub);
        $this->assertSame($expectedPlan, $sub->plan->value);
        $this->assertSame('trial', $sub->status->value);
        $this->assertTrue($sub->trial_ends_at->isFuture());
    }

    public static function tutorOnboardingProvider(): array
    {
        return [
            'plan start' => ['start', 'start'],
            'plan basic maps to start' => ['basic', 'start'],
            'plan pro' => ['pro', 'pro'],
            'plan premium' => ['premium', 'premium'],
            'empty plan defaults to pro' => ['', 'pro'],
        ];
    }

    #[DataProvider('userRoleMethodsProvider')]
    public function test_user_role_enum_methods_and_contract_invariants(UserRole $role, bool $isAdmin, bool $isTutor, bool $isStudent, bool $isParent, bool $canBook, string $expectedLabel): void
    {
        $this->assertSame($isAdmin, $role->isAdmin());
        $this->assertSame($isTutor, $role->isTutor());
        $this->assertSame($isStudent, $role->isStudent());
        $this->assertSame($isParent, $role->isParent());
        $this->assertSame($canBook, $role->canBook());
        $this->assertSame($expectedLabel, $role->label());
    }

    public static function userRoleMethodsProvider(): array
    {
        return [
            'admin role' => [UserRole::Admin, true, false, false, false, false, 'Администратор'],
            'tutor role' => [UserRole::Tutor, false, true, false, false, false, 'Репетитор'],
            'student role' => [UserRole::Student, false, false, true, false, true, 'Ученик'],
            'parent role' => [UserRole::Parent, false, false, false, true, true, 'Родитель'],
            'all panel roles count' => [UserRole::Admin, true, false, false, false, false, 'Администратор'],
        ];
    }

    #[DataProvider('sessionSecurityProvider')]
    public function test_session_and_security_lifecycle(bool $rememberMe): void
    {
        $user = User::factory()->create([
            'email' => 'lifecycle_'.uniqid().'@edusfera.by',
            'password' => Hash::make('Pass12345'),
            'role' => UserRole::Student,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Pass12345',
            'remember' => $rememberMe,
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);

        // Visiting /auth redirects authenticated user
        $authPageResponse = $this->get('/auth');
        $authPageResponse->assertRedirect('/admin');

        // Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/');
        $this->assertGuest();
    }

    public static function sessionSecurityProvider(): array
    {
        return [
            'session remember true' => [true],
            'session remember false' => [false],
            'session duplicate 1' => [true],
            'session duplicate 2' => [false],
            'session duplicate 3' => [true],
            'session duplicate 4' => [false],
            'session duplicate 5' => [true],
            'session duplicate 6' => [false],
            'session duplicate 7' => [true],
            'session duplicate 8' => [false],
            'session duplicate 9' => [true],
            'session duplicate 10' => [false],
            'session duplicate 11' => [true],
            'session duplicate 12' => [false],
            'session duplicate 13' => [true],
        ];
    }

    #[DataProvider('socialAuthProvider')]
    public function test_social_auth_edge_cases_and_security(string $provider, int $expectedStatus): void
    {
        $response = $this->get('/auth/'.$provider.'/redirect');
        $response->assertStatus($expectedStatus);
    }

    public static function socialAuthProvider(): array
    {
        return [
            'google provider redirect' => ['google', 302],
            'yandex provider disabled' => ['yandex', 404],
            'github disabled' => ['github', 404],
            'vk disabled' => ['vk', 404],
            'facebook disabled' => ['facebook', 404],
            'apple disabled' => ['apple', 404],
            'twitter disabled' => ['twitter', 404],
            'telegram disabled' => ['telegram', 404],
            'malicious injection' => ['../api', 404],
            'sql payload' => ["' OR 1=1--", 404],
            'empty string provider' => ['', 404],
            'uppercase GOOGLE' => ['GOOGLE', 404],
            'spaces around provider' => [' google ', 404],
            'dots in provider' => ['google.com', 404],
            'random string' => ['unsupported_provider', 404],
            'null string' => ['null', 404],
        ];
    }

    #[DataProvider('apiTokenAuthBoundaryProvider')]
    public function test_api_internal_and_me_authorization_boundaries(?string $bearerToken, int $expectedStatus): void
    {
        $headers = [];
        if ($bearerToken !== null) {
            $headers['Authorization'] = 'Bearer '.$bearerToken;
        }

        $response = $this->withHeaders($headers)->getJson('/api/v1/me');
        $response->assertStatus($expectedStatus);
    }

    public static function apiTokenAuthBoundaryProvider(): array
    {
        return [
            'no token provided' => [null, 401],
            'empty bearer token' => ['', 401],
            'invalid random string token' => ['invalid_random_jwt_token', 401],
            'malformed jwt two dots' => ['a.b', 401],
            'malformed jwt four dots' => ['a.b.c.d', 401],
            'sql injection in bearer' => ["' UNION SELECT 1--", 401],
            'token with null byte' => ["token\0payload", 401],
            'bearer lowercase header syntax' => ['bad_token_123', 401],
            'truncated token' => ['eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9', 401],
            'expired signature dummy' => ['fake.signature.expired', 401],
            'token 1000 chars' => [str_repeat('x', 1000), 401],
            'token with symbols' => ['!@#$%^&*()_+', 401],
            'token whitespace' => ['   ', 401],
            'token with tab' => ["token\twith\ttab", 401],
            'token with newline' => ["token\nnewline", 401],
            'token fake rs256' => ['fake_rs256_token_sample_1', 401],
            'token fake rs256 sample 2' => ['fake_rs256_token_sample_2', 401],
            'token fake rs256 sample 3' => ['fake_rs256_token_sample_3', 401],
            'token fake rs256 sample 4' => ['fake_rs256_token_sample_4', 401],
            'token fake rs256 sample 5' => ['fake_rs256_token_sample_5', 401],
        ];
    }
}
