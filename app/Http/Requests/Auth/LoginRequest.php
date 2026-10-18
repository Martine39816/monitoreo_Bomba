<?php

namespace App\Http\Requests\Auth;

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

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Autentica contra la columna 'correo' de la tabla 'usuarios'.
     * El campo del formulario sigue llamandose 'email' para no tener
     * que tocar las vistas Blade de Breeze.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // 'estado' => true impide que una cuenta desactivada pueda iniciar
        // sesion: el proveedor Eloquent lo agrega como filtro en la consulta,
        // por lo que si el usuario esta inactivo, el intento simplemente falla.
        $credentials = [
            'correo' => $this->input('email'),
            'estado' => true,
            'password' => $this->input('password'),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Registro de ultimo acceso (trazabilidad basica)
        Auth::user()->forceFill(['ultimo_acceso' => now()])->save();
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos de acceso. Por favor, inténtalo de nuevo en {$seconds} segundos.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
