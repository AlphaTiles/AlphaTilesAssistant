<?php

namespace App\Rules;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CustomRequired implements Rule
{
    protected $request;
    protected $value;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function passes($attribute, $value)
    {
        if(!empty($value['delete'])) {
            return true;
        }

        $isValid = true;
        $isValid = !empty($value['type']);

        if (!$isValid) {
            $this->value = $value;
        }

        return $isValid;
    }

    public function message()
    {
        if(empty($this->value['type'])) {
            return "The type is required for {$this->value['value']}";
        }
    }
}