<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces the canonical E.164 PH mobile format: +639XXXXXXXXX.
 * The frontend normalizes user input (local 09XXXXXXXXX or +639XXXXXXXXX)
 * to this shape before submit, so this rule intentionally rejects anything else.
 */
class PhilippineMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match('/^\+639\d{9}$/', $value)) {
            $fail('The :attribute must be a valid PH mobile number in the format +639XXXXXXXXX.');
        }
    }
}
