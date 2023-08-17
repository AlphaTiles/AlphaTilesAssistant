<?php
use App\Enums\TileTypeEnum;
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
		<form method="post" action="/languagepack/tiles/{{ $id }}" enctype="multipart/form-data">			
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

	<form method="post" action="/languagepack/tiles/{{ $id }}" enctype="multipart/form-data">			
	@csrf
	@method('PATCH')
	@if(count($tiles) > 0)
		<div>
			<table class="table table-compact w-full">
				<colgroup>
					<col span="1" style="width: 5%;">
					<col span="1" style="width: 5%;">
					<col span="1" style="width: 15%;">
					<col span="1" style="width: 25%;">
					<col span="1" style="width: 25%;">
					<col span="1" style="width: 25%;">
				</colgroup>                        
				<thead>
				<tr>
					<th>Tile</th> 
					<th>Uppercase</th> 
					<th>Type</th>
					<th>Distractors</th>                             
					<th>Audio instructions</th>
					<th><input type="checkbox"  onClick="checkAllTiles(this)" /> Delete</th>
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
						<div class="custom-file">
							<input type="file" name="tiles[{{ $key }}][file]" class="custom-file-input" id="chooseFile" value="{{ old('tiles.' . $key . '.file') }}">
							@if(isset($tile->file) || isset($tile->filename))
								<?php 									
								$filename = $tile->file->name ?? $tile->filename;
								$storedFileNumber = str_pad($tile->id, 3, '0', STR_PAD_LEFT); 
								?>
								<a href="/languagepack/tiles/{{ $tile->languagepackid }}/download/tile_{{ $storedFileNumber }}.mp3">
									{{ mb_strlen($filename) > 30 ? mb_substr($filename, 0, 30) . '...' : $filename }}
								</a>
								<input type="hidden" name="tiles[{{ $key }}][filename]" value="{{ $filename }}">
							@endif
							@if($errors->has('tiles.' . $key . '.file'))
								<div class="error">The file upload failed.</div>
							@endif									
						</div>
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

	<form method="post" action="/languagepack/tiles/{{ $id }}">
		@csrf
		<div>
			<label for="add_tiles">Add tiles (one tile per line):</label><br>
			<textarea name="add_tiles" rows=7 cols=40></textarea>
		</div>

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $id }}" />
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
</script>
@endsection