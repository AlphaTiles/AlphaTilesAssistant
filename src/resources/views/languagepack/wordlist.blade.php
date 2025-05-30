<?php
use App\Enums\TabEnum;
use App\Enums\LangInfoEnum;
use Illuminate\Support\Arr;
use App\Models\LanguageSetting;

$langName = LanguageSetting::where('languagepackid', $languagePack->id)
	->where('name', LangInfoEnum::LANG_NAME_ENGLISH->value)->first()->value;
$path = "/storage/languagepacks/" . $languagePack->id . "/res/raw/";	
$tabEnum = TabEnum::WORD;
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
		$wordData = old('words') ?? request()['words'] ?? $words;
		$deleteValues = old('words') ? Arr::pluck(old('words') , 'delete') : Arr::pluck($wordData , 'delete'); 
		$deleteValues = array_pad($deleteValues, count($words), null);
		?>
		@if($words && in_array(1, $deleteValues))		
		<form method="post" action="{{ url('/languagepack/wordlist/' . $languagePack->id) . '?' . http_build_query(request()->query()) }}" enctype="multipart/form-data">			
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
	
	<form method="post" action ="{{ url('/languagepack/wordlist/' . $languagePack->id) . '?' . http_build_query(request()->query()) }}" enctype="multipart/form-data">			
	@csrf
	@method('PATCH')
	@if(count($words) > 0)
		<div>
			<table class="table table-compact w-full">
				<colgroup>
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 10%;">
					<col span="1" style="width: 20%;">
					<col span="1" style="width: 30%;">
					<col span="1" style="width: 20%;">
					<col span="1" style="width: 10%;">
				</colgroup>                        
				<thead>
				<tr>
					<th>Word in {{ $langName }} <a href="#" onClick="openAlert('Words with Syllables', 'If you want to use syllable-based games, you must put periods between syllables in each word. If your words have spaces or dashes, you must put periods around the spaces or dashes. Example: o.pen.-.source');"><i class="fa-solid fa-circle-info"></i></a></th> 					
					<th>Mixed Types <a href="#" onClick="openAlert('Mixed Types', 'Not used by most languages. Watch this YouTube <a href=\'https://www.youtube.com/watch?v=s-HAUAc6tAg\' target=\'_blank\'>video</a> to learn more.');"><i class="fa-solid fa-circle-info"></i></a></th>
					<th>Audio</th>
					<th>Image</th>
					<th>Stage <a href="#" onClick="openAlert('First appears in stage (overrule default)', 'If you are defining stages for each tile, exceptions can be defined for individual words.');"><i class="fa-solid fa-circle-info"></i></a></th>
					<th><input type="checkbox" onClick="checkAll(this, 'words')" /> Delete</th>
				</tr>
				</thead> 
				<tbody>
				@foreach($words as $key => $word)
				<tr>
					<td>					
						<input type="hidden" name="words[{{ $key }}][languagepackid]" value="{{ $word->languagepackid }}">
						<input type="hidden" name="words[{{ $key }}][id]" value="{{ $word->id }}" />						
						<?php $errorClass = isset($errorKeys) && in_array('words.' . $key, $errorKeys) ? 'inputError' : ''; ?>			
						<input type="text" name="words[{{ $key }}][value]" value="{{ old('words.' . $key . '.value') ?? $word->value }}" class="{{ $errorClass }}" />
						<a href="#" onClick="showWordInfo({{ $word->languagepackid }}, {{$key }}, {{ $word->id }});">
							<i class="fa-solid fa-th"></i>
						</a>
					</td> 
					<td>								
						<input type="text" name="words[{{ $key }}][mixed_types]" value="{{ old('words.' . $key . '.mixed_types') ?? $word->mixed_types }}" />
					</td> 
					<td>
						<div class="custom-file">
							<input type="file" name="words[{{ $key }}][audioFile]" class="custom-file-input" id="chooseFile" value="{{ old('words.' . $key . '.audioFile') }}">
							<br>
							@if($errors->has('words.' . $key . '.audioFile') && old('words.' . $key . '.delete') != '1')							
								<div class="error">Upload a valid file</div>
							@else
								@if(isset($word->audioFile) || isset($word->audioFilename))
									<?php 		
									$audioFilename = isset($word->audioFile) ? (isset($word->audioFile->name) ? $word->audioFile->name : $word->audioFilename) 
										: (isset($word->audioFilename) ? $word->audioFilename : '');
										$storedFileName = '';
										if(!empty($word->audioFile->file_path)) {
											$storedFileName = str_replace($path, '', $word->audioFile->file_path);
										}									
									?>
									<div class="mt-1">
										<audio controls style="width: 200px;">
											<source src="/languagepack/wordlist/{{ $word->languagepackid }}/download/{{ $storedFileName }}?{{ time() }}" type="audio/mpeg">
											Your browser does not support the audio element.
										</audio> 								
									</div>
									<input type="hidden" name="words[{{ $key }}][audioFilename]" value="{{ $audioFilename }}">
								@endif
							@endif									
						</div>
					</td> 

					<td>
						<div class="custom-file">
							<input type="file" name="words[{{ $key }}][imageFile]" class="custom-file-input" id="chooseFile" value="{{ old('words.' . $key . '.imageFile') }}">
							<br>
							@if($errors->has('words.' . $key . '.imageFile') && old('words.' . $key . '.delete') != '1')							
								<div class="error">Upload a valid file</div>
							@else
								@if(isset($word->imageFile) || isset($word->imageFilename))
									<?php 		
									$imageFilename = isset($word->imageFile) ? (isset($word->imageFile->name) ? $word->imageFile->name : $word->imageFilename) 
										: (isset($word->imageFilename) ? $word->imageFilename : '');								
									$storedFileName = '';
									if(!empty($word->imageFile->file_path)) {
										$storedFileName = str_replace($path, '', $word->imageFile->file_path);
									}								
									?>
									<div class="mt-1">
										<img width="30" src="/languagepack/wordlist/{{ $word->languagepackid }}/download/{{ $storedFileName }}?{{ time() }}" />
									</div>
									<input type="hidden" name="words[{{ $key }}][imageFilename]" value="{{ $imageFilename }}">
								@endif
							@endif
						</div>
					</td> 

					<td>
						<div class="h-7"><input type="number" min="0" size=3 name="words[{{ $key }}][stage]" value="{{ $word->stage }}" /></div>
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


	<form id="formAddItems" method="post" action="/languagepack/wordlist/{{ $languagePack->id }}">
		@csrf
		<div>
			<label for="add_items">Add Words (one word per line):</label> <a href="#" onClick="openAlert('How many words should be included?', '300 words is recommended, but 100-150 words is a common starting point. A good goal is to include, for every game tile, one word that begins with that game tile (although there are of course some game tiles in some languages that never appear at the beginning of words). If you have more than 300 words, it is worth considering whether multiple apps should be made, perhaps dividing the words into semantic groupings or beginner/advanced groupings, etc.')"><i class="fa-solid fa-circle-info"></i></a><br>
			<textarea name="add_items" id="txtAddItems" rows=7 cols=45 class="leading-tight">{{ old('add_items') }}</textarea>
		</div>

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePack->id }}" />
			<input type="submit" name="btnAdd" value="Add words" class="btn-sm btn-primary ml-1" />
		</div>
		<div class="mt-6 w-9/12">	
			<a href="#" onClick='autoSavePage("/languagepack/tiles/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-secondary pt-0.5 font-normal">Back</a>
			@if($languagePack->words->count() > 0)
				<a href="#" onClick='autoSavePage("/languagepack/keyboard/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">Next</a>		
			@endif
		</div>

	</form>
	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection

@section('scripts')
<script>	
function showWordInfo(languagePackId, inputNr, wordId) {
    // Find the input element associated with this word
    const wordInput = document.querySelector(`input[name="words[${inputNr}][value]"]`);
    
    // Check if the input value has changed from its original value
    if (wordInput && wordInput.defaultValue !== wordInput.value) {
        Swal.fire({
            title: 'Unsaved Changes',
            text: 'You need to save the changes first before you can view the tiles.',
            confirmButtonColor: 'blue',
            confirmButtonText: 'Close',
            showConfirmButton: true,
            showCancelButton: false
        });
        return;
    }

    // Existing fetch logic for showing tiles
    fetch(`/api/words/tiles/${languagePackId}/${wordId}`)
        .then(response => response.text())
        .then(tiles => {
            Swal.fire({
                title: 'Word gets parsed into these tiles',
                html: tiles,
                confirmButtonColor: 'blue',
                confirmButtonText: 'Close',
                showConfirmButton: true,
                showCancelButton: false
            });
        })
        .catch(error => {
            console.error('Error fetching tiles:', error);
            Swal.fire({
                title: 'Error',
                text: 'Could not fetch tiles for the selected word.',
                confirmButtonColor: 'blue',
                confirmButtonText: 'Close',
                showConfirmButton: true,
                showCancelButton: false
            });
        });
}

document.getElementById('formAddItems').addEventListener('submit', function(event) {
	const text = document.getElementById('txtAddItems').value;
	const lines = text.split('\n');
	const pattern = /[\t,;]/;

	// Check each line for multiple columns
	const hasMultipleColumns = lines.some(line => pattern.test(line));

	if (hasMultipleColumns) {
		event.preventDefault(); // Pause form submission
		Swal.fire({
			title: 'Are you sure?',
			html: "Make sure you only include a single column of data that has the words as written in the language of the game.",
			showCancelButton: true,
			confirmButtonText: 'Yes, proceed!',
			cancelButtonText: 'Cancel',
			allowOutsideClick: false,
		}).then((result) => {
			if (result.isConfirmed) {
				event.target.submit(); // Submit the form if confirmed
			}
		});
	}
});
</script>
@endsection