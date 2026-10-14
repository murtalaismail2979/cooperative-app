<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value) || $value === '') {
            return;
        }

        $formattedAttr = ucwords(str_replace('_', ' ', $attribute));

        if (!is_string($value) && !is_numeric($value)) {
            $fail("The {$formattedAttr} must be a valid phone number.");
            return;
        }

        $phone = trim((string) $value);

        // Check for disallowed characters.
        // Valid characters: digits, leading '+', spaces, hyphens, and balanced parentheses.
        if (!preg_match('/^[0-9+\s\-()]+$/', $phone)) {
            $fail("The {$formattedAttr} contains invalid characters. Please enter a valid phone number with digits (e.g. 08012345678 or +2348012345678).");
            return;
        }

        // Check '+' placement and count: only 1 '+' allowed, and must be at the beginning or after an opening parenthesis
        $plusCount = substr_count($phone, '+');
        if ($plusCount > 1) {
            $fail("The {$formattedAttr} cannot contain multiple '+' signs.");
            return;
        }
        if ($plusCount === 1) {
            $plusPos = strpos($phone, '+');
            if ($plusPos !== 0 && $plusPos !== 1) {
                $fail("The '+' symbol in {$formattedAttr} must be at the beginning of the number.");
                return;
            }
        }

        // Check parentheses balance
        if (substr_count($phone, '(') !== substr_count($phone, ')')) {
            $fail("The {$formattedAttr} has unmatched parentheses.");
            return;
        }

        // Count pure digits
        $digitsOnly = preg_replace('/[^0-9]/', '', $phone);
        $digitCount = strlen($digitsOnly);

        // Standard national and international phone numbers have between 7 and 15 digits (ITU-T E.164)
        if ($digitCount < 7 || $digitCount > 15) {
            $fail("The {$formattedAttr} must contain between 7 and 15 digits (e.g. 08012345678 or +2348012345678).");
            return;
        }
    }
}
