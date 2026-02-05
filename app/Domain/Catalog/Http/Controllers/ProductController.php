<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Http\Controllers;

use App\Domain\Catalog\Http\Requests\{StoreProductRequest, UpdateProductRequest};
use App\Domain\Catalog\Models\{Category, Product, Tag};
use App\Domain\Catalog\Services\ProductService;
use App\Http\Controllers\Controller;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {
    }

    /**
     * Display a listing of products.
     */
    public function index(): View
    {
        return view('admin.products.index');
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('position')
            ->get();

        $tags = Tag::orderBy('name')->get();

        return view('admin.products.create', compact('categories', 'tags'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Convert price from reais to centavos
        $data['price'] = (int) round(($data['price'] ?? 0) * 100);

        if (isset($data['sale_price'])) {
            $data['sale_price'] = (int) round($data['sale_price'] * 100);
        }

        if (isset($data['cost'])) {
            $data['cost'] = (int) round($data['cost'] * 100);
        }

        $product = $this->productService->create($data);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Produto criado com sucesso!');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $product->load([
            'categories',
            'tags',
            'images',
            'variants.attributeValues.attribute',
            'variants.images',
        ]);

        $categories = Category::query()
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('position')
            ->get();

        $tags = Tag::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories', 'tags'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        // Convert price from reais to centavos
        $data['price'] = (int) round(($data['price'] ?? 0) * 100);

        if (isset($data['sale_price'])) {
            $data['sale_price'] = (int) round($data['sale_price'] * 100);
        }

        if (isset($data['cost'])) {
            $data['cost'] = (int) round($data['cost'] * 100);
        }

        $this->productService->update($product, $data);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Produto atualizado com sucesso!');
    }

    /**
     * Remove the specified product from storage (soft delete).
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produto movido para a lixeira!');
    }

    /**
     * Display a listing of trashed products.
     */
    public function trash(): View
    {
        return view('admin.products.trash');
    }

    /**
     * Restore the specified product from trash.
     */
    public function restore(Product $product): RedirectResponse
    {
        $this->productService->restore($product);

        return redirect()->route('admin.products.trash')
            ->with('success', 'Produto restaurado com sucesso!');
    }

    /**
     * Permanently delete the specified product.
     */
    public function forceDelete(Product $product): RedirectResponse
    {
        $this->productService->forceDelete($product);

        return redirect()->route('admin.products.trash')
            ->with('success', 'Produto excluído permanentemente!');
    }

    /**
     * Duplicate the specified product.
     */
    public function duplicate(Product $product): RedirectResponse
    {
        $newProduct = $this->productService->duplicate($product);

        return redirect()->route('admin.products.edit', $newProduct)
            ->with('success', 'Produto duplicado com sucesso!');
    }

    /**
     * Perform bulk actions on products.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action'     => ['required', 'string', 'in:activate,deactivate,delete,restore,force_delete'],
            'products'   => ['required', 'array'],
            'products.*' => ['required', 'integer'],
        ], [
            'action.required'   => 'A ação é obrigatória.',
            'action.in'         => 'Ação inválida.',
            'products.required' => 'Selecione pelo menos um produto.',
        ]);

        $productIds = $validated['products'];

        $count = match ($validated['action']) {
            'activate'     => $this->productService->bulkUpdateStatus($productIds, 'active'),
            'deactivate'   => $this->productService->bulkUpdateStatus($productIds, 'inactive'),
            'delete'       => $this->productService->bulkDelete($productIds),
            'restore'      => $this->productService->bulkRestore($productIds),
            'force_delete' => $this->bulkForceDelete($productIds),
        };

        $actionLabels = [
            'activate'     => 'ativados',
            'deactivate'   => 'desativados',
            'delete'       => 'movidos para a lixeira',
            'restore'      => 'restaurados',
            'force_delete' => 'excluídos permanentemente',
        ];

        return back()->with('success', "{$count} produtos {$actionLabels[$validated['action']]}!");
    }

    /**
     * Bulk force delete products.
     *
     * @param  array<int>  $productIds
     */
    private function bulkForceDelete(array $productIds): int
    {
        $count    = 0;
        $products = Product::onlyTrashed()->whereIn('id', $productIds)->get();

        foreach ($products as $product) {
            $this->productService->forceDelete($product);
            $count++;
        }

        return $count;
    }
}
