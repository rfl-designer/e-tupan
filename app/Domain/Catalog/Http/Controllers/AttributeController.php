<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Http\Controllers;

use App\Domain\Catalog\Enums\AttributeType;
use App\Domain\Catalog\Http\Requests\{StoreAttributeRequest, UpdateAttributeRequest};
use App\Domain\Catalog\Models\Attribute;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttributeController extends Controller
{
    /**
     * Display a listing of attributes.
     */
    public function index(): View
    {
        $attributes = Attribute::query()
            ->withCount('values')
            ->orderBy('position')
            ->get();

        return view('admin.attributes.index', compact('attributes'));
    }

    /**
     * Show the form for creating a new attribute.
     */
    public function create(): View
    {
        $types = AttributeType::options();

        return view('admin.attributes.create', compact('types'));
    }

    /**
     * Store a newly created attribute in storage.
     */
    public function store(StoreAttributeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Set position to last
        $data['position'] = Attribute::max('position') + 1;

        Attribute::create($data);

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Atributo criado com sucesso!');
    }

    /**
     * Show the form for editing the specified attribute.
     */
    public function edit(Attribute $attribute): View
    {
        $types = AttributeType::options();
        $attribute->load('values');

        return view('admin.attributes.edit', compact('attribute', 'types'));
    }

    /**
     * Update the specified attribute in storage.
     */
    public function update(UpdateAttributeRequest $request, Attribute $attribute): RedirectResponse
    {
        $attribute->update($request->validated());

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Atributo atualizado com sucesso!');
    }

    /**
     * Remove the specified attribute from storage.
     */
    public function destroy(Attribute $attribute): RedirectResponse
    {
        // Check if attribute is in use by products via product_attributes table
        $inUse = \Illuminate\Support\Facades\DB::table('product_attributes')
            ->where('attribute_id', $attribute->id)
            ->exists();

        if ($inUse) {
            return back()->withErrors(['error' => 'Não é possível excluir um atributo que está em uso por produtos.']);
        }

        $attribute->delete();

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Atributo excluído com sucesso!');
    }
}
