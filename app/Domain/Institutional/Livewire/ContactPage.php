<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class ContactPage extends Component
{
    public function render(): View
    {
        return view('livewire.institutional.contact-page')
            ->layout('components.institutional.layout', [
                'title'           => 'TUPAN | Contato',
                'metaTitle'       => 'TUPAN | Fale com nossa equipe técnica',
                'metaDescription' => 'Solicite consultoria técnica e encontre a solução ideal para sua instituição de saúde.',
                'canonicalUrl'    => url('/contato'),
            ]);
    }
}
