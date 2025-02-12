<?php

namespace App\View\Components;

use App\Enums\TabEnum;
use App\Models\LanguagePack;
use Illuminate\View\Component;

class ValidationErrors extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public LanguagePack $languagePack,
        public array $errors,
        public ?TabEnum $tab = null
    ) {}

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.validation-errors');
    }
}
