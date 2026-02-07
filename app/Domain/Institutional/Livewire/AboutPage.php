<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class AboutPage extends Component
{
    public function render(): View
    {
        return view('livewire.institutional.about-page')
            ->layout('components.institutional.layout', [
                'title'           => 'TUPAN | Sobre',
                'metaTitle'       => 'TUPAN | Nossa história',
                'metaDescription' => 'Conheça a trajetória da TUPAN: 16 anos de autoridade técnica, responsabilidade e entrega consistente em saúde.',
                'canonicalUrl'    => url('/sobre'),
            ]);
    }
}
