@extends('layouts.app')

@section('content')	

<div class="prose text-center max-w-full">
    <h1 class="mb-0 sm:text-6xl  text-4xl text-slate-500">Alpha Tiles Assistant</h1>
    <p class="text-lg">
     {{ __('Web app for adding all the data needed for creating the mobile app') }}
    </p>
    <p class="mb-5">
        <a href="https://alphatilesapps.org/contact.html">{{ __('Contact us') }}</a> {{ __('to get started. We will provide you with login details.') }}
    </p>
</div>
		
@endsection
