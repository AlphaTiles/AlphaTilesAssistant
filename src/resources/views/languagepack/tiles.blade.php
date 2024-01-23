<?php
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
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
		$tilesData = old('tiles') ?? $tiles;
		$deleteValues = old('tiles') ? Arr::pluck(old('tiles') , 'delete') : Arr::pluck($tilesData , 'delete'); 
		?>
		@if($tiles && in_array(1, $deleteValues))
		<form method="post" action="/languagepack/tiles/{{ $languagePack->id }}" enctype="multipart/form-data">			
		@csrf
		@method('DELETE')
		<div class="alert mb-3">  				
			<div class="block p-2">
				<h3 class="mt-0">Are you sure want to delete the following tiles?</h3>
				<?php $tileDeleteIds = []; ?>
				@foreach ($tiles as $key => $tile)					
					@if(isset($deleteValues[$key]))
						<?php array_push($tileDeleteIds, $tile->id); ?>
						<div>{{ $tile->value }}</div>
					@endif
				@endforeach					
				<div class="mt-2">
					<input type="hidden" name="tileIds" value="{{ implode(',', $tileDeleteIds); }}" />
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

	<form method="post" action="/languagepack/tiles/{{ $languagePack->id }}" enctype="multipart/form-data">			
	@csrf
	@method('PATCH')
	@if(count($tiles) > 0)
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
					<th>Distractors</th>                             
					<th>Audio instructions</th>
					<th>Stage <a href="#" onClick="openAlert('First Stage', 'Define in which stage the tile should first appear');"><i class="fa-solid fa-circle-info"></i></a></th>
					<th><input type="checkbox"  /> Delete</th>
				</tr>
				</thead> 
				<tbody>
				@foreach($tiles as $key => $tile)
				<tr>
					<td>
					<input type="hidden" name="tiles[{{ $key }}][languagepackid]" value="{{ $tile->languagepackid }}">
						<input type="hidden" name="tiles[{{ $key }}][id]" value="{{ $tile->id }}" />
						<input type="hidden" name="tiles[{{ $key }}][value]" value="{{ $tile->value }}" />
						{{ $tile->value }}
					</td> 
					<td>								
						<input type="text" size=2 name="tiles[{{ $key }}][upper]" value="{{ $tile->upper }}" />
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
						<div class="mt-1 h-7">
						<x-select-type 
							:nr="2"
							:key=$key
							:tile=$tile
							:error-keys="$errorKeys ?? null"
						/>
						</div>
						<div class="mt-1 h-7">
						<x-select-type 
							:nr="3"
							:key=$key
							:tile=$tile
							:error-keys="$errorKeys ?? null"
						/>
						</div>
					</td> 
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('tiles.' . $key . '.or_1', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="tiles[{{ $key }}][or_1]" value="{{ old('tiles.' . $key . '.or_1') ?? $tile->or_1 }}" class="{{ $errorClass }}" />
						<?php $errorClass = isset($errorKeys) && in_array('tiles.' . $key . '.or_2', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="tiles[{{ $key }}][or_2]" value="{{ old('tiles.' . $key . '.or_2') ?? $tile->or_2 }}" class="{{ $errorClass }}" />
						<?php $errorClass = isset($errorKeys) && in_array('tiles.' . $key . '.or_3', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=2 name="tiles[{{ $key }}][or_3]" value="{{ old('tiles.' . $key . '.or_3') ?? $tile->or_3 }}" class="{{ $errorClass }}" />
					</td> 
					<td>
						<div class="custom-file h-7">
							<x-select-file
							:nr="1"
							:key=$key
							:tile=$tile
							:error-keys="$errorKeys ?? null"
							/>
						</div>
						<div class="mt-1 custom-file h-7">						
							<x-select-file
							:nr="2"
							:key=$key
							:tile=$tile
							:error-keys="$errorKeys ?? null"
							/>
						</div>
						<div class="mt-1 custom-file h-7">						
							<x-select-file
							:nr="3"
							:key=$key
							:tile=$tile
							:error-keys="$errorKeys ?? null"
							/>
						</div>
					</td> 
					<td>								
						<div class="h-7">1: <input type="number" min="0" size=3 name="tiles[{{ $key }}][stage]" value="{{ $tile->stage }}" /></div>
						<div id="stage{{ $key }}_2" class="h-7 {{ empty($tile->type2) ? 'hidden' : '' }}">2: <input type="number" min="0" size=3 name="tiles[{{ $key }}][stage2]" value="{{ $tile->stage2 }}" /></div>
						<div id="stage{{ $key }}_3" class="h-7 {{ empty($tile->type3) ? 'hidden' : '' }}">3: <input type="number" min="0" size=3 name="tiles[{{ $key }}][stage3]" value="{{ $tile->stage3 }}" /></div>
					</td> 					
					<td>
						<?php $delete = $deleteValues[$key] === '1'; ?>
						<input type="checkbox" name="tiles[{{ $key }}][delete]" value="1" 
							{{  $delete ? 'checked' : '' }} />
					</td>
				</tr>
				@endforeach
			</table>                    
		</div>

		<p>
			<input type="submit" name="btnSave" value="Save" class="btn-sm btn-primary ml-1" />
		</p>			
	@endif

		</div>
	</form>

	<form method="post" action="/languagepack/tiles/{{ $languagePack->id }}">
		@csrf
		<div>
			<label for="add_tiles">Add tiles (one tile per line):</label><br>
			<textarea name="add_tiles" rows=7 cols=40></textarea>
		</div>

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePack->id }}" />
			<input type="submit" name="btnAdd" value="Add tiles" class="btn-sm btn-primary ml-1" />
		</div>
		<div class="mt-6 w-9/12">	
			<input type="submit" name="btnBack" value="Back" class="btn-sm btn-secondary" />
			<input type="submit" name="btnNext" value="Next" class="btn-sm btn-primary ml-1" />
		</div>

	</form>
	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection

@section('scripts')
<script>	
function checkAllTiles(source) {
	let checkboxes = document.querySelectorAll('input[name^="tiles["][name$="][delete]"]');
	for(var i=0, n=checkboxes.length;i<n;i++) {
		checkboxes[i].checked = source.checked;
	}
}

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
	console.log(stageId);
	var showStage = document.getElementById(stageId);
	showStage.classList.remove('hidden');

	if(nr === 2) {
		var addSecondLink = document.getElementById('add_type' + key + '_3');
		addSecondLink.classList.remove('hidden');
	}
}

function openAlert(title, text) {
	Swal.fire({
                title: title,
                text: text,
                confirmButtonText: 'OK'
            });
}
</script>
@endsection