<?php

namespace App\Rules;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Rule;

class RequireAtLeastOneDistractor implements Rule
{
    protected $request;
    protected $tileName = "";

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function passes($attribute, $value)
    {
        if(!empty($value['delete'])) {
            return true;
        }

        $isValid = !empty($value['or_1']) && !empty($value['or_2']) && !empty($value['or_3']);

        if (!$isValid) {
            $this->tileName = $value['value'];
        }

        return $isValid;
    }

    public function message()
    {
        return "All 3 distractors are required for {$this->tileName}";
    }
}