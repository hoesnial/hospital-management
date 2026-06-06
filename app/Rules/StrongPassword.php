<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || strlen($value) < 12) {
            $fail('The password must be at least 12 characters.');
            return;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $fail('The password must contain at least one uppercase letter.');
            return;
        }

        if (!preg_match('/[a-z]/', $value)) {
            $fail('The password must contain at least one lowercase letter.');
            return;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $fail('The password must contain at least one number.');
            return;
        }

        if (!preg_match('/[!@#$%^&*()\-_=+{};:,.<>?~`\[\]\\\\\/|]/', $value)) {
            $fail('The password must contain at least one special character.');
        }
    }
}
