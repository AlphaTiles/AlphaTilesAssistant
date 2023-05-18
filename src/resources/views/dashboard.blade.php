@extends('layouts.app')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-title">{{ __('Welcome') }} {{ Auth::user()->name }}</div>

                <div class="mt-5">
                    <a href="/languagepack/create" class="btn btn-primary w-40 mt-1">Create Language Pack</a>
                </div>

                @if(count($languagepacks) > 0)
                <div class="overflow-x-auto mt-5 max-w-3xl">
                    <table class="table table-compact w-full">
                        <colgroup>
                            <col span="1" style="width: 5%;">
                            <col span="1" style="width: 70%;">
                            <col span="1" style="width: 15%;">
                        </colgroup>                        
                        <thead>
                        <tr>
                            <th>Edit</th> 
                            <th>Name</th> 
                            <th>Date Created</th> 
                        </tr>
                        </thead> 
                        <tbody>
                        @foreach($languagepacks as $languagepack)
                        <tr>
                            <td>
                                <a href="/languagepack/edit/{{ $languagepack->id }}">
                                    <img src="/images/edit.svg" width=25>
                                </a>
                            </td> 
                            <td>
                                <a href="/languagepack/{{ $languagepack->id }}">
                                    {{ $languagepack->name }}
                                </a>
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
