@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')

<div class="prose">

    <h1>{{ __('Notes') }}</h1>

	<div>
		<div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
			@if (session()->has('success'))
			<div class="p-3 text-green-700 bg-green-300 rounded">
				{{ session()->get('success') }}
			</div>
			@endif
		</div>
	</div>

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

	<form method="post" action="/languagepack/notes/{{ $languagePack->id }}" enctype="multipart/form-data">
	@csrf
	@method('PATCH')
	@if(count($items) > 0)
		<div>
			<table class="table table-compact w-full">
				<colgroup>
					<col span="1" style="width: 5%;">
					<col span="1" style="width: 55%;">
					<col span="1" style="width: 20%;">
					<col span="1" style="width: 20%;">
				</colgroup>
				<thead>
				<tr>
					<th>{{ __('#') }}</th>
					<th>{{ __('Note') }}</th>
					<th>{{ __('Created') }}</th>
					<th>{{ __('Updated') }}</th>
				</tr>
				</thead>
				<tbody>
				@foreach($items as $key => $item)
				<tr>
					<td>{{ $items->firstItem() + $key }}</td>
					<td>
						<input type="hidden" name="items[{{ $key }}][id]" value="{{ $item->id }}" />
						<?php $errorClass = isset($errorKeys) && in_array('items.' . $key . '.text', $errorKeys) ? 'inputError' : ''; ?>
						<textarea name="items[{{ $key }}][text]" rows=2 cols=50 class="{{ $errorClass }}">{{ old('items.' . $key . '.text') ?? $item->text }}</textarea>
					</td>
					<td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
					<td>{{ $item->updated_at->format('Y-m-d H:i') }}</td>
				</tr>
				@endforeach
			</table>
		</div>

		<div>
			{!! $pagination !!}
		</div>

		<p>
			<input type="submit" name="btnHiddenSave" id="saveButton" value="{{ __('Save') }}" class="hidden" />
			<input type="submit" name="btnSave" value="{{ __('Save') }}" class="btn-sm btn-primary ml-1" onClick='handleSaveReset();' />
		</p>
	@endif

	</form>

	<form method="post" action="/languagepack/notes/{{ $languagePack->id }}">
		@csrf
		<div>
			<label for="text">{{ __('Add a note:') }}</label><br>

			<textarea name="text" rows=5 cols=50 class="leading-tight"></textarea>
		</div>

		<div class="mt-3 w-9/12">
			<input type="hidden" name="id" value="{{ $languagePack->id }}" />
			<input type="submit" name="btnAdd" value="{{ __('Add note') }}" class="btn-sm btn-primary ml-1" />
		</div>
	</form>

	<div class="mt-6 w-9/12">
		<a href="#" onClick='autoSavePage("/languagepack/games/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-secondary pt-0.5 font-normal">{{ __('Back') }}</a>
		<a href="#" onClick='autoSavePage("/languagepack/export/{{ $languagePack->id }}");' class="inline-block no-underline btn-sm btn-primary ml-1 pt-0.5 text-white font-normal">{{ __('Next') }}</a>
	</div>

	<div class="mt-4">
		<a href="/dashboard">{{ __('Back to Dashboard') }}</a>
	</div>
</div>

@endsection
