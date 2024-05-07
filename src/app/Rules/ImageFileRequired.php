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
    protected $file;    
    protected $validImageDimensionInKb = 512;        
    protected $words;

    public function __construct(Request $request, ?array $words = null)
    {
        $this->request = $request;
        $this->words = $words;
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

        $this->file = $value;

        if ($this->key != 'imageFile' && (!empty($value['imageFilename']) || !empty($value['delete']))) {
            return true;
        }

        if($this->key === 'imageFile')
        {
            if($value->getClientOriginalExtension() !== 'png') {
                return false;
            }

            $fileSizeInKB = $value->getSize() / 256;
            //if file is too big to be uploaded, it will return as size 0
            if($fileSizeInKB === 0 || $fileSizeInKB > 256) {
                return false;
            }    

            if(!$this->isValidDimensions()) {
                return false;
            }
            
            return true;
        }

        //not required
        return true;
    }

    public function message()
    {
        $errorMessage = "";

        $wordKey = explode('.', $this->attribute)[1];
        $word = $this->words[$wordKey]['value'] ?? '';

        if(is_array($this->value)) {
            $errorMessage = "An image file is required for {$word}. It must be of the png type.";    
        }

        if(!is_array($this->file)) {
            $fileSizeInKB = $this->file->getSize() / 256;
            if($this->file->getClientOriginalExtension() !== 'png') {
                $errorMessage = "The image file for {$word} must be of the png type.";
            } elseif($fileSizeInKB === 0 || $fileSizeInKB > 256) {
                $errorMessage = "The file size must be no bigger than 256kb.";
            }
            elseif(!$this->isValidDimensions($this->value)) {
                $errorMessage = "The dimensions of the image file have to be {$this->validImageDimensionInKb}x{$this->validImageDimensionInKb}px.";
            }    
        }

        return [
            $this->attribute . '.imageFile' =>  $errorMessage
        ];                        
    }

    protected function isValidDimensions(): bool
    {
        if($this->file->getClientOriginalExtension() !== 'png') {
            return false;
        }

        if(getimagesize($this->file)[0] !== $this->validImageDimensionInKb && getimagesize($this->file)[1] !== $this->validImageDimensionInKb) {
            return false;
        }

        return true;
    }
}