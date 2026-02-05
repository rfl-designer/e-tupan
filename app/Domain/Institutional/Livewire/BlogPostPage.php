<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Component;

class BlogPostPage extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $post = [];

    public function mount(string $slug): void
    {
        $post = $this->findPost($slug);

        if ($post === null) {
            abort(404);
        }

        $this->post = $post;
    }

    public function render(): View
    {
        return view('livewire.institutional.blog-post-page')
            ->layout('components.institutional.layout', [
                'title'           => 'TUPAN | ' . Arr::get($this->post, 'title', 'Blog'),
                'metaTitle'       => 'TUPAN | ' . Arr::get($this->post, 'title', 'Blog'),
                'metaDescription' => Arr::get($this->post, 'excerpt', 'Conteudo tecnico em saude.'),
                'canonicalUrl'    => url()->current(),
            ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findPost(string $slug): ?array
    {
        $posts = config('institutional.blog_posts');

        foreach ($posts as $post) {
            if ($post['id'] === $slug) {
                return $post;
            }
        }

        return null;
    }
}
