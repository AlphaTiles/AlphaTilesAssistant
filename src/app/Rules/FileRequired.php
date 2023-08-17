<?php

namespace App\Rules;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class FileRequired implements Rule
{
    protected $attribute;
    protected $request;
    protected $value;        

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function passes($attribute, $value)
    {
        if (substr($attribute, -4) != 'file' && (!empty($value['filename']) || !empty($value['delete']))) {
            return true;
        }

        $this->attribute = $attribute;
        $this->value = $value;        

        if(substr($attribute, -4) === 'file')
        {
            if($value->getClientOriginalExtension() !== 'mp3') {
                return false;
            }

            $fileSizeInKB = $value->getSize() / 1024;
            //if file is too big to be uploaded, it will return as size 0
            if($fileSizeInKB === 0 || $fileSizeInKB > 1024) {
                return false;
            }    
            
            return true;
        }

        return false;
    }

    public function message()
    {
        if(substr($this->attribute, -4) != 'file' && (empty($this->value['file']) || empty($this->value['filename']))) {
            return [
                $this->attribute . '.file' => "An audio file is required for {$this->value['value']}. It must be an mp3 and the file size no bigger than 1024kb."
            ];
        }
    }
}