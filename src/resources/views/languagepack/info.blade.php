<?php
use App\Enums\FieldTypeEnum;
$languagePackId = $languagePack ? $languagePack->id : '';

function getValue($errors, array $setting)
{	
	if ($errors->any()) {
		return old('settings.' . $setting['name']) ?? '';
	}

	return $setting['value'];
}
?>
@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Language Info</h1>
	
	<form method="post" action="/languagepack/edit/{{ $languagePackId }}">
		@csrf
		<div class="form">
			@if ($errors->any())
			<div class="alert alert-error">
				<ul class="block">
					@foreach ($errors->all() as $error)
						<li class="block">{{ $error }}</li>
					@endforeach
				</ul>
			</div>
			@endif

			@foreach($settings as $setting)
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
						<?php $errorClass = !empty($errors->keys()) && in_array('settings.' . $setting['name'], $errors->keys()) ? 'inputError' : ''; 
						?>
						<input type="text" class="form-control {{ $errorClass }}" name="settings[{{ $setting['name'] }}]" size="70" value="{{ getValue($errors, $setting) }}" placeholder="{{ $setting['placeholder'] }}">
					@endif
				</div>
			</div>
			@endforeach
		</div>

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePackId }}" />
			<input type="submit" name="btnSave" id="saveButton" value="Save" class="btn-sm btn-secondary" />
			<input type="submit" name="btnNext" value="Next" class="btn-sm btn-primary ml-1" />
		</div>
	</form>
	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection