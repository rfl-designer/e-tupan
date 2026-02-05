<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class BlogListPage extends Component
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPosts(): array
    {
        return config('institutional.blog_posts');
    }

    public function render(): View
    {
        return view('livewire.institutional.blog-list-page', [
            'posts' => $this->getPosts(),
        ])->layout('components.institutional.layout', [
            'title'           => 'TUPAN | Blog',
            'metaTitle'       => 'TUPAN | Conteudos tecnicos em saude',
            'metaDescription' => 'Artigos e insights tecnicos para apoiar decisoes seguras em saude.',
            'canonicalUrl'    => url('/blog'),
        ]);
    }
}
