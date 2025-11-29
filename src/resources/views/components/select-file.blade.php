<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

$typeField = $nr > 1 ? 'type' . $nr : 'type';
$fileField = $nr > 1 ? "file{$nr}" : 'file';
$fileIdKey = $nr > 1 ? "file{$nr}_id" : 'file_id';
$fileRelation = $fileField;
$hideFileSelectionClass = ''; 
$previousFileValue = old('items' . $nr . '.' . $key . '.file');

if(empty($item->{$typeField}) && $nr > 1) {
    $hideFileSelectionClass = 'hidden';
}
?>

<div id="show_file{{ $key }}_{{ $nr }}" class="h-7 {{$hideFileSelectionClass}} flex">
    <div>
    {{ $nr }}:
    <input type="file" name="items[{{ $key }}][{{ $fileField }}]" class="custom-file-input" id="chooseFile" value="{{ $previousFileValue }}">
    </div>   
    @if($errors->has('items.' . $key . '.' . $fileField))
        <div class="error">The file upload failed.</div>
    @else
        <div>
        @if($item->{$fileRelation} || !empty($item->{$fileIdKey}))
            <?php 	        
            $fileid = (string) $item->{$fileRelation}->id ?? null;
            $storedFileNumber = str_pad($item->id, 3, '0', STR_PAD_LEFT); 
            $extension = File::extension($item->{$fileRelation}->file_path);            
            ?>
            @if($extension === 'mp3')
                <audio controls style="width: 200px; height: 30px; margin-top: 5px;">
                    <source src="/languagepack/items/{{ $item->languagepackid }}/download/{{ $prefix }}_{{ $storedFileNumber }}_{{ $nr }}.mp3?{{ time() }}" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio> 								
            @else
                <img width="30" src="/languagepack/items/{{ $item->languagepackid }}/download/{{ $prefix }}_{{ $storedFileNumber }}_{{ $nr }}.png?{{ time() }}" />
            @endif
            <input type="hidden" name="items[{{ $key }}][{{ $fileIdKey }}]" value="{{ $item->$fileIdKey }}">
        @endif
        </div>
    @endif									
</div>