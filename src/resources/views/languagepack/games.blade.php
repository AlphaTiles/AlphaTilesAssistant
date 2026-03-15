<?php
use App\Enums\TabEnum;
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

	<form id="games-filter-form" method="get" action="{{ url('/languagepack/games/' . $languagePack->id) }}" class="mt-4 mb-0 space-y-1">
		@foreach(request()->except(['show_excluded', 'required_assets_filter', 'show_tile_audio', 'show_syllable_breaks_only', 'show_syllable_breaks_and_audio', 'show_abs_exclusive', 'page', 'reordered']) as $queryKey => $queryValue)
			@if(is_array($queryValue))
				@foreach($queryValue as $nestedValue)
					<input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}" />
				@endforeach
			@else
				<input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}" />
			@endif
		@endforeach
		<div class="flex flex-wrap gap-x-6 gap-y-1">
			<label class="inline-flex items-center gap-2 text-sm">
				<input type="hidden" name="show_excluded" value="0" />
				<input
					type="checkbox"
					name="show_excluded"
					value="1"
					{{ $showExcludedGames ? 'checked' : '' }}
				/>
				<span class="ml-1 mr-4">Excluded games</span>
			</label>

			<label class="inline-flex items-center gap-2 text-sm ml-2">
				<span class="ml-1">Required assets</span>
				<select name="required_assets_filter" class="input input-bordered input-sm w-52">
					<option value="all" {{ $requiredAssetsFilter === 'all' ? 'selected' : '' }}>All (excluding ABS)</option>
					<option value="TA" {{ $requiredAssetsFilter === 'TA' ? 'selected' : '' }}>Requires tile audio</option>
					<option value="SB/T" {{ $requiredAssetsFilter === 'SB/T' ? 'selected' : '' }}>Requires syllable breaks only</option>
					<option value="SB/T+SA" {{ $requiredAssetsFilter === 'SB/T+SA' ? 'selected' : '' }}>Requires syllable breaks and syllable audio</option>
					<option value="abs" {{ $requiredAssetsFilter === 'abs' ? 'selected' : '' }}>Requires Arabic Based Script setup</option>
				</select>
			</label>
		</div>
	</form>

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
						{{ $item->friendly_name }}
					</td> 
					<td>
						{{ $item->country }}
					</td>
					<td>
						{{ $item->level }}
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
						{{ $item->syll_or_tile}}
					</td> 					
					<td>
						<input type="number" size=5 class="w-20" name="items[{{ $key }}][stages_included]" value="{{ old('items.' . $key . '.stages_included') ?? $item->stages_included }}" />
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
function syncFilterFallbackInputs() {
	const filterForm = document.getElementById('games-filter-form');
	if (!filterForm) {
		return;
	}

	const checkboxes = filterForm.querySelectorAll('input[type="checkbox"][name]');
	checkboxes.forEach((checkbox) => {
		const hiddenFallback = filterForm.querySelector(`input[type="hidden"][name="${checkbox.name}"]`);
		if (hiddenFallback) {
			hiddenFallback.disabled = checkbox.checked;
		}
	});
}

const gamesFilterForm = document.getElementById('games-filter-form');
if (gamesFilterForm) {
	const filterCheckboxes = gamesFilterForm.querySelectorAll('input[type="checkbox"][name]');
	const requiredAssetsFilterSelect = gamesFilterForm.querySelector('select[name="required_assets_filter"]');
	const excludedGamesCheckbox = gamesFilterForm.querySelector('input[type="checkbox"][name="show_excluded"]');

	function enforceExcludedGamesDependency() {
		if (!excludedGamesCheckbox) {
			return;
		}

		const hasRequiredAssetsFilter = requiredAssetsFilterSelect && requiredAssetsFilterSelect.value !== 'all';

		if (hasRequiredAssetsFilter) {
			excludedGamesCheckbox.checked = true;
		}
	}

	enforceExcludedGamesDependency();
	syncFilterFallbackInputs();
	gamesFilterForm.addEventListener('submit', syncFilterFallbackInputs);

	filterCheckboxes.forEach((checkbox) => {
		checkbox.addEventListener('change', () => {
			if (checkbox.name === 'show_excluded' && !checkbox.checked && requiredAssetsFilterSelect) {
				requiredAssetsFilterSelect.value = 'all';
			}

			enforceExcludedGamesDependency();
			syncFilterFallbackInputs();
			if (typeof gamesFilterForm.requestSubmit === 'function') {
				gamesFilterForm.requestSubmit();
			} else {
				gamesFilterForm.submit();
			}
		});
	});

	if (requiredAssetsFilterSelect) {
		requiredAssetsFilterSelect.addEventListener('change', () => {
			enforceExcludedGamesDependency();
			syncFilterFallbackInputs();
			if (typeof gamesFilterForm.requestSubmit === 'function') {
				gamesFilterForm.requestSubmit();
			} else {
				gamesFilterForm.submit();
			}
		});
	}
}

function parseQueryParams(search) {
	const query = (search || '').replace(/^\?/, '');
	if (!query) {
		return {};
	}

	return query.split('&').reduce((result, pair) => {
		if (!pair) {
			return result;
		}

		const [rawKey, ...rawValueParts] = pair.split('=');
		const key = decodeURIComponent(rawKey || '');
		const value = decodeURIComponent((rawValueParts.join('=') || '').replace(/\+/g, ' '));

		if (key) {
			result[key] = value;
		}

		return result;
	}, {});
}

function buildQueryString(params) {
	return Object.keys(params)
		.filter(key => params[key] !== undefined && params[key] !== null && params[key] !== '')
		.map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
		.join('&');
}

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
			// Redirect with reordered game ID for highlighting while preserving current filters.
			const params = parseQueryParams(window.location.search);
			params.reordered = String(data.gameId);
			const queryString = buildQueryString(params);
			location.href = queryString
				? `{{ url('/languagepack/games') }}/${languagePackId}?${queryString}`
				: `{{ url('/languagepack/games') }}/${languagePackId}`;
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
	const params = parseQueryParams(window.location.search);
	const reorderedId = params.reordered;
	
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
		
		// Remove the reordered parameter from URL.
		delete params.reordered;
		const queryString = buildQueryString(params);
		const newUrl = queryString ? `${window.location.pathname}?${queryString}` : window.location.pathname;
		window.history.replaceState({}, document.title, newUrl);
	}
});
</script>
@endsection
