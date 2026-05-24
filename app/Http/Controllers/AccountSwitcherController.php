<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MultiAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountSwitcherController extends Controller
{
    public function __construct(private readonly MultiAccountService $multiAccount) {}

    /**
     * Seamlessly switch to a linked account.
     */
    public function switch(Request $request, int $userId): RedirectResponse
    {
        $guard = Auth::guard('web');

        if (! $guard->check()) {
            // Not logged in — send to Filament login
            return redirect('/admin/login');
        }

        if ($this->multiAccount->switchTo($userId)) {
            return redirect('/admin');
        }

        // switchTo failed (userId not in cookie, user not found, etc.)
        return redirect()->back()
            ->with('error', 'Не удалось переключить аккаунт.');
    }

    /**
     * Save current account + logout, then redirect to login to add another account.
     */
    public function addAccount(Request $request): RedirectResponse
    {
        $guard = Auth::guard('web');

        if (! $guard->check()) {
            return redirect('/admin/login');
        }

        // Save current user to the persistent cookie
        $this->multiAccount->addId($guard->id());

        $guard->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
