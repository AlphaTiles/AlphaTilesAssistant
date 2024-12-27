<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SelectFile extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public int $nr,        
        public string $key,
        public string $prefix,
        public object $item,
        public ?array $errorKeys
    ) {}
    
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-file');
    }
}
