<?php
use App\Enums\TabEnum;
use Illuminate\Support\Arr;
$tabEnum = TabEnum::GAME;
?>

@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Games</h1>
	
	<div>
		<div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
			@if (session()->has('success'))
			<div class="p-3 text-green-700 bg-green-300 rounded">
				{{ session()->get('success') }}
			</div>
			@endif
		</div>	
	</div>
	
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

	<form method="post" action="{{ route('update-games', $languagePack->id) . '?' . http_build_query(request()->query()) }}" enctype="multipart/form-data">			
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
					<th>Include</th> 
					<th>Door</th> 
					<th>Friendly Name</th>					
					<th>Country</th> 
					<th>Challenge Level</th>
					<th>Color</th>                             
					<th>Audio instructions</th>
					<th>Syllable Or Tile</th>
					<th>Stages Included</th>
				</tr>
				</thead> 
				<tbody>
				@foreach($items as $key => $item)
				<tr>
					<td>
						<input type="hidden" name="items[{{ $key }}][languagepackid]" value="{{ $item->languagepackid }}">
						<input type="hidden" name="items[{{ $key }}][id]" value="{{ $item->id }}" />
						<input type="hidden" name="items[{{ $key }}][value]" value="{{ $item->value }}" />						
						<input type="checkbox" name="items[{{ $key }}][include]" {{ $item->include ? 'checked' : '' }} />
					</td> 
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.door', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="items[{{ $key }}][door]" value="{{ old('items.' . $key . '.door') ?? $item->door }}" class="{{ $errorClass }}" />							
					</td> 
					<td>
						<input type="text" size=20 name="items[{{ $key }}][friendly_name]" value="{{ old('items.' . $key . '.friendly_name') ?? $item->friendly_name }}" />
					</td> 
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.country', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=15 name="items[{{ $key }}][country]" value="{{ old('items.' . $key . '.country') ?? $item->country }}" class="{{ $errorClass }}" />
					</td>
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.challenge_level', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=15 name="items[{{ $key }}][challenge_level]" value="{{ old('items.' . $key . '.challenge_level') ?? $item->challenge_level }}" class="{{ $errorClass }}" />
					</td>
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.color', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=10 name="items[{{ $key }}][color]" value="{{ old('items.' . $key . '.color') ?? $item->color }}" class="{{ $errorClass }}" />
					<td>
						<x-select-file
						:nr="1"
						:key=$key
						:prefix="'$item'"
						:item=$item
						:error-keys="$errorKeys ?? null"
						/>
					</td> 
					<td>								
						<?php $syllableOrTile = old('items.' . $key . '.syllable_or_tile') ?? $item->syllable_or_tile; ?>
						<select name="items[{{ $key }}][syllable_or_tile]">
							<option value="tile" {{ $syllableOrTile === 'T' ? 'selected' : '' }}>Tile</option>
							<option value="syllable" {{ $syllableOrTile === 'S' ? 'selected' : '' }}>Syllable</option>
						</select>
					</td> 					
					<td>
						<input type="number" size=5 name="items[{{ $key }}][stages_included]" value="{{ old('items.' . $key . '.stages_included') ?? $item->stages_included }}" />
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

	<div class="mt-6 w-9/12">	
		<a href="#" onClick='autoSavePage("/languagepack/game_settings/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-secondary pt-0.5 font-normal">Back</a>
		<a href="#" onClick='autoSavePage("/languagepack/export/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">Next</a>		
	</div>

	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection
