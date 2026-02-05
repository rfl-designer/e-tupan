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
                'metaTitle'       => 'TUPAN | Nossa historia',
                'metaDescription' => 'Conheca a trajetoria da TUPAN: 16 anos de autoridade tecnica, responsabilidade e entrega consistente em saude.',
                'canonicalUrl'    => url('/sobre'),
            ]);
    }
}
