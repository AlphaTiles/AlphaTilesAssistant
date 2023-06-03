<?php
use App\Enums\TileTypeEnum;
?>

@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>Tiles</h1>
	
	<form method="post" action="/languagepack/tiles/{{ $id }}">
		@csrf
		@method('PATCH')
		<div>
			<div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
				@if (session()->has('success'))
				<div class="p-3 text-green-700 bg-green-300 rounded">
					{{ session()->get('success') }}
				</div>
				@endif
			</div>			
			@if ($errors->any())
			<div class="alert alert-error">
				<ul class="block">
					@foreach ($errors->all() as $error)
						<li class="block">{{ $error }}</li>
					@endforeach
				</ul>
			</div>
			@endif

			@if(count($tiles) > 0)
                <div>
                    <table class="table table-compact w-full">
                        <colgroup>
                            <col span="1" style="width: 5%;">
							<col span="1" style="width: 5%;">
							<col span="1" style="width: 15%;">
                            <col span="1" style="width: 25%;">
                            <col span="1" style="width: 25%;">
							<col span="1" style="width: 25%;">
                        </colgroup>                        
                        <thead>
                        <tr>
                            <th>Tile</th> 
							<th>Uppercase</th> 
							<th>Type</th>
                            <th>Distractors</th>                             
							<th>Audio instructions</th>
							<th>Delete</th>
                        </tr>
                        </thead> 
                        <tbody>
                        @foreach($tiles as $key => $tile)
                        <tr>
                            <td>
								<input type="hidden" name="tiles[{{ $key }}][id]" value="{{ $tile->id }}" />
								{{ $tile->value }}
                            </td> 
                            <td>
								<input type="text" size=2 name="tiles[{{ $key }}][upper]" value="{{ $tile->upper }}" />
                            </td> 
                            <td>
								<select name="tiles[{{ $key }}][type]">
									<option value=""></option>
								@foreach(TileTypeEnum::cases() as $optionKey => $typeEnum)
									<?php $selected = $tile->type === $typeEnum->value ? 'selected' : ''; ?>								
									<option value="{{ $typeEnum->value }}" {{ $selected }}>{{ $typeEnum->label() }}</option>
								@endforeach								
							</td> 
							<td>
								<input type="text" size=2 name="tiles[{{ $key }}][or_1]" value="{{ $tile->or_1 }}" />
								<input type="text" size=2 name="tiles[{{ $key }}][or_2]" value="{{ $tile->or_2 }}" />
								<input type="text" size=2 name="tiles[{{ $key }}][or_3]" value="{{ $tile->or_3 }}" />
							</td> 
							<td></td> 
							<td>
								<a href="/languagepack/tiles/{{ $tile->languagepackid }}/delete/{{ $tile->id }}">
									<i class="fa-regular fa-trash-can"></i>
								</a>							
							</td>
                        </tr>
                        @endforeach
                    </table>                    
                </div>
                @endif

				<p>
					<input type="submit" name="btnSave" value="Save" class="btn-sm btn-primary ml-1" />
				</p>
			</div>
		</form>

	<form method="post" action="/languagepack/tiles/{{ $id }}">

		<div>
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