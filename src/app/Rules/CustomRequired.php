<?php

namespace App\Rules;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CustomRequired implements Rule
{
    protected $key;
    protected $request;
    protected $value;    

    public function __construct(Request $request, string $key)
    {
        $this->request = $request;
        $this->key  = $key;
    }

    public function passes($attribute, $value)
    {
        if(!empty($value['delete'])) {
            return true;
        }

        $isValid = true;
        $isValid = !empty($value[$this->key]);

        if (!$isValid) {
            $this->value = $value;
        }

        return $isValid;
    }

    public function message()
    {
        if(empty($this->value[$this->key])) {
            if($this->key === 'value') {
                return "A value for word is required";
            }
        }
    }
}