<?php
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

?>

@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Wordlist</h1>
	
	<div>
		<div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
			@if (session()->has('success'))
			<div class="p-3 text-green-700 bg-green-300 rounded">
				{{ session()->get('success') }}
			</div>
			@endif
		</div>	
		<?php 
		$wordData = old('words') ?? $words;
		$deleteValues = old('words') ? Arr::pluck(old('words') , 'delete') : Arr::pluck($wordData , 'delete'); 
		?>
		@if($words && in_array(1, $deleteValues))
		<form method="post" action="/languagepack/wordlist/{{ $id }}" enctype="multipart/form-data">			
		@csrf
		@method('DELETE')
		<div class="alert mb-3">  				
			<div class="block p-2">
				<h3 class="mt-0">Are you sure want to delete the following words?</h3>
				<?php $wordDeleteIds = []; ?>
				@foreach ($words as $key => $word)	
					@if(isset($deleteValues[$key]))
						<?php array_push($wordDeleteIds, $word->id); ?>
						<div>{{ $word->value }}</div>
					@endif
				@endforeach					
				<div class="mt-2">
					<input type="hidden" name="wordIds" value="{{ implode(',', $wordDeleteIds); }}" />
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


	<form method="post" action="/languagepack/wordlist/{{ $id }}" enctype="multipart/form-data">			
	@csrf
	@method('PATCH')
	@if(count($words) > 0)
		<div>
			<table class="table table-compact w-full">
				<colgroup>
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 15%;">
					<col span="1" style="width: 30%;">
					<col span="1" style="width: 10%;">
				</colgroup>                        
				<thead>
				<tr>
					<th>Word</th> 
					<th>Translation</th> 
					<th>Mixed Types</th>
					<th>Audio</th>
					<th><input type="checkbox"  onClick="checkAllWords(this)" /> Delete</th>
				</tr>
				</thead> 
				<tbody>
				@foreach($words as $key => $word)
				<tr>
					<td>
						<input type="hidden" name="words[{{ $key }}][languagepackid]" value="{{ $word->languagepackid }}">
						<input type="hidden" name="words[{{ $key }}][id]" value="{{ $word->id }}" />
						<input type="hidden" name="words[{{ $key }}][value]" value="{{ $word->value }}" />
						{{ $word->value }}
					</td> 
					<td>					
						<?php $errorClass = isset($errorKeys) && in_array('words.' . $key . '.translation', $errorKeys) ? 'inputError' : ''; ?>			
						<input type="text" name="words[{{ $key }}][translation]" value="{{ old('words.' . $key . '.translation') ?? $word->translation }}" class="{{ $errorClass }}" />
					</td> 
					<td>								
						<input type="text" name="words[{{ $key }}][mixed_types]" value="{{ old('words.' . $key . '.mixed_types') ?? $word->mixed_types }}" />
					</td> 
					<td>
						<div class="custom-file">
							<input type="file" name="words[{{ $key }}][file]" class="custom-file-input" id="chooseFile" value="{{ old('words.' . $key . '.file') }}">
							@if(isset($word->file) || isset($word->filename))
								<?php 		
								$filename = isset($word->file) ? (isset($word->file->name) ? $word->file->name : $word->filename) 
									: (isset($word->filename) ? $word->filename : '');
								$storedFileName = strtolower(preg_replace("/\s+/", "", $word->translation));
								?>
								<a href="/languagepack/wordlist/{{ $word->languagepackid }}/download/{{ $storedFileName }}.mp3">
									{{ mb_strlen($filename) > 30 ? mb_substr($filename, 0, 30) . '...' : $filename }}
								</a>
								<input type="hidden" name="words[{{ $key }}][filename]" value="{{ $filename }}">
							@endif
							@if($errors->has('words.' . $key . '.file') && old('words.' . $key . '.delete') != '1')							
								<div class="error">Upload a valid file</div>
							@endif									
						</div>
					</td> 
					<td>
						<?php $delete = $deleteValues[$key] === '1'; ?>
						<input type="checkbox" name="words[{{ $key }}][delete]" value="1" 
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


	<form method="post" action="/languagepack/wordlist/{{ $id }}">
		@csrf
		<div>
			<label for="add_words">Add Words (one word + translation separated by tab per line):</label><br>
			<textarea name="add_words" rows=7 cols=45></textarea>
		</div>

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $id }}" />
			<input type="submit" name="btnAdd" value="Add words" class="btn-sm btn-primary ml-1" />
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
function checkAllWords(source) {
	let checkboxes = document.querySelectorAll('input[name^="words["][name$="][delete]"]');
	for(var i=0, n=checkboxes.length;i<n;i++) {
		checkboxes[i].checked = source.checked;
	}
}
</script>
@endsection