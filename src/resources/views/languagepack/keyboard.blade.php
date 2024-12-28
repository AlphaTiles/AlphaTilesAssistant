<?php
use App\Enums\ColorEnum;
use Illuminate\Support\Arr;
?>

@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Keyboard</h1>
	
	<div>
		<div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
			@if (session()->has('success'))
			<div class="p-3 text-green-700 bg-green-300 rounded">
				{{ session()->get('success') }}
			</div>
			@endif
		</div>	
		<?php 
		$keyData = old('items') ?? request()['items'] ?? $keys;
		$deleteValues = old('items') ? Arr::pluck(old('items') , 'delete') : Arr::pluck($keyData , 'delete'); 
		?>
		@if($keys && in_array(1, $deleteValues))
		<form method="post" action="/languagepack/keyboard/{{ $languagePack->id }}" enctype="multipart/form-data">			
		@csrf
		@method('DELETE')
		<div class="alert mb-3">  				
			<div class="block p-2">
				<h3 class="mt-0">Are you sure want to delete the following keys?</h3>
				<?php $keyDeleteIds = []; ?>
				@foreach ($keys as $key => $keyItem)					
					@if(isset($deleteValues[$key]))
						<?php array_push($keyDeleteIds, $keyItem->id); ?>
						<div>{{ $keyItem->value }}</div>
					@endif
				@endforeach									
				<div class="mt-2">
					<input type="hidden" name="deleteIds" value="{{ implode(',', $keyDeleteIds); }}" />
					<button name="btnCancel" value="cancel" class="btn btn-sm">Cancel</button>
					<button name="btnDelete" value="delete" class="btn btn-sm btn-primary">Yes</button>
				</div>
			</div>
		</div>	
		</form>						
		@endif

	@if ($errors->any())
	<div class="alert alert-error">
		<ul class="block">
			<?php 
			$errorKeys = $errors->keys();
			$errorsUnique = array_unique($errors->all());
			?>
			@foreach ($errorsUnique as $error)
				<li class="block">{{ $error }}</li>
			@endforeach
		</ul>
	</div>
	@endif

	<form method="post" action="/languagepack/keyboard/{{ $languagePack->id }}" enctype="multipart/form-data">			
	@csrf
	@method('PATCH')
	@if(count($keys) > 0)
		<div>
			<table class="table table-compact w-auto">
				<colgroup>
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 10%;">
				</colgroup>                        
				<thead>
				<tr>
					<th>Key</th> 
					<th>Color <a href="#" onClick="openAlert('Keyboard colors', 'Group keys by color, e.g. consonants vs vocals.')"><i class="fa-solid fa-circle-info"></i></a></th> 
					<th><input type="checkbox" onClick="checkAll(this, 'items')" /> Delete</th>
				</tr>
				</thead> 
				<tbody>
				@foreach($keys as $key => $keyItem)
				<tr>
					<td>
					<input type="hidden" name="items[{{ $key }}][languagepackid]" value="{{ $keyItem->languagepackid }}">
						<input type="hidden" name="items[{{ $key }}][id]" value="{{ $keyItem->id }}" />
						<input type="text" size=2 name="items[{{ $key }}][value]" value="{{ $keyItem->value }}" />
					</td> 
					<td>								
						<select name="items[{{ $key }}][color]" id=selectColor{{ $key }} onChange='changeColor(this);'>
							<option value=""></option>
						@foreach(ColorEnum::cases() as $optionKey => $colorEnum)
							<?php 
							$colorNr = old('items.' . $key . '.color') ?? $keyItem->color;
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
					</td> 
					<td>
						<?php $delete = $deleteValues[$key] === '1'; ?>
						<input type="checkbox" name="items[{{ $key }}][delete]" value="1" 
							{{  $delete ? 'checked' : '' }} />
					</td>
				</tr>
				@endforeach
			</table>                    
		</div>

		<p>
			<input type="submit" name="btnHiddenSave" id="saveButton" value="Save" class="hidden" />
			<input type="submit" name="btnSave" value="Save" class="btn-sm btn-primary ml-1" onClick='handleSaveReset();' />
		</p>			
	@endif

		</div>
	</form>

	<div class="mt-6 w-9/12">	
		<form method="post" action="/languagepack/keyboard/{{ $languagePack->id }}">
			@csrf
			<div>
				<label for="add_keys">Add keys (one key per line):</label> <a href="#" onClick="openAlert('Keyboard instructions', 'The order in which the keys are listed in the keyboard tab will be the order in which the keyboard is created in the game.<br><br>If your word includes a dash, you must add it. If your words contain spaces, you must add it as an underscore.')"><i class="fa-solid fa-circle-info"></i></a><br>
				<textarea name="add_keys" rows=10 cols=15 class="leading-tight">{{ $defaultKeys }}</textarea>
			</div>

			<div class="mt-3 w-9/12">		
				<input type="hidden" name="id" value="{{ $languagePack->id }}" />
				<input type="submit" name="btnAdd" value="Add keys" class="btn-sm btn-primary ml-1" />
			</div>
		</form>
		<div class="mt-6 w-9/12">	
			<a href="#" onClick='autoSavePage("/languagepack/wordlist/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-secondary pt-0.5 font-normal">Back</a>
			@if($languagePack->keys->count() > 0)
				<a href="#" onClick='autoSavePage("/languagepack/syllables/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">Next</a>		
			@endif
		</div>
	</div>

	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

<?php
$jsColorArray = generateJavaScriptColorArray(ColorEnum::class);
?>

@endsection

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