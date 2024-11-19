<?php
use App\Enums\ImportStatus;
use App\Enums\FieldTypeEnum;
use App\Repositories\GameSettingsRepository;
use App\Repositories\LangInfoRepository;

$languagePackId = $languagePack ? $languagePack->id : '';
?>
@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Game Settings</h1>
	
	<form method="post" action="/languagepack/game_settings/{{ $languagePackId }}">
		@csrf
		@method('PATCH')
		<div class="form w-fit">
			<div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
				@if (session()->has('success'))
				<div class="p-3 text-green-700 bg-green-300 rounded">
					{{ session()->get('success') }}
				</div>
				@endif
			</div>	

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
			<div class="grid grid-cols-2 gap-4 p-2 even:bg-gray-100">
				<?php
				$errorData = isset($errors) ? $errors : null;
				$value = GameSettingsRepository::getValue($errorData, $setting);
				?>
				<div>
					<label for="title">{{ $setting['label'] }}:</label> 				
				</div>
			
				<div class="flex items-end gap-1">
					<div>
						@if($setting['type'] === FieldTypeEnum::DROPDOWN)
							<select name="settings[{{ $setting['name'] }}]">
								@foreach($setting['options'] as $optionKey => $optionValue)								
									<?php $selected = $setting['value'] == $optionKey ? 'selected' : ''; ?>								
									<option value="{{ $optionKey }}" {{ $selected }}>{{ $optionValue }}</option>
								@endforeach
							</select>
						@elseif($setting['type'] === FieldTypeEnum::CHECKBOX)
							<?php $isChecked = $setting['value'] == 1; ?>
							<input type="hidden" name="settings[{{ $setting['name'] }}]" value="0">
							<input type="checkbox" name="settings[{{ $setting['name'] }}]" value="1" {{ $isChecked ? 'checked' : '' }}>
						@elseif($setting['type'] === FieldTypeEnum::TEXTBOX)
							<textarea name="settings[{{ $setting['name'] }}]" rows=3 cols=50>{{ $setting['value'] }}</textarea>
						@elseif($setting['type'] === FieldTypeEnum::NUMBER)
							<input type="number" class="form-control" name="settings[{{ $setting['name'] }}]" min=1 max="{{ $setting['max'] }}" size="10" value="{{ $value }}" placeholder="{{ $setting['placeholder'] }}">
						@else
							<?php $errorClass = isset($errors) && !empty($errors->keys()) && in_array('settings.' . $setting['name'], $errors->keys()) ? 'inputError' : ''; 
							?>
							<input type="text" class="form-control {{ $errorClass }}" name="settings[{{ $setting['name'] }}]" size="70" value="{{ $value }}" placeholder="{{ $setting['placeholder'] }}">
						@endif
					</div>
					@if(isset($setting['help_image']) || isset($setting['help_text']))
						<div><a href="#" onClick="openAlert('{{ $setting['label'] }}', '{{ $setting['help_text'] }}', '{{ $setting['help_image'] }}');"><i class="fa-solid fa-circle-info"></i></a></div>
					@endif


				</div>
			</div>
			@endforeach

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePackId }}" />
			<input type="submit" name="btnHiddenSave" id="saveButton" value="Save" class="hidden" />
			<input type="submit" name="btnSave" value="Save" class="btn-sm btn-primary ml-1 cursor-pointer" onClick='handleSaveReset();' />											
		</div>
	</form>		
	
	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection
