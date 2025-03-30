<?php
use App\Enums\TileTypeEnum;
?>

<?php 
$typeField = $nr > 1 ? 'type' . $nr : 'type';
$errorClass = isset($errorKeys) && in_array('items{{ $nr }}.' . $key . '.type', $errorKeys) ? 'inputError' : ''; 
$hideTypeSelectionClass = '';
$hideAddLinkClass = $nr === 3 && empty($tile->type2) ? 'hidden' : '';
?>
@if(empty($tile->{$typeField}) && $nr > 1)
    <?php $hideTypeSelectionClass = 'hidden'; ?>
    <div id="add_type{{ $key }}_{{ $nr }}" class="{{$hideAddLinkClass}} text-xs"><a href="#" onclick="addType({{ $key }}, {{ $nr }})">Add type</a> <a href="#" onClick="openAlert('Additional types', 'In most cases, setup for multi-function symbols is not necessary. See this YouTube <a href=\'https://www.youtube.com/watch?v=s-HAUAc6tAg\' target=\'_blank\'>video</a> for more information.');"><i class="fa-solid fa-circle-info"></i></a></div>
@endif
<div id="show_type{{ $key }}_{{ $nr }}" class="{{$hideTypeSelectionClass}} h-7">
    {{ $nr }}:
    <select name="items[{{ $key }}][{{ $typeField }}]" class="{{ $errorClass }}">
        <option value=""></option>
    @foreach(TileTypeEnum::cases() as $optionKey => $typeEnum)
        <?php 
        $typeValue = old('items{{ $nr }}.' . $key . '.type') ?? $tile->{$typeField};
        $selected = $typeValue === $typeEnum->value ? 'selected' : ''; 
        ?>								
        <option value="{{ $typeEnum->value }}" {{ $selected }}>{{ $typeEnum->value }}: {{ $typeEnum->label() }}</option>
    @endforeach		
    </select>						
</div>