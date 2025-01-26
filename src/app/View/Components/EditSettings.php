<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;

class EditSettings extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public ?int $languagePackId,
        public array $settings,
        public string $repositoryClass,
        public bool $showNext,
        public string $formPath,
        public string $nextPath,
        public ?string $backPath = null
    ) {}

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.edit-settings');
    }
}
