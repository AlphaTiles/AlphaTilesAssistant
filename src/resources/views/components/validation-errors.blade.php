<?php
use Illuminate\Support\Str;
use App\Enums\ErrorTypeEnum;
?>
@if (count($errors) > 0)
	<h2 class="text-2xl mt-2 mb-2 text-red-500">Validation Errors</h2>
	@php
		$previousTabName = null;
	@endphp
	@foreach ($errors as $errorType => $errorGroup)
	@php
		$typeEnum = ErrorTypeEnum::from($errorType);
		$currentTabName = $typeEnum->tab()->name();
	@endphp
	@if ($currentTabName !== $previousTabName && empty($tab))
		<h3 class="text-xl mt-1 mb-1">{{ Str::plural($currentTabName) }}</h3>
	@endif
    <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box peer-checked:bg-secondary peer-checked:text-secondary-content">
        <input type="checkbox" class="peer" />
        <div class="collapse-title text-xl font-medium text-red-500">
            {{ $typeEnum->label() }}
        </div>
        <div class="collapse-content">
            @foreach ($errorGroup as $error)
                <div>
                    <a href="/languagepack/{{ $typeEnum->tab()->path() }}/{{ $languagePack->id }}/{{ $error['value'] }}">
                        {{ $error['value'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
	@php
        $previousTabName = $currentTabName;
    @endphp	
	@endforeach	

	@endif