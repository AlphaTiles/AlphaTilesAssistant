<?php
use App\Enums\FieldTypeEnum;
?>
@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="container">

	<div class="prose">
		<h1>Export Language Pack</h1>
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
	
	<form method="post" action="/languagepack/export/{{ $languagePack->id }}">
		@csrf

		<div class="mt-5 mb-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePack->id }}" />
			<input type="submit" name="btnExport" value="Download language pack" class="btn-sm btn-primary cursor-pointer" />
		</div>		
	</form>

	<hr>

	<div class="mt-3 w-9/12 flex">
		<div>
			<a href="/drive/export/{{ $languagePack->id }}" class="btn btn-primary w-40 mt-1">Save data on Google Drive</a>
		</div>
		<div class="ml-5">
			This will export all the media files into folders on Google Drive and the data into a Google sheet.
			You will find the language pack inside a folder named "alphatilesassistant". 
		</div>
	</div>

	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection