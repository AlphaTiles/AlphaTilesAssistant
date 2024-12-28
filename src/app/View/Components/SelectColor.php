<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;

class SelectColor extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public string $key,
        public ?int $color,
        public ?array $errorKeys
    ) {}

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-color');
    }
}
