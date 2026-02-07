<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Component;

class DivisionPage extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $division = [];

    public function mount(string $slug): void
    {
        $division = $this->findDivision($slug);

        if ($division === null) {
            abort(404);
        }

        $this->division = $division;
    }

    public function render(): View
    {
        return view('livewire.institutional.division-page')
            ->layout('components.institutional.layout', [
                'title'           => 'TUPAN | ' . Arr::get($this->division, 'title', 'Divisão'),
                'metaTitle'       => 'TUPAN | ' . Arr::get($this->division, 'title', 'Divisão'),
                'metaDescription' => Arr::get($this->division, 'description', 'Solução técnica em saúde.'),
                'canonicalUrl'    => url()->current(),
            ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findDivision(string $slug): ?array
    {
        $divisions = config('institutional.divisions');

        foreach ($divisions as $division) {
            if ($division['id'] === $slug) {
                return $division;
            }
        }

        return null;
    }
}
