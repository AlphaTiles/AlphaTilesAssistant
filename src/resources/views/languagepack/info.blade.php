<?php
use App\Enums\ImportStatus;
$repositoryClass = \App\Repositories\LangInfoRepository::class;
$languagePackId = $languagePack ? $languagePack->id : null;

$showNext = false;
if(isset($languagePack->langInfo) &&  $languagePack->langInfo->count() > 0) {
	$showNext = true;
}

?>
@extends('layouts.app')

@section('content')

@include('layouts/langpacksteps')    

<div class="prose">

    <h1>{{ __('Language Info') }}</h1>

	@if(isset($languagePack) && $languagePack->import_status === ImportStatus::IMPORTING->value)
		<span class="text-blue-700 ml-4">{{ __('Import in progress') }}</span>
	
	@else
	<x-edit-settings 
		:language-pack-id=$languagePackId
		:settings=$settings
		:repository-class="$repositoryClass"
		:show-next=$showNext
		form-path="edit"
		next-path="tiles"
	/>
		
	@endif

	@if($languagePack)
	<div class="mt-5">
		@if($languagePack->user_id == Auth::id())
			<input type="button" value="{{ __('Delete') }}" onClick="confirmDelete();" class="ml-1 inline-block no-underline btn-sm btn-error font-normal cursor-pointer" />
		@else
			<input type="button" value="{{ __('Leave Project') }}" onClick="confirmRemoveCollaboration();" class="ml-1 inline-block no-underline btn-sm btn-error font-normal cursor-pointer /">
		@endif
	</div>
	@endif
	
	<div class="mt-4">
		<a href="/dashboard">{{ __('Back to Dashboard') }}</a>
	</div>
</div>

@endsection

@section('scripts')
<script>
	function confirmDelete() {
	Swal.fire({
				title: "{{ __('Confirm Deletion') }}",
				html: "{{ __('Please confirm that you want to delete this language pack and any words, files, etc. that you added to it.') }}",
				showCancelButton: true,
				cancelButtonText: "{{ __('Cancel') }}",
				cancelButtonColor: 'grey',
				confirmButtonColor: 'red',
				confirmButtonText: "{{ __('Delete') }}",
				allowOutsideClick: false,
			})
			.then((result) => {				
				if (result.isConfirmed) {
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
							Swal.fire("{{ __('The language pack has been deleted!') }}", {
								icon: "success",
							}).then((confirmed) => {
								window.location.href = "/dashboard";
							});							
						} else {
							// Handle error response
							Swal.fire("{{ __('Oops! Something went wrong!') }}", {
								icon: "error",
							});
						}
					})
					.catch(error => {
						// Handle fetch error
						console.error(error);
						swal("{{ __('Oops! Something went wrong!') }}", {
							icon: "error",
						});
					});
				}
        });
	}

	function confirmRemoveCollaboration() {
	Swal.fire({
				title: "{{ __('Confirm removal') }}",
				html: "{{ __('Please confirm that you want to be removed as collaborator from this project.') }}",
				showCancelButton: true,
				cancelButtonText: "{{ __('Cancel') }}",
				cancelButtonColor: 'grey',
				confirmButtonColor: 'red',
				confirmButtonText: "{{ __('Leave project') }}",
				allowOutsideClick: false,
			})
			.then((result) => {
				if (result.isConfirmed) {
					window.location.href = "/languagepack/remove/{{ $languagePackId }}/{{ Auth::id() }}";
				}
			});
		}
</script>
@endsection