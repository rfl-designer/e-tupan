<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Http\Controllers;

use App\Domain\Catalog\Http\Requests\{ReorderCategoryRequest, StoreCategoryRequest, UpdateCategoryRequest};
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Services\CategoryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
    ) {
    }

    /**
     * Display a listing of categories in tree format.
     */
    public function index(): View
    {
        $categories = $this->categoryService->getTree();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View
    {
        $parentCategories = $this->categoryService->getFlatList()
            ->filter(fn (array $item) => $item['depth'] < Category::MAX_DEPTH - 1);

        return view('admin.categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            // Handle image upload
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            $this->categoryService->create($data);

            return redirect()->route('admin.categories.index')
                ->with('success', 'Categoria criada com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['parent_id' => $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category): View
    {
        $parentCategories = $this->categoryService->getFlatList()
            ->filter(function (array $item) use ($category) {
                // Exclude the category itself and its descendants
                if ($item['id'] === $category->id) {
                    return false;
                }

                // Only allow categories that can have children (depth < MAX_DEPTH - 1)
                return $item['depth'] < Category::MAX_DEPTH - 1;
            });

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $data = $request->validated();

            // Handle image upload
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            $this->categoryService->update($category, $data);

            return redirect()->route('admin.categories.index')
                ->with('success', 'Categoria atualizada com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['parent_id' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        try {
            $this->categoryService->delete($category);

            return redirect()->route('admin.categories.index')
                ->with('success', 'Categoria excluída com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Reorder categories.
     */
    public function reorder(ReorderCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->reorder($request->validated('order'));

        return redirect()->route('admin.categories.index')
            ->with('success', 'Ordem das categorias atualizada com sucesso!');
    }
}
