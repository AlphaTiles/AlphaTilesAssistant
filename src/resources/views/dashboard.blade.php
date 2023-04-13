@extends('layouts.app')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-title">{{ __('Welcome') }} {{ Auth::user()->name }}</div>

                <div class="mt-5">
                    <a href="/app/create" class="btn btn-primary w-40 mt-1">Create Language Pack</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
