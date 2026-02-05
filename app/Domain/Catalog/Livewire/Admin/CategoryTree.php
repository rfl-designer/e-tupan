<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Livewire\Admin;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class CategoryTree extends Component
{
    /**
     * IDs of expanded categories.
     *
     * @var array<int>
     */
    public array $expandedCategories = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        // Expand all categories by default
        $this->expandedCategories = Category::pluck('id')->toArray();
    }

    /**
     * Toggle the expanded state of a category.
     */
    public function toggleExpand(int $categoryId): void
    {
        if (in_array($categoryId, $this->expandedCategories)) {
            $this->expandedCategories = array_values(
                array_diff($this->expandedCategories, [$categoryId]),
            );
        } else {
            $this->expandedCategories[] = $categoryId;
        }
    }

    /**
     * Expand all categories.
     */
    public function expandAll(): void
    {
        $this->expandedCategories = Category::pluck('id')->toArray();
    }

    /**
     * Collapse all categories.
     */
    public function collapseAll(): void
    {
        $this->expandedCategories = [];
    }

    /**
     * Reorder categories based on drag-and-drop.
     *
     * @param  array<int, array{id: int, children?: array<int, array{id: int, children?: array}>}>  $items
     */
    public function reorder(array $items, ?int $parentId = null): void
    {
        $categoryService = app(CategoryService::class);

        foreach ($items as $index => $item) {
            $category = Category::find($item['id']);

            if ($category === null) {
                continue;
            }

            // Validate hierarchy before moving
            if ($parentId !== null) {
                $newParent = Category::find($parentId);

                if ($newParent !== null && !$category->canBeParent($newParent)) {
                    $this->dispatch('notify', type: 'error', message: __('Não é possível mover a categoria para este destino. Limite de 3 níveis.'));

                    return;
                }
            }

            $categoryService->update($category, [
                'parent_id' => $parentId,
                'position'  => $index,
            ]);

            if (!empty($item['children'])) {
                $this->reorder($item['children'], $item['id']);
            }
        }

        $this->dispatch('notify', type: 'success', message: __('Categorias reordenadas com sucesso!'));
    }

    /**
     * Delete a category.
     */
    public function delete(int $categoryId): void
    {
        $categoryService = app(CategoryService::class);
        $category        = Category::find($categoryId);

        if ($category === null) {
            $this->dispatch('notify', type: 'error', message: __('Categoria não encontrada.'));

            return;
        }

        try {
            $categoryService->delete($category);
            $this->dispatch('notify', type: 'success', message: __('Categoria excluída com sucesso!'));
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Refresh the component when a category is reordered.
     */
    #[On('category-reordered')]
    public function refresh(): void
    {
        // Component will re-render automatically
    }

    /**
     * Get the categories tree.
     *
     * @return Collection<int, Category>
     */
    private function getCategories(): Collection
    {
        return app(CategoryService::class)->getTree();
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.category-tree', [
            'categories' => $this->getCategories(),
        ]);
    }
}
