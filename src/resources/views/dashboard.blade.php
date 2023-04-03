@extends('layouts.app')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-title">{{ __('Welcome') }} {{ Auth::user()->name }}</div>

                <div class="mt-5">

                    <a href="/app/create" class="btn btn-primary w-40 mt-1">Create</a>


                    @if(count($apps) > 0)
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
                            @foreach($apps as $app)
                            <tr>
                                <td>
                                    <a href="/app/edit/{{ $app->id }}">
                                        <img src="/images/edit.svg" width=25>
                                    </a>
                                </td> 
                                <td>
                                    {{ $app->name }}
                                </td> 
                                <td>{{  $app->created_at->format("d/m/Y") }}</td> 
                            </tr>
                            @endforeach
                        </table>                    
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
