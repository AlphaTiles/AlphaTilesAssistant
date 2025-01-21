<?php
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
?>

@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Syllables</h1>
	<div>Adding syllables is optional. You can make the app without syllable-based games.</div>

	<div>
		<div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
			@if (session()->has('success'))
			<div class="p-3 text-green-700 bg-green-300 rounded">
				{{ session()->get('success') }}
			</div>
			@endif
		</div>	
		<?php 
		$syllablesData = old('items') ?? request()['items'] ?? $syllables;
		$deleteValues = old('items') ? Arr::pluck(old('items') , 'delete') : Arr::pluck($syllablesData , 'delete'); 
		?>
		@if($syllables && in_array(1, $deleteValues))
		<form method="post" action="/languagepack/syllables/{{ $languagePack->id }}" enctype="multipart/form-data">			
		@csrf
		@method('DELETE')
		<div class="alert mb-3">  				
			<div class="block p-2">
				<h3 class="mt-0">Are you sure want to delete the following syllables?</h3>
				<?php $syllableDeleteIds = []; ?>
				@foreach ($syllables as $key => $syllable)					
					@if(isset($deleteValues[$key]))
						<?php array_push($syllableDeleteIds, $syllable->id); ?>
						<div>{{ $syllable->value }}</div>
					@endif
				@endforeach					
				<div class="mt-2">
					<input type="hidden" name="deleteIds" value="{{ implode(',', $syllableDeleteIds); }}" />
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

	<form method="post" action="/languagepack/syllables/{{ $languagePack->id }}" enctype="multipart/form-data">			
	@csrf
	@method('PATCH')
	@if(count($syllables) > 0)
		<div>
			<table class="table table-compact w-full">
				<colgroup>
					<col span="1" style="width: 5%;">
					<col span="1" style="width: 5%;">
					<col span="1" style="width: 5%;">
					<col span="1" style="width: 5%;">
					<col span="1" style="width: 35%;">
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 20%;">
				</colgroup>                        
				<thead>
				<tr>
					<th>Syllable</th> 
					<th>Distractors <a href="#" onClick="openAlert('Distractors', 'The three columns (Or1, Or2, Or3) contain “distractors”. They are used to provide alternative (incorrect) answers. For example, in the word-builder game to the right, the player has compared the syllables in the first column and has correctly selected |chá| below and not |ché| above. The syllable |ché| appears as an option because, in the syllables tab, “ché” is listed as a distractor of “chá”. For distractor syllables, only use syllables that appear in column A as well.', '/images/help/distractor_syllables.png');"><i class="fa-solid fa-circle-info"></i></a></th> 
					<th>Audio instructions</th>
					<th>Color <a href="#" onClick="openAlert('Colors', 'The “Color” column indicates which color from your colors tab will appear as the background for that syllable in the Sudan game. This may help you to visually group syllables based on similarity in the Sudan game.')"><i class="fa-solid fa-circle-info"></i></a></th>
					<th><input type="checkbox" onClick="checkAll(this, 'items')" /> Delete</th>
				</tr>
				</thead> 
				<tbody>
				@foreach($syllables as $key => $syllable)
				<tr>
					<td>
					<input type="hidden" name="items[{{ $key }}][languagepackid]" value="{{ $syllable->languagepackid }}">
						<input type="hidden" name="items[{{ $key }}][id]" value="{{ $syllable->id }}" />
						<input type="hidden" name="items[{{ $key }}][value]" value="{{ $syllable->value }}" />
						{{ $syllable->value }}
					</td> 					
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.or_1', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="items[{{ $key }}][or_1]" value="{{ old('items.' . $key . '.or_1') ?? $syllable->or_1 }}" class="{{ $errorClass }}" />

						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.or_2', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="items[{{ $key }}][or_2]" value="{{ old('items.' . $key . '.or_2') ?? $syllable->or_2 }}" class="{{ $errorClass }}" />

						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.or_3', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="items[{{ $key }}][or_3]" value="{{ old('items.' . $key . '.or_3') ?? $syllable->or_3 }}" class="{{ $errorClass }}" />
					</td> 
					<td>
						<div class="custom-file h-7">
							<x-select-file
								:nr="1"
								:key=$key
								:prefix="'syllable'"
								:item=$syllable
								:error-keys="$errorKeys ?? null"
								/>
						</div>						
					</td> 
					<td>			
						<x-select-color 
							:key=$key
							:color="$syllable->color"
							:error-keys="$errorKeys ?? null"						
						/>										
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

	<form method="post" action="/languagepack/syllables/{{ $languagePack->id }}">
		@csrf
		<div>
			<label for="add_syllables">Add syllables (one syllable per line):</label><br>

			<textarea name="add_syllables" rows=7 cols=40 class="leading-tight"></textarea>
		</div>

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePack->id }}" />
			<input type="submit" name="btnAdd" value="Add syllables" class="btn-sm btn-primary ml-1" />
		</div>
	</form>
	<div class="mt-6 w-9/12">	
		<a href="#" onClick='autoSavePage("/languagepack/keyboard/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-secondary pt-0.5 font-normal">Back</a>
		<a href="#" onClick='autoSavePage("/languagepack/resources/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">Next</a>		
	</div>

	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection
