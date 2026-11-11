<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $correo = (string) $this->input('email');
        $usuario = User::where('correo', $correo)->first();

        // Se rechaza la cuenta inactiva antes de crear una sesión.
        if ($usuario && ! $usuario->estado) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta se encuentra desactivada. Comunícate con el administrador.'],
            ]);
        }

        $credentials = [
            'correo' => $correo,
            'password' => $this->input('password'),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Segunda comprobación por seguridad, por si el estado cambió durante el login.
        if (! Auth::user()->estado) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta se encuentra desactivada. Comunícate con el administrador.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Auth::user()->forceFill(['ultimo_acceso' => now()])->save();
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
