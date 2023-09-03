<?php
use App\Enums\TileTypeEnum;
?>

<div>
    <?php $errorClass = isset($errorKeys) && in_array('tiles.' . $key . '.type', $errorKeys) ? 'inputError' : ''; ?>
    <select name="tiles[{{ $key }}][type]" class="{{ $errorClass }}">
        <option value=""></option>
    @foreach(TileTypeEnum::cases() as $optionKey => $typeEnum)
        <?php 
        $typeValue = old('tiles.' . $key . '.type') ?? $tile->type;
        $selected = $typeValue === $typeEnum->value ? 'selected' : ''; 
        ?>								
        <option value="{{ $typeEnum->value }}" {{ $selected }}>{{ $typeEnum->label() }}</option>
    @endforeach								
</div>