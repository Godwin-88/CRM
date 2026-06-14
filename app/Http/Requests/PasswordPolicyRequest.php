<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $minLength = max(10, config('security.password_min_length', 12));
        $user = $this->user();

        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:'.$minLength,
                Password::min($minLength)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.uncompromised' => 'This password has been found in a known data breach. Please choose a different password.',
        ];
    }

    protected function passedValidation(): void
    {
        $this->checkPasswordHistory($this->user(), $this->password);
    }

    private function checkPasswordHistory($user, string $password): void
    {
        $history = $user->passwordHistory()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($history as $entry) {
            if (password_verify($password, $entry->password_hash)) {
                abort(422, 'You cannot reuse any of your last 5 passwords.');
            }
        }
    }
}
