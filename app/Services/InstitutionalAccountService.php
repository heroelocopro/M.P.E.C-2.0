<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InstitutionalAccountService
{
    /** @return array{email: string, password: string} */
    public function provision(User $user, string $personalEmail): array
    {
        $password = Str::password(16);
        $institutionalEmail = $this->uniqueEmail($user->name);
        $personalEmail = Str::lower(trim($personalEmail));

        $user->forceFill([
            'email' => $institutionalEmail,
            'personal_email' => $personalEmail,
            'password' => $password,
            'email_verified_at' => now(),
        ])->save();

        Mail::raw(
            'Tu cuenta institucional de '.config('app.name')." fue creada.\n\nCorreo institucional: {$institutionalEmail}\nContraseña temporal: {$password}\n\nUsa estas credenciales para ingresar y conserva este mensaje en un lugar seguro.",
            fn ($mail) => $mail->to($personalEmail)->subject('Tu cuenta institucional de '.config('app.name')),
        );

        return ['email' => $institutionalEmail, 'password' => $password];
    }

    private function uniqueEmail(string $name): string
    {
        $parts = preg_split('/\s+/', trim(Str::lower(Str::ascii($name)))) ?: [];
        $letters = collect($parts)->filter()->map(fn (string $part): string => substr($part, 0, 1))->join('');
        $base = $letters !== '' ? $letters : 'usuario';
        $domain = (string) config('app.institution_domain');
        $email = "{$base}@{$domain}";
        $suffix = 2;

        while (User::query()->where('email', $email)->exists()) {
            $email = "{$base}{$suffix}@{$domain}";
            $suffix++;
        }

        return $email;
    }
}
