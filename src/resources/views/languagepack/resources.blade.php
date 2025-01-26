<?php
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
?>

@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Resources</h1>
	<div>Use the resources tab to promote web sites related to the language.</div>

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
		?>
		@if($items && in_array(1, $deleteValues))
		<form method="post" action="/languagepack/resources/{{ $languagePack->id }}" enctype="multipart/form-data">			
		@csrf
		@method('DELETE')
		<div class="alert mb-3">  				
			<div class="block p-2">
				<h3 class="mt-0">Are you sure want to delete the following resources?</h3>
				<?php $itemDeleteIds = []; ?>
				@foreach ($items as $key => $item)					
					@if(isset($deleteValues[$key]))
						<?php array_push($itemDeleteIds, $item->id); ?>
						<div>{{ $item->link }}</div>
					@endif
				@endforeach					
				<div class="mt-2">
					<input type="hidden" name="deleteIds" value="{{ implode(',', $itemDeleteIds); }}" />
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

	<form method="post" action="/languagepack/resources/{{ $languagePack->id }}" enctype="multipart/form-data">			
	@csrf
	@method('PATCH')
	@if(count($items) > 0)
		<div>
			<table class="table table-compact w-full">
				<colgroup>
					<col span="1" style="width: 20%;">
					<col span="1" style="width: 20%;">
					<col span="1" style="width: 40%;">
					<col span="1" style="width: 20%;">
				</colgroup>                        
				<thead>
				<tr>
					<th>Name</th> 
					<th>Link</th> 
					<th>Image</th>
					<th><input type="checkbox" onClick="checkAll(this, 'items')" /> Delete</th>
				</tr>
				</thead> 
				<tbody>
				@foreach($items as $key => $item)
				<tr>
					<td>
					<input type="hidden" name="items[{{ $key }}][languagepackid]" value="{{ $item->languagepackid }}">
						<input type="hidden" name="items[{{ $key }}][id]" value="{{ $item->id }}" />
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.name', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=20 name="items[{{ $key }}][name]" value="{{ $item->name }}" class="{{ $errorClass }}" />
					</td> 					
					<td>
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.link', $errorKeys) ? 'inputError' : ''; ?>
						<input type="text" size=30 name="items[{{ $key }}][link]" value="{{ old('items.' . $key . '.link') ?? $item->link }}" class="{{ $errorClass }}" />
					</td> 
					<td>
						<div class="custom-file h-7">
							<x-select-file
								:nr="1"
								:key=$key
								:prefix="'resource'"
								:item=$item
								:error-keys="$errorKeys ?? null"
								/>
						</div>						
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

	<form method="post" action="/languagepack/resources/{{ $languagePack->id }}">
		@csrf
		<div>
			<label for="add_resources">Add resource links (one link per line):</label><br>

			<textarea name="add_resources" rows=7 cols=40 class="leading-tight"></textarea>
		</div>

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePack->id }}" />
			<input type="submit" name="btnAdd" value="Add resources" class="btn-sm btn-primary ml-1" />
		</div>
	</form>
	<div class="mt-6 w-9/12">	
		<a href="#" onClick='autoSavePage("/languagepack/syllables/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-secondary pt-0.5 font-normal">Back</a>
		<a href="#" onClick='autoSavePage("/languagepack/game_settings/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">Next</a>		
	</div>

	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection
