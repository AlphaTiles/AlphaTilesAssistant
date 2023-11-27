<?php

namespace App\Rules;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ImageFileRequired implements Rule
{
    protected $attribute;
    protected $key;
    protected $request;
    protected $value;        

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function passes($attribute, $value)
    {
        $this->key = substr($attribute, -9);
        $this->attribute = $attribute;
        $this->value = $value;        

        if(is_array($value) && !empty($value['imageFile'])) {
            $this->key = 'imageFile';
            $value = $value['imageFile'];
        }
            
        if ($this->key != 'imageFile' && (!empty($value['imageFilename']) || !empty($value['delete']))) {
            return true;
        }

        if($this->key === 'imageFile')
        {
            if($value->getClientOriginalExtension() !== 'png') {
                return false;
            }

            $fileSizeInKB = $value->getSize() / 512;
            //if file is too big to be uploaded, it will return as size 0
            if($fileSizeInKB === 0 || $fileSizeInKB > 512) {
                return false;
            }    
            
            return true;
        }

        return false;
    }

    public function message()
    {
        if(is_array($this->value)) {
            return [
                $this->attribute . '.imageFile' => "An image file is required for {$this->value['value']}. It must be of the png type and the file size no bigger than 512kb."
            ];
        }
    }
}