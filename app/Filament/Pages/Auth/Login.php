<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Auth\Http\Responses\Contracts\LoginResponse; // ⬅️ INI YANG BENAR
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        // Kalau MFA / gagal login → null
        if (! $response) {
            return null;
        }

        if ($user = Auth::user()) {
            $user->forceFill([
                'last_login_at' => now(),
            ])->save();
        }

        return $response;
    }
}
