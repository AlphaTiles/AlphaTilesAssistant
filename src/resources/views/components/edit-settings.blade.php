<?php
use App\Enums\FieldTypeEnum;
use App\Models\File;
?>	
	<form method="post" action="/languagepack/{{ $formPath }}/{{ $languagePackId }}" enctype="multipart/form-data">
		@csrf
		<div class="form w-fit">
			<div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
				@if (session()->has('success'))
				<div class="p-3 text-green-700 bg-green-300 rounded mb-2">
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
				$value = $repositoryClass::getValue($errorData, $setting);
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
						@elseif($setting['type'] === FieldTypeEnum::UPLOAD)
							<div>
								<input type="file" class="form-control" name="settings[{{ $setting['name'] }}]" >
								@if(!empty($value))
									@php
										// determine URL for file (keep absolute URLs as-is, use asset() for local paths)
										$file = File::find($value);
										$fileName = $file ? $file->name : null;
										$url = '';
										if ($fileName) {
											$url = "/languagepack/items/$languagePackId/download/$fileName";
										}
									@endphp

									<div class="mt-2">
										<a href="{{ $url }}" target="_blank" rel="noopener">{{ $fileName }}</a>

										<label class="ml-2 inline-flex items-center">
											<input type="checkbox" name="settings[{{ $setting['name'] }}_remove]" value="1" class="ml-1">
											<span class="ml-1 text-sm">Remove file</span>
										</label>
									</div>
								@endif
							</div>
						@else
							<?php $errorClass = isset($errors) && !empty($errors->keys()) && in_array('settings.' . $setting['name'], $errors->keys()) ? 'inputError' : ''; 
							?>
							<input type="text" class="form-control {{ $errorClass }}" name="settings[{{ $setting['name'] }}]" size="60" value="{{ $value }}" placeholder="{{ $setting['placeholder'] }}">
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
			@if($showNext && empty($backPath))
				<a href="#" onClick='autoSavePage("/languagepack/{{ $nextPath }}/{{ $languagePackId }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">Next</a>		
			@endif
		</div>
		@if(!empty($backPath))
		<div class="mt-6 w-9/12">	
			<a href="#" onClick='autoSavePage("/languagepack/{{ $backPath }}/{{ $languagePackId }}");' class="inline-block no-underline btn-sm btn-secondary pt-0.5 font-normal">Back</a>
			<a href="#" onClick='autoSavePage("/languagepack/{{ $nextPath }}/{{ $languagePackId }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">Next</a>		
		</div>
		@endif
	</form>		
