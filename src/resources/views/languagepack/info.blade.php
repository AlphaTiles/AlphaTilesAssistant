<?php
use App\Enums\ImportStatus;
use App\Enums\FieldTypeEnum;
use App\Repositories\LangInfoRepository;

$languagePackId = $languagePack ? $languagePack->id : '';
?>
@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Language Info</h1>

	@if(isset($languagePack) && $languagePack->import_status === ImportStatus::IMPORTING->value)
		<span class="text-blue-700 ml-4">Import in progress</span>
	
	@else
	
		<form method="post" action="/languagepack/edit/{{ $languagePackId }}">
			@csrf
			<div class="form">
				@if (isset($errors) && $errors->any())
				<div class="alert alert-error">
					<ul class="block">
						@foreach ($errors->all() as $error)
							<li class="block">{{ $error }}</li>
						@endforeach
					</ul>
				</div>
				@endif

				@foreach($settings as $setting)
				<?php
				$errorData = isset($errors) ? $errors : null;
				$value = LangInfoRepository::getValue($errorData, $setting);
				?>
				<label for="title">{{ $setting['label'] }}:</label><br>
				<div class="flex items-end">
					<div>
						@if($setting['type'] === FieldTypeEnum::DROPDOWN)
							<select name="settings[{{ $setting['name'] }}]">
								@foreach($setting['options'] as $optionKey => $optionValue)
									<?php $selected = $setting['value'] === $optionKey ? 'selected' : ''; ?>								
									<option value="{{ $optionKey }}" {{ $selected }}>{{ $optionValue }}</option>
								@endforeach
							</select>
						@elseif($setting['type'] === FieldTypeEnum::TEXTBOX)
							<textarea name="settings[{{ $setting['name'] }}]" rows=3 cols=50>{{ $setting['value'] }}</textarea>
						@else
							<?php $errorClass = isset($errors) && !empty($errors->keys()) && in_array('settings.' . $setting['name'], $errors->keys()) ? 'inputError' : ''; 
							?>
							<input type="text" class="form-control {{ $errorClass }}" name="settings[{{ $setting['name'] }}]" size="70" value="{{ $value }}" placeholder="{{ $setting['placeholder'] }}">
						@endif
					</div>
				</div>
				@endforeach
			</div>

			<div class="mt-3 w-9/12">		
				<input type="hidden" name="id" value="{{ $languagePackId }}" />
				<input type="submit" name="btnHiddenSave" id="saveButton" value="Save" class="hidden" />
				<input type="submit" name="btnSave" value="Save" class="btn-sm btn-primary ml-1 cursor-pointer" onClick='handleSaveReset();' />											
				@if(isset($languagePack->langInfo) &&  $languagePack->langInfo->count() > 0)
					<a href="#" onClick='autoSavePage("/languagepack/tiles/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">Next</a>		
				@endif
			</div>
		</form>
		
	@endif

	<div class="mt-5">
		<input type="button" value="Delete" onClick="confirmDelete();" class="ml-1 inline-block no-underline btn-sm btn-error font-normal cursor-pointer" />
	</div>
	
	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection

@section('scripts')
<script>
	function confirmDelete() {
	Swal.fire({
				title: 'Confirm Deletion',
				html: 'Please confirm that you want to delete this language pack and any words, files, etc. that you added to it.',
				showCancelButton: true,
				confirmButtonColor: 'red',
				confirmButtonText: 'Delete',
			})
			.then((willDelete) => {
				if (willDelete) {
					// User clicked the confirm button, send DELETE request
					fetch("/languagepack/delete/{{ $languagePackId }}", {
						method: 'DELETE',
						headers: {
							'X-CSRF-TOKEN': '{{ csrf_token() }}',
							'Content-Type': 'application/json',
						},
					})
					.then(response => {
						if (response.ok) {
							// Handle success response
							Swal.fire("The language pack has been deleted!", {
								icon: "success",
							}).then((confirmed) => {
								window.location.href = "/dashboard";
							});							
						} else {
							// Handle error response
							Swal.fire("Oops! Something went wrong!", {
								icon: "error",
							});
						}
					})
					.catch(error => {
						// Handle fetch error
						console.error(error);
						swal("Oops! Something went wrong!", {
							icon: "error",
						});
					});
				}
        });
	}
</script>
@endsection