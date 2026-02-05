<?php

declare(strict_types = 1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    /**
     * Create a new component instance.
     *
     * @param  array<int, array{label: string, url?: string}>  $breadcrumbs
     */
    public function __construct(
        public string $title = 'Admin',
        public array $breadcrumbs = [],
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('admin.layouts.app');
    }
}
