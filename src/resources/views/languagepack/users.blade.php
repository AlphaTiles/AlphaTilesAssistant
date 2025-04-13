@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
    <div x-data="{ showMessage: true }" x-show="showMessage" x-init="setTimeout(() => showMessage = false, 3000)">
		@if (session()->has('success'))
		<div class="p-3 text-green-700 bg-green-300 rounded">
			{{ session()->get('success') }}
		</div>
		@endif
	</div>	

    <div>
        @if (session()->has('error'))
		<div class="p-3 text-red-700 bg-red-300 rounded">
			{{ session()->get('error') }}
		</div>
		@endif        

        @if ($errors->any())
        <div class="p-3 text-red-700 bg-red-300 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif    
    </div>

    <h1 class="text-2xl font-bold mb-6">Manage Users for {{ $languagepack->name }}</h1>

    <div class="mb-4">
        <h2 class="text-xl mb-4">Add Collaborator</h2>
        <form action="{{ route('languagepack.addUser', $languagepack) }}" method="POST" class="flex gap-4">
            @csrf
            <input type="email" 
                   name="email" 
                   placeholder="Enter user email" 
                   class="form-input flex-1"
                   required>
            <button type="submit" class="btn btn-primary">Add User</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $languagepack->owner->name }}</td>
                    <td>{{ $languagepack->owner->email }}</td>
                    <td>Owner</td>
                    <td>-</td>
                </tr>
                @foreach($collaborators as $collaborator)
                <tr>
                    <td>{{ $collaborator->user->name }}</td>
                    <td>{{ $collaborator->user->email }}</td>
                    <td>Collaborator</td>
                    <td>
                        <form action="{{ route('languagepack.removeUser', [$languagepack, $collaborator->user]) }}" 
                              method="POST" 
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to remove this user?')">
                                Remove
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
		<a href="/dashboard">Back to Dashboard</a>
	</div>

</div>

@endsection