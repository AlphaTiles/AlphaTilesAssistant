<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ThreeLettersRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Check if the length of the input is exactly 3 characters
        return strlen($value) === 3;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be exactly 3 letters long.';
    }
}