<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class MultiAccountService
{
    private const COOKIE_NAME = 'edusfera_linked_ids';
    private const COOKIE_LIFETIME = 43200; // 30 days in minutes

    /**
     * Get all linked accounts from the persistent cookie (excluding current user).
     *
     * @return array<int, array{id: int, name: string, email: string, role: string}>
     */
    public function getLinkedAccounts(): array
    {
        $ids = $this->getIdsFromCookie();
        $currentId = Auth::guard('web')->id();

        // Filter out current user and invalid IDs
        $linkedIds = array_filter($ids, fn ($id) => $id && $id !== $currentId);

        if (empty($linkedIds)) {
            return [];
        }

        return User::query()
            ->whereIn('id', $linkedIds)
            ->get(['id', 'name', 'email', 'role'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ])
            ->values()
            ->all();
    }

    /**
     * Add a user ID to the linked accounts cookie.
     */
    public function addId(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $ids = $this->getIdsFromCookie();

        if (! in_array($userId, $ids, true)) {
            $ids[] = $userId;
            $this->saveIdsToCookie($ids);
        }
    }

    /**
     * Switch to a linked account by user ID.
     * Uses only standard Laravel 'web' guard — safe to call from any route context.
     */
    public function switchTo(int $userId): bool
    {
        $ids = $this->getIdsFromCookie();

        if (! in_array($userId, $ids, true)) {
            return false;
        }

        /** @var User|null $user */
        $user = User::query()->find($userId);
        if (! $user) {
            $this->removeId($userId);
            return false;
        }

        $guard = Auth::guard('web');

        // Save current user to cookie before switching
        if ($currentId = $guard->id()) {
            $this->addId($currentId);
        }

        // Log in as the target user via the 'web' guard
        $guard->login($user, remember: true);

        // Regenerate session to prevent fixation attacks
        session()->regenerate();

        // Update the password hash the AuthenticateSession middleware
        // checks on every Filament request (key = 'password_hash_{guardName}')
        session()->put('password_hash_web', $user->getAuthPassword());

        return true;
    }

    /**
     * Remove a user ID from the linked accounts cookie.
     */
    public function removeId(int $userId): void
    {
        $ids = $this->getIdsFromCookie();
        $ids = array_filter($ids, fn ($id) => $id !== $userId);
        $this->saveIdsToCookie($ids);
    }

    /**
     * Clear all linked accounts (call on full logout).
     */
    public function clearAll(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    /**
     * Get role label in Russian.
     */
    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Админ',
            'tutor' => 'Репетитор',
            'student' => 'Ученик',
            'parent' => 'Родитель',
            default => $role,
        };
    }

    /**
     * Internal: Read IDs from cookie.
     *
     * @return int[]
     */
    private function getIdsFromCookie(): array
    {
        $cookie = request()->cookie(self::COOKIE_NAME);

        if (! is_string($cookie) || $cookie === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', explode(',', $cookie)),
            fn ($id) => $id > 0
        )));
    }

    /**
     * Internal: Save IDs to cookie.
     *
     * @param int[] $ids
     */
    private function saveIdsToCookie(array $ids): void
    {
        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));

        if (empty($ids)) {
            Cookie::queue(Cookie::forget(self::COOKIE_NAME));
            return;
        }

        Cookie::queue(
            self::COOKIE_NAME,
            implode(',', $ids),
            self::COOKIE_LIFETIME
        );
    }
}
