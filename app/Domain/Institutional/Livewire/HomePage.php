<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomePage extends Component
{
    public function render(): View
    {
        return view('livewire.institutional.home-page')
            ->layout('components.institutional.layout', [
                'title'           => 'TUPAN | Home',
                'metaTitle'       => 'TUPAN | Solucoes tecnicas em saude',
                'metaDescription' => 'Distribuicao e tecnologia para saude com suporte tecnico, consultoria especializada e cobertura no Nordeste.',
                'canonicalUrl'    => url('/'),
            ]);
    }
}
