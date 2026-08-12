<?php
use App\Enums\ImportStatus;
?>
@extends('layouts.app')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-title">{{ __('All Language Packs') }}</div>

                @include('partials.languagepacks-table', ['languagepacks' => $languagepacks, 'showOwnerColumn' => true])

            </div>
        </div>
    </div>
</div>
@endsection
