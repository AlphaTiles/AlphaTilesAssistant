<?php
use App\Enums\ImportStatus;
use Illuminate\Support\Facades\Auth;
?>
@extends('layouts.app')

@section('content')
<div class="container">

    <div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
		@if (session()->has('success'))
		<div class="p-3 text-green-700 bg-green-300 rounded">
			{{ session()->get('success') }}
		</div>
		@endif
	</div>	

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-title">{{ __('Welcome') }} {{ Auth::user()->name }}</div>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <a href="/languagepack/create" class="btn btn-primary w-40 mt-1">{{ __('Create Language Pack') }}</a>
                    @if (Auth::user()->isAdmin())
                        <a href="{{ route('admin.languagepacks') }}" class="btn btn-secondary mt-1">{{ __('All Language Packs') }}</a>
                    @endif
                </div>

                @include('partials.languagepacks-table', ['languagepacks' => $languagepacks, 'showOwnerColumn' => false])

            </div>
        </div>
    </div>
</div>
@endsection


@section('scripts')
<script>

function confirmRemoveCollaboration(languagepackId) {
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
                    window.location.href = "/languagepack/remove/" + languagepackId + "/{{ Auth::id() }}";
				}
			});
		}

function confirmDeleteLanguagePack(languagepackId) {
	Swal.fire({
				title: "{{ __('Delete language pack') }}",
				html: "{{ __('Please confirm that you want to permanently delete this language pack.') }}",
				showCancelButton: true,
				cancelButtonText: "{{ __('Cancel') }}",
				cancelButtonColor: 'grey',
				confirmButtonColor: 'red',
				confirmButtonText: "{{ __('Delete') }}",
				allowOutsideClick: false,
			})
			.then((result) => {
				if (result.isConfirmed) {
                    window.location.href = "/languagepack/delete/" + languagepackId;
				}
			});
		}
</script>
@endsection        