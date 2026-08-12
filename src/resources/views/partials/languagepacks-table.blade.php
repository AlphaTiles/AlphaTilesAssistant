@php
use App\Enums\ImportStatus;
@endphp

@if(count($languagepacks) > 0)
<div class="overflow-x-auto mt-5 max-w-3xl">
    <table class="table table-compact w-full">
        <colgroup>
            @if($showOwnerColumn ?? false)
                <col span="1" style="width: 5%;">
                <col span="1" style="width: 55%;">
                <col span="1" style="width: 25%;">
                <col span="1" style="width: 15%;">
            @else
                <col span="1" style="width: 5%;">
                <col span="1" style="width: 5%;">
                <col span="1" style="width: 70%;">
                <col span="1" style="width: 15%;">
            @endif
        </colgroup>
        <thead>
        <tr>
            <th>{{ __('Edit') }}</th>
            @if($showOwnerColumn ?? false)
                <th>{{ __('Name') }}</th>
                <th>{{ __('Owner') }}</th>
                <th>{{ __('Date Created') }}</th>
            @else
                <th>{{ __('Users') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Date Created') }}</th>
            @endif
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
            @if($showOwnerColumn ?? false)
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
                <td>{{ $languagepack->owner->name ?? '' }} ({{ $languagepack->owner->email ?? '' }})</td>
                <td>{{ $languagepack->created_at->format("d/m/Y") }}</td>
            @else
                <td>
                    @php
                        $isAdminUser = Auth::user()->isAdmin() || strtolower(Auth::user()->email ?? '') === strtolower(env('ADMIN_EMAIL', ''));
                    @endphp
                    @if($languagepack->user_id == Auth::id() || $isAdminUser)
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
                <td>{{ $languagepack->created_at->format("d/m/Y") }}</td>
            @endif
        </tr>
        @endforeach
    </table>
</div>
@endif
