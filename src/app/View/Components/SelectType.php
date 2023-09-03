<?php

namespace App\View\Components;

use App\Models\Tile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;

class SelectType extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public string $key,
        public object $tile,
        public ?array $errorKeys
    ) {}

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-type');
    }
}
