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
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 20%;">
				</colgroup>                        
				<thead>
				<tr>
					<th>Include</th> 
					<th>Door</th> 
					<th>Friendly Name</th>					
					<th>Country</th> 
					<th>Level</th>
					<th>Color</th>                             
					<th>Audio instructions</th>
					<th>Syllable Or Tile</th>
					<th><span class="mr-2">Stages Included</span></th>
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

						<div class="ml-2 inline-block">
							<button type="button" class="move-game-btn" data-game-id="{{ $item->id }}" data-direction="up" data-language-pack-id="{{ $languagePack->id }}" title="Move up" style="padding: 2px 6px; font-size: 12px; background-color: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">↑</button>
							<button type="button" class="move-game-btn" data-game-id="{{ $item->id }}" data-direction="down" data-language-pack-id="{{ $languagePack->id }}" title="Move down" style="padding: 2px 6px; font-size: 12px; background-color: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">↓</button>
						</div>
					</td> 
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.door', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="items[{{ $key }}][door]" value="{{ old('items.' . $key . '.door') ?? $item->door }}" class="{{ $errorClass }}" readonly style="background-color: #f3f4f6; color: #6b7280; cursor: not-allowed;" />							
					</td>
					<td>
						<input type="text" size=20 name="items[{{ $key }}][friendly_name]" value="{{ old('items.' . $key . '.friendly_name') ?? $item->friendly_name }}" />
					</td> 
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.country', $errorKeys) ? 'inputError' : ''; ?>
						@php
							$countryOptions = \App\Enums\CountryEnum::options();
							$selectedCountry = old('items.' . $key . '.country') ?? $item->country;
						@endphp
						<select name="items[{{ $key }}][country]" class="{{ $errorClass }}" style="min-width: 120px;">
							<option value="">Select country</option>
							@foreach($countryOptions as $country)
								<option value="{{ $country }}" {{ $selectedCountry === $country ? 'selected' : '' }}>{{ $country }}</option>
							@endforeach
						</select>
					</td>
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.level', $errorKeys) ? 'inputError' : ''; ?>
						<input type="number" size=5 name="items[{{ $key }}][level]" value="{{ old('items.' . $key . '.level') ?? $item->level }}" class="{{ $errorClass }}" />
					</td>
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.color', $errorKeys) ? 'inputError' : ''; ?>
						@php
							$color = old('items.' . $key . '.color') ?? $item->color;
						@endphp
						<x-select-color :key="$key" :color="$color" :error-keys="$errorKeys ?? null" />
					<td class="max-w-sm>
						<x-select-file
						:nr="1"
						:key=$key
						:prefix="'game'"
						:item=$item
						:error-keys="$errorKeys ?? null"
						/>
					</td> 
					<td>								
						<?php $syllableOrTile = old('items.' . $key . '.syll_or_tile') ?? $item->syll_or_tile; ?>
						<select name="items[{{ $key }}][syll_or_tile]">
							<option value="T" {{ $syllableOrTile === 'T' ? 'selected' : '' }}>Tile</option>
							<option value="S" {{ $syllableOrTile === 'S' ? 'selected' : '' }}>Syllable</option>
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

@section('scripts')
<style>
	@keyframes highlightFlash {
		0% { background-color: #dbeafe; }
		50% { background-color: #bfdbfe; }
		100% { background-color: transparent; }
	}
	
	.highlight-reordered {
		animation: highlightFlash 1.5s ease-in-out forwards;
	}
</style>

<script>
document.querySelectorAll('.move-game-btn').forEach(btn => {
	console.log('Attaching event listener to move button');
	btn.addEventListener('click', function(e) {
		console.log('Move button clicked');
		e.preventDefault();
		const gameId = this.dataset.gameId;
		const direction = this.dataset.direction;
		const languagePackId = this.dataset.languagePackId;
		
		// Disable buttons during request
		document.querySelectorAll('.move-game-btn').forEach(b => b.disabled = true);
		
		fetch(`/api/games/${gameId}/move`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
			},
			body: JSON.stringify({
				direction: direction,
				languagePackId: languagePackId
			})
		})
		.then(response => {
			if (!response.ok) throw new Error('Move failed');
			return response.json();
		})
		.then(data => {
			// Redirect with reordered game ID for highlighting
			location.href = `{{ url('/languagepack/games') }}/${languagePackId}?reordered=${data.gameId}`;
		})
		.catch(error => {
			console.error('Error moving game:', error);
			alert('Failed to move game');
			// Re-enable buttons on error
			document.querySelectorAll('.move-game-btn').forEach(b => b.disabled = false);
		});
	});
});

// Highlight recently reordered items from URL parameter
window.addEventListener('load', function() {
	const params = new URLSearchParams(window.location.search);
	const reorderedId = params.get('reordered');
	
	if (reorderedId) {
		// Find row with the reordered game
		const rows = document.querySelectorAll('tbody tr');
		rows.forEach(row => {
			const hiddenInput = row.querySelector('input[name*="[id]"]');
			if (hiddenInput && hiddenInput.value === reorderedId) {
				row.classList.add('highlight-reordered');
				// Scroll into view
				row.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		});
		
		// Remove the reordered parameter from URL
		const newUrl = window.location.pathname + window.location.search.replace(/[?&]reordered=[^&]*/, '').replace(/^\?&/, '?');
		window.history.replaceState({}, document.title, newUrl);
	}
});
</script>
@endsection
