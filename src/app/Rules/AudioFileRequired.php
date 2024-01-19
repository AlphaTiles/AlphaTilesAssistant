<?php

namespace App\Rules;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AudioFileRequired implements Rule
{
    protected $attribute;
    protected $key;
    protected $request;
    protected $value;        
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

        if(is_array($value) && !empty($value['audioFile'])) {
            $this->key = 'audioFile';
            $value = $value['audioFile'];
        }
            
        if ($this->key != 'audioFile' && (!empty($value['audioFilename']) || !empty($value['delete']))) {
            return true;
        }

        if($this->key === 'audioFile')
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
        $wordKey = explode('.', $this->attribute)[1];
        $word = $this->words[$wordKey]['value'] ?? '';

        if(is_array($this->value)) {
            return [
                $this->attribute . '.audioFile' => "An audio file is required for {$word}. It must be an mp3 and the file size no bigger than 1024kb."
            ];
        }
    }
}