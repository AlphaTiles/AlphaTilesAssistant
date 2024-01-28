<?php

use Illuminate\Support\Facades\Log;

$typeField = $nr > 1 ? 'type' . $nr : 'type';
$fileField = $nr > 1 ? "file{$nr}" : 'file';
$filenameKey = $nr > 1 ? "filename{$nr}" : 'filename';
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
    <div>
    @if(isset($tile->{$fileRelation}) || isset($tile->{$filenameKey}))
        <?php 	        
        $filename = $tile->{$fileRelation}->name ?? $tile->{$filenameKey};
        $storedFileNumber = str_pad($tile->id, 3, '0', STR_PAD_LEFT); 
        ?>
        <audio controls style="width: 200px; height: 30px;">
            <source src="/languagepack/tiles/{{ $tile->languagepackid }}/download/tile_{{ $storedFileNumber }}_{{ $nr }}.mp3?{{ time() }}" type="audio/mpeg">
            Your browser does not support the audio element.
        </audio> 								

        <input type="hidden" name="tiles[{{ $key }}][{{ $filenameKey }}]" value="{{ $filename }}">
    @endif
    </div>
    @if($errors->has('tiles.' . $key . '.' . $fileField))
        <div class="error">The file upload failed.</div>
    @endif									
</div>