<?php
use Illuminate\Support\Str;
use App\Enums\ErrorTypeEnum;
use App\Enums\ErrorLevelEnum;
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
		$level = $typeEnum->level();
		$levelTagClasses = match ($level) {
			ErrorLevelEnum::CRITICAL => 'bg-red-100 text-red-800 border border-red-300',
			ErrorLevelEnum::WARNING => 'bg-amber-100 text-amber-800 border border-amber-300',
			ErrorLevelEnum::RECOMMENDATION => 'bg-sky-100 text-sky-800 border border-sky-300',
		};
		$titleColorClasses = match ($level) {
			ErrorLevelEnum::CRITICAL => 'text-red-600',
			ErrorLevelEnum::WARNING => 'text-amber-700',
			ErrorLevelEnum::RECOMMENDATION => 'text-sky-700',
		};
	@endphp
	@if ($currentTabName !== $previousTabName && empty($tab))
		<h3 class="text-xl mt-1 mb-1">{{ Str::plural($currentTabName) }}</h3>
	@endif
    <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
        <input type="checkbox" class="peer" />
        <div class="collapse-title text-xl font-medium {{ $titleColorClasses }} flex items-center gap-3">
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold tracking-wide uppercase {{ $levelTagClasses }}">
                {{ $level->value }}
            </span>
            <span>{{ $typeEnum->label() }}</span>
        </div>
        <div class="collapse-content">
            @foreach ($errorGroup as $error)
                <div class="py-1">
                    @if(!empty($error['value']))
                        @if($typeEnum->isLinkable())
                        <a href="/languagepack/{{ $typeEnum->tab()->path() }}/{{ $languagePack->id }}/{{ $error['value'] }}" class="link link-hover">
                            {{ $error['value'] }}
                        </a>
                        @else
                            {{ $error['value'] }}
                        @endif
                    @else
                        <span class="italic text-sm text-base-content/70">No row-specific value for this message.</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
	@php
        $previousTabName = $currentTabName;
    @endphp	
	@endforeach	

	@endif