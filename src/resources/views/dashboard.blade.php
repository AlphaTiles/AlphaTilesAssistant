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

                <div class="mt-5">
                    <a href="/languagepack/create" class="btn btn-primary w-40 mt-1">{{ __('Create Language Pack') }}</a>
                </div>

                @if(count($languagepacks) > 0)
                <div class="overflow-x-auto mt-5 max-w-3xl">
                    <table class="table table-compact w-full">
                        <colgroup>
                            <col span="1" style="width: 5%;">
                            <col span="1" style="width: 5%;">
                            <col span="1" style="width: 70%;">
                            <col span="1" style="width: 15%;">
                        </colgroup>                        
                        <thead>
                        <tr>
                            <th>{{ __('Edit') }}</th> 
                            <th>{{ __('Users') }}</th>
                            <th>{{ __('Name') }}</th> 
                            <th>{{ __('Date Created') }}</th> 
                        </tr>
                        </thead> 
                        <tbody>
                        @foreach($languagepacks as $languagepack)
                        <tr>
                            <td>
                                <a href="/languagepack/edit/{{ $languagepack->id }}">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                            </td> 
                            <td>
                                @if($languagepack->user_id == Auth::id())
                                    <a href="/languagepack/users/{{ $languagepack->id }}">
                                        <i class="fa-solid fa-people-group"></i>
                                    </a>
                                @else
                                    <a href="#" onClick="confirmRemoveCollaboration({{ json_encode($languagepack->id) }});">
                                        <i class="fa-solid fa-user-minus"></i>
                                    </a>
                                @endif
                            </td>                             
                            <td>
                                <a href="/languagepack/edit/{{ $languagepack->id }}">
                                    {{ $languagepack->name }} 
                                    @if($languagepack->appId)
                                        ({{ $languagepack->appId }})
                                    @endif
                                </a>
                                @if($languagepack->import_status === ImportStatus::IMPORTING->value)
                                    <span class="text-blue-700 ml-4">{{ __('Import in progress') }}</span>
                                @endif
                                @if($languagepack->import_status === ImportStatus::FAILED->value)
                                    <span class="text-red-500 ml-4">{{ __('Import failed') }}</span>
                                @endif
                            </td> 
                            <td>{{  $languagepack->created_at->format("d/m/Y") }}</td> 
                        </tr>
                        @endforeach
                    </table>                    
                </div>
                @endif

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
</script>
@endsection        