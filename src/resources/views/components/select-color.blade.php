<?php 

use App\Enums\ColorEnum;
$jsColorArray = generateJavaScriptColorArray(ColorEnum::class);
?>
<select name="items[{{ $key }}][color]" id=selectColor{{ $key }} onChange='changeColor(this);'>
    <option value=""></option>
@foreach(ColorEnum::cases() as $optionKey => $colorEnum)
    <?php 
    $colorNr = old('items.' . $key . '.color') ?? $color;
    if(isset($colorNr)) {
        $colorNr = (int) $colorNr;
    }
    $selected =  $colorNr === $colorEnum->value ? 'selected' : ''; 
    ?>								
    <option value="{{ $colorEnum->value }}" {{ $selected }}>{{ $colorEnum->label() }}</option>
@endforeach		
</select>
<?php 
$hexCode = '#FFFFFF';
$hidden = 'hidden';
if(isset($colorNr)) {
    $hexCode = ColorEnum::from($colorNr)->hexCode();
    $hidden = '';
}
?>
<div id=displayColor{{ $key }} class="{{ $hidden }} inline-block align-bottom ml-3 p-4 rounded-lg shadow-md" style="width:5px; height:5px; background-color:{{ $hexCode }};"></div>						

@section('scripts')
<script>	
function changeColor(selectElement) {
	var selectedValue = selectElement.value;    
	let displayColorId = selectElement.id.replace('selectColor', 'displayColor');
	let displayColorBox = document.getElementById(displayColorId);

	if(selectedValue.length === 0 && !displayColorBox.classList.contains("hidden")) {
		displayColorBox.classList.add('hidden');
		return;
	}

	const colorArray = <?php echo json_encode($jsColorArray, JSON_PRETTY_PRINT); ?>;
	displayColorBox.style.backgroundColor = colorArray[selectedValue];
	displayColorBox.classList.remove('hidden');
}
</script>
@endsection