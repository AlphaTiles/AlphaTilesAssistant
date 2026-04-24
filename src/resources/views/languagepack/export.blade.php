@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="container">

	<div class="prose">
		<h1>Export Language Pack</h1>
	</div>

	<x-validation-errors
		:languagePack="$languagePack"
		:errors="$errors"
		:tab=null
	/>	

		<form id="zip-export-form" method="post" action="/languagepack/export/{{ $languagePack->id }}" data-warning-message="{{ $exportWarningMessage }}" data-warning-level="{{ $exportWarningLevel }}">
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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
	var form = document.getElementById('zip-export-form');
	if (!form) {
		return;
	}

	var warningMessage = form.dataset.warningMessage || '';
	var warningLevel = form.dataset.warningLevel || '';
	if (!warningMessage) {
		return;
	}

	var isCriticalWarning = warningLevel === 'critical';
	var confirmButtonColor = isCriticalWarning ? '#dc2626' : '#d97706';

	form.addEventListener('submit', function (event) {
		event.preventDefault();

		Swal.fire({
			title: 'Proceed with export?',
			text: warningMessage,
			icon: isCriticalWarning ? 'warning' : 'info',
			showCancelButton: true,
			confirmButtonText: 'Proceed',
			cancelButtonText: 'Cancel',
			confirmButtonColor: confirmButtonColor,
			reverseButtons: true,
		}).then(function (result) {
			if (result.isConfirmed) {
				form.submit();
			}
		});
	});
});
</script>
@endsection