<?php
use App\Enums\TabEnum;
use Illuminate\Support\Arr;
$tabEnum = TabEnum::TILE;
?>

@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Tiles</h1>
	
	<div>
		<div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
			@if (session()->has('success'))
			<div class="p-3 text-green-700 bg-green-300 rounded">
				{{ session()->get('success') }}
			</div>
			@endif
		</div>	
		<?php 
		$itemsData = old('items') ?? request()['items'] ?? $items;
		$deleteValues = old('items') ? Arr::pluck(old('items') , 'delete') : Arr::pluck($itemsData , 'delete'); 
		$deleteValues = array_pad($deleteValues, count($items), null);
		?>
		@if($items && in_array(1, $deleteValues))
		<form method="post" action="{{ route('delete-tiles', $languagePack->id) . '?' . http_build_query(request()->query()) }}" enctype="multipart/form-data">			
		@csrf
		@method('DELETE')
		<div class="alert mb-3">  				
			<div class="block p-2">
				<h3 class="mt-0">Are you sure want to delete the following items?</h3>
				<?php $tileDeleteIds = []; ?>
				@foreach ($items as $key => $tile)					
					@if(isset($deleteValues[$key]))
						<?php array_push($tileDeleteIds, $tile->id); ?>
						<div>{{ $tile->value }}</div>
					@endif
				@endforeach					
				<div class="mt-2">
					<input type="hidden" name="deleteIds" value="{{ implode(',', $tileDeleteIds); }}" />
					<button name="btnCancel" value="cancel" class="btn btn-sm">Cancel</button>
					<button name="btnDelete" value="delete" class="btn btn-sm btn-primary">Yes</button>
				</div>
			</div>
		</div>	
		</form>						
		@endif

	@if(!empty($validationErrors))
	<x-validation-errors
		:languagePack="$languagePack"
		:errors=$validationErrors
		:tab="$tabEnum"
	/>	
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

	<form method="post" action="{{ route('update-tiles', $languagePack->id) . '?' . http_build_query(request()->query()) }}" enctype="multipart/form-data">			
	@csrf
	@method('PATCH')
	@if(count($items) > 0)
		<div>
			<table class="table table-compact w-full">
				<colgroup>
					<col span="1" style="width: 5%;">
					<col span="1" style="width: 5%;">
					<col span="1" style="width: 15%;">
					<col span="1" style="width: 15%;">
					<col span="1" style="width: 35%;">
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 20%;">
				</colgroup>                        
				<thead>
				<tr>
					<th>Tile</th> 
					<th>Uppercase</th> 
					<th>Type</th>
					<th>Distractors <a href="#" onClick="openAlert('Distractors', 'The three columns (Or1, Or2, Or3) contain “distractors”. They are used to provide alternative (incorrect) answers. For example, in the word-builder game to the right, the player has compared the two purple items and has correctly selected |r| and not |i|. The game tile |i| appears as an option because in the gameitems tab, the letter “i” is listed to the right of the row for the letter “r”. You should only select distractors from the items found in the first column of the gameitems tab.', '/images/help/distractors.png');"><i class="fa-solid fa-circle-info"></i></a></th>                             
					<th>Audio instructions</th>
					<th>Stage <a href="#" onClick="openAlert('First Stage', 'Define in which stage the tile should first appear');"><i class="fa-solid fa-circle-info"></i></a></th>
					<th><input type="checkbox" onClick="checkAll(this, 'items')" /> Delete</th>
				</tr>
				</thead> 
				<tbody>
				@foreach($items as $key => $tile)
				<tr>
					<td>
					<input type="hidden" name="items[{{ $key }}][languagepackid]" value="{{ $tile->languagepackid }}">
						<input type="hidden" name="items[{{ $key }}][id]" value="{{ $tile->id }}" />
						<input type="hidden" name="items[{{ $key }}][value]" value="{{ $tile->value }}" />
						<a href="#" onClick="showTileInfo({{ $languagePack->id }}, '{{ $tile->value }}');">{{ $tile->value }}</a>
					</td> 
					<td>								
						<input type="text" size=2 name="items[{{ $key }}][upper]" value="{{ $tile->upper }}" />
					</td> 
					<td>						
						<div class="h-7">
						<x-select-type 
							:nr="1"
							:key=$key
							:tile=$tile
							:error-keys="$errorKeys ?? null"
						/>
						</div>
						<div class="mt-1">
						<x-select-type 
							:nr="2"
							:key=$key
							:tile=$tile
							:error-keys="$errorKeys ?? null"
						/>
						</div>
						<div class="mt-1">
						<x-select-type 
							:nr="3"
							:key=$key
							:tile=$tile
							:error-keys="$errorKeys ?? null"
						/>
						</div>
					</td> 
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.or_1', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="items[{{ $key }}][or_1]" value="{{ old('items.' . $key . '.or_1') ?? $tile->or_1 }}" class="{{ $errorClass }}" />
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.or_2', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="items[{{ $key }}][or_2]" value="{{ old('items.' . $key . '.or_2') ?? $tile->or_2 }}" class="{{ $errorClass }}" />
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.or_3', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="items[{{ $key }}][or_3]" value="{{ old('items.' . $key . '.or_3') ?? $tile->or_3 }}" class="{{ $errorClass }}" />
					</td> 
					<td>
						<div class="custom-file h-7">
							<x-select-file
							:nr="1"
							:key=$key
							:prefix="'tile'"
							:item=$tile
							:error-keys="$errorKeys ?? null"
							/>
						</div>
						<div class="mt-1 custom-file">						
							<x-select-file
							:nr="2"
							:key=$key
							:prefix="'tile'"
							:item=$tile
							:error-keys="$errorKeys ?? null"
							/>
						</div>
						<div class="mt-1 custom-file">						
							<x-select-file
							:nr="3"
							:key=$key
							:prefix="'tile'"
							:item=$tile
							:error-keys="$errorKeys ?? null"
							/>
						</div>
					</td> 
					<td>								
						<div class="h-7">1: <input type="number" min="0" size=3 name="items[{{ $key }}][stage]" value="{{ $tile->stage }}" /></div>
						<div id="stage{{ $key }}_2" class="{{ empty($tile->type2) ? 'hidden' : '' }}"><div class='h-7'>2: <input type="number" min="0" size=3 name="items[{{ $key }}][stage2]" value="{{ $tile->stage2 }}" /></div></div>
						<div id="stage{{ $key }}_3" class="{{ empty($tile->type3) ? 'hidden' : '' }}"><div class='h-7'>3: <input type="number" min="0" size=3 name="items[{{ $key }}][stage3]" value="{{ $tile->stage3 }}" /></div></div>
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

		<div>
			{!! $pagination !!}
		</div>

		<p>
			<input type="submit" name="btnHiddenSave" id="saveButton" value="Save" class="hidden" />
			<input type="submit" name="btnSave" value="Save" class="btn-sm btn-primary ml-1" onClick='handleSaveReset();' />
		</p>			
	@endif

		</div>
	</form>

	<form method="post" action="/languagepack/tiles/{{ $languagePack->id }}">
		@csrf
		<div>
			<label for="add_items">Add items (one tile per line):</label><br>
			<textarea name="add_items" rows=7 cols=40 class="leading-tight">{{ old('add_items') }}</textarea>
		</div>

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePack->id }}" />
			<input type="submit" name="btnAdd" value="Add items" class="btn-sm btn-primary ml-1" />
		</div>
	</form>
	<div class="mt-6 w-9/12">	
		<a href="#" onClick='autoSavePage("/languagepack/edit/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-secondary pt-0.5 font-normal">Back</a>
		@if($languagePack->tiles->count() > 0)
			<a href="#" onClick='autoSavePage("/languagepack/wordlist/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">Next</a>		
		@endif
	</div>

	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection

@section('scripts')
<script>	
function addType(key, nr) {
	var typeId = 'type' + key + '_' + nr;
	var addLink = document.getElementById('add_' + typeId);
	addLink.style.display = 'none';
	var showType = document.getElementById('show_' + typeId);
	showType.classList.remove('hidden');

	var fileId = 'file' + key + '_' + nr;
	var showFile = document.getElementById('show_' + fileId);
	showFile.classList.remove('hidden');

	var stageId = 'stage' + key + '_' + nr;
	var showStage = document.getElementById(stageId);
	showStage.classList.remove('hidden');

	if(nr === 2) {
		var addSecondLink = document.getElementById('add_type' + key + '_3');
		addSecondLink.classList.remove('hidden');
	}

	event.preventDefault();
}

function showTileInfo(languagePackId, tile) {

	fetch(`/api/tiles/words/${languagePackId}/${tile}`)
		.then(response => response.json())
		.then(words => {
			const wordList = words.map(word => `<li>${word}</li>`).join('');			
			Swal.fire({
				title: 'Words in which tile is used',
				html: `<ul>${wordList}</ul>`,
				confirmButtonColor: 'blue',
				confirmButtonText: 'Close'
			});
		})
		.catch(error => {
			console.error('Error fetching words:', error);
			Swal.fire({
				title: 'Error',
				text: 'Could not fetch words for the selected tile.',
				confirmButtonColor: 'blue',
				confirmButtonText: 'Close'
			});
		});
}

</script>
@endsection