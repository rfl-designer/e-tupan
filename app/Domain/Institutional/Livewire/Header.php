<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Header extends Component
{
    public function render(): View
    {
        return view('livewire.institutional.header');
    }
}
