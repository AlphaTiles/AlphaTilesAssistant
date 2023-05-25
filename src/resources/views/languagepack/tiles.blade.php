@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Tiles</h1>
	
	<form method="post" action="/languagepack/tiles/{{ $id }}">
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

			<label for="add_tiles">Add tiles (one tile per line):</label><br>
			<textarea name="add_tiles" rows=7 cols=40></textarea>
		</div>

		<div class="mt-3 w-9/12">		
			<input type="hidden" name="id" value="{{ $id }}" />
			<input type="submit" name="btnAdd" value="Add tiles" class="btn-sm btn-primary ml-1" />
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