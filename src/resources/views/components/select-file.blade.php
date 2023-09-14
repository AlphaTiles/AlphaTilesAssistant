<?php

use Illuminate\Support\Facades\Log;

$typeField = $nr > 1 ? 'type' . $nr : 'type';
$fileField = $nr > 1 ? "file{$nr}" : 'file';
$filenameKey = $nr > 1 ? "filename{$nr}" : 'filename';
$fileRelation = $fileField;
$hideFileSelectionClass = ''; 
$previousFileValue = old('tiles' . $nr . '.' . $key . '.file');

if(empty($tile->{$typeField})) {
    $hideFileSelectionClass = 'hidden';
}
?>

<div id="show_file{{ $key }}_{{ $nr }}" class="{{$hideFileSelectionClass}}">
    {{ $nr }}:
    <input type="file" name="tiles[{{ $key }}][{{ $fileField }}]" class="custom-file-input" id="chooseFile" value="{{ $previousFileValue }}">
    @if(isset($tile->{$fileRelation}) || isset($tile->{$filenameKey}))
        <?php 	        
        $filename = $tile->{$fileRelation}->name ?? $tile->{$filenameKey};
        $storedFileNumber = str_pad($tile->id, 3, '0', STR_PAD_LEFT); 
        ?>
        <a href="/languagepack/tiles/{{ $tile->languagepackid }}/download/tile_{{ $storedFileNumber }}.mp3">
            {{ mb_strlen($filename) > 30 ? mb_substr($filename, 0, 30) . '...' : $filename }}
        </a>
        <input type="hidden" name="tiles[{{ $key }}][{{ $filenameKey }}]" value="{{ $filename }}">
    @endif
    @if($errors->has('tiles.' . $key . '.' . $fileField))
        <div class="error">The file upload failed.</div>
    @endif									
</div>