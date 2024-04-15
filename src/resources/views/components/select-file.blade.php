<?php

use Illuminate\Support\Facades\Log;

$typeField = $nr > 1 ? 'type' . $nr : 'type';
$fileField = $nr > 1 ? "file{$nr}" : 'file';
$fileIdKey = $nr > 1 ? "file{$nr}_id" : 'file_id';
$fileRelation = $fileField;
$hideFileSelectionClass = ''; 
$previousFileValue = old('tiles' . $nr . '.' . $key . '.file');

if(empty($tile->{$typeField}) && $nr > 1) {
    $hideFileSelectionClass = 'hidden';
}
?>

<div id="show_file{{ $key }}_{{ $nr }}" class="h-7 {{$hideFileSelectionClass}} flex">
    <div>
    {{ $nr }}:
    <input type="file" name="tiles[{{ $key }}][{{ $fileField }}]" class="custom-file-input" id="chooseFile" value="{{ $previousFileValue }}">
    </div>   
    @if($errors->has('tiles.' . $key . '.' . $fileField))
        <div class="error">The file upload failed.</div>
    @else
        <div>
        @if($tile->{$fileRelation} || !empty($tile->{$fileIdKey}))
            <?php 	        
            $fileid = (string) $tile->{$fileRelation}->id ?? null;
            $storedFileNumber = str_pad($tile->id, 3, '0', STR_PAD_LEFT); 
            ?>
            <audio controls style="width: 200px; height: 30px;">
                <source src="/languagepack/tiles/{{ $tile->languagepackid }}/download/tile_{{ $storedFileNumber }}_{{ $nr }}.mp3?{{ time() }}" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio> 								

            <input type="hidden" name="tiles[{{ $key }}][{{ $fileIdKey }}]" value="{{ $tile->$fileIdKey }}">
        @endif
        </div>
    @endif									
</div>