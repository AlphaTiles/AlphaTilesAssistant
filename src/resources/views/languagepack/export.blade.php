<?php
use App\Enums\FieldTypeEnum;
?>
@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Export Language Pack</h1>
	
	<form method="post" action="/languagepack/export/{{ $languagePack->id }}">
		@csrf

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $languagePack->id }}" />
			<input type="submit" name="btnExport" value="Download language pack" class="btn-sm btn-primary" />
		</div>
	</form>
	<div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>
</div>

@endsection