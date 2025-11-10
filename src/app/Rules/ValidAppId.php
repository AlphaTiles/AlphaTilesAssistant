<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\LanguageSetting;

class ValidAppId implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  \Closure(string): void  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // try to resolve languagepack id from route or input
        $languagePackId = null;
        $routeLp = request()->route('languagePack');
        if ($routeLp) {
            $languagePackId = is_object($routeLp) ? $routeLp->id : $routeLp;
        }
        if (!$languagePackId) {
            $languagePackId = request()->input('id');
        }

        if (!$languagePackId) {
            // cannot validate without a language pack context
            return;
        }

        $langSetting = LanguageSetting::where('languagepackid', $languagePackId)
            ->where('name', 'ethnologue_code')
            ->first();

        if (!$langSetting || empty($langSetting->value)) {
            // no ethnologue code available, don't fail validation here
            return;
        }

        $eth = strtolower(substr((string) $langSetting->value, 0, 3));
        $valueStr = (string) $value;
        $first3 = strtolower(substr($valueStr, 0, 3));

        if ($eth !== $first3) {
            $fail("The first three letters of the App ID must match the ethnologue code ({$eth}).");
            return;
        }

        // ensure the rest of the app id follows a valid Java identifier format (language description)
        $rest = substr($valueStr, 3);
        if ($rest === '' ) {
            $fail('The App ID must include a language description after the ethnologue code.');
            return;
        }

        // Java identifier-like check: must not start with a digit and contain only letters, digits or underscore
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $rest)) {
            $fail('The language description part of the App ID must be a valid Java identifier (letters, digits, underscore, not starting with a digit).');
            return;
        }
    }
}
