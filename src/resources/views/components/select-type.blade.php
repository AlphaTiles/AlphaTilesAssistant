<?php
use App\Enums\TileTypeEnum;
?>

<?php 
$typeField = $nr > 1 ? 'type' . $nr : 'type';
$errorClass = isset($errorKeys) && in_array('tiles{{ $nr }}.' . $key . '.type', $errorKeys) ? 'inputError' : ''; 
$hideTypeSelectionClass = '';
$hideAddLinkClass = $nr === 3 && empty($tile->type2) ? 'hidden' : '';
?>
@if(empty($tile->{$typeField}))
    <?php $hideTypeSelectionClass = 'hidden'; ?>
    <div id="add_type{{ $key }}_{{ $nr }}" class="{{$hideAddLinkClass}} text-xs"><a href="#" onclick="addType({{ $key }}, {{ $nr }})">Add type</a></div>
@endif
<div id="show_type{{ $key }}_{{ $nr }}" class="{{$hideTypeSelectionClass}}">
    {{ $nr }}:
    <select name="tiles[{{ $key }}][{{ $typeField }}]" class="{{ $errorClass }}">
        <option value=""></option>
    @foreach(TileTypeEnum::cases() as $optionKey => $typeEnum)
        <?php 
        $typeValue = old('tiles{{ $nr }}.' . $key . '.type') ?? $tile->{$typeField};
        $selected = $typeValue === $typeEnum->value ? 'selected' : ''; 
        ?>								
        <option value="{{ $typeEnum->value }}" {{ $selected }}>{{ $typeEnum->label() }}</option>
    @endforeach		
    </select>						
</div>