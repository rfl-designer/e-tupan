<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Livewire\Admin;

use App\Domain\Catalog\Models\{Attribute, AttributeValue};
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AttributeManager extends Component
{
    public Attribute $attribute;

    /** @var array<int, array{id: int, value: string, color_hex: ?string, position: int}> */
    public array $values = [];

    #[Validate('required|string|max:255')]
    public string $newValue = '';

    #[Validate('nullable|regex:/^#[0-9A-Fa-f]{6}$/')]
    public ?string $newColorHex = null;

    public ?int $editingValueId = null;

    public string $editingValue = '';

    public ?string $editingColorHex = null;

    /**
     * Mount the component.
     */
    public function mount(Attribute $attribute): void
    {
        $this->attribute = $attribute;
        $this->loadValues();
    }

    /**
     * Load attribute values from database.
     */
    public function loadValues(): void
    {
        $this->values = $this->attribute->values()
            ->orderBy('position')
            ->get()
            ->map(fn (AttributeValue $value) => [
                'id'        => $value->id,
                'value'     => $value->value,
                'color_hex' => $value->color_hex,
                'position'  => $value->position,
            ])
            ->toArray();
    }

    /**
     * Add a new value to the attribute.
     */
    public function addValue(): void
    {
        $rules = [
            'newValue' => ['required', 'string', 'max:255'],
        ];

        if ($this->attribute->isColor()) {
            $rules['newColorHex'] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        $this->validate($rules, [
            'newValue.required'    => 'O valor é obrigatório.',
            'newValue.max'         => 'O valor não pode ter mais de 255 caracteres.',
            'newColorHex.required' => 'A cor é obrigatória para atributos do tipo cor.',
            'newColorHex.regex'    => 'A cor deve estar no formato hexadecimal (ex: #FF0000).',
        ]);

        $maxPosition = $this->attribute->values()->max('position') ?? 0;

        $this->attribute->values()->create([
            'value'     => $this->newValue,
            'color_hex' => $this->attribute->isColor() ? $this->newColorHex : null,
            'position'  => $maxPosition + 1,
        ]);

        $this->newValue    = '';
        $this->newColorHex = null;
        $this->loadValues();

        $this->dispatch('notify', type: 'success', message: 'Valor adicionado!');
    }

    /**
     * Start editing a value.
     */
    public function startEditing(int $valueId): void
    {
        $value = AttributeValue::find($valueId);

        if ($value === null) {
            return;
        }

        $this->editingValueId  = $valueId;
        $this->editingValue    = $value->value;
        $this->editingColorHex = $value->color_hex;
    }

    /**
     * Cancel editing.
     */
    public function cancelEditing(): void
    {
        $this->editingValueId  = null;
        $this->editingValue    = '';
        $this->editingColorHex = null;
    }

    /**
     * Save the edited value.
     */
    public function saveEditing(): void
    {
        if ($this->editingValueId === null) {
            return;
        }

        $rules = [
            'editingValue' => ['required', 'string', 'max:255'],
        ];

        if ($this->attribute->isColor()) {
            $rules['editingColorHex'] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        $this->validate($rules, [
            'editingValue.required'    => 'O valor é obrigatório.',
            'editingValue.max'         => 'O valor não pode ter mais de 255 caracteres.',
            'editingColorHex.required' => 'A cor é obrigatória para atributos do tipo cor.',
            'editingColorHex.regex'    => 'A cor deve estar no formato hexadecimal (ex: #FF0000).',
        ]);

        $attributeValue = AttributeValue::find($this->editingValueId);

        if ($attributeValue === null) {
            $this->cancelEditing();

            return;
        }

        $attributeValue->update([
            'value'     => $this->editingValue,
            'color_hex' => $this->attribute->isColor() ? $this->editingColorHex : null,
        ]);

        $this->cancelEditing();
        $this->loadValues();

        $this->dispatch('notify', type: 'success', message: 'Valor atualizado!');
    }

    /**
     * Delete a value.
     */
    public function deleteValue(int $valueId): void
    {
        $attributeValue = AttributeValue::find($valueId);

        if ($attributeValue === null) {
            $this->dispatch('notify', type: 'error', message: 'Valor não encontrado.');

            return;
        }

        // Check if value is in use by variants
        if ($attributeValue->variants()->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Este valor está em uso por variantes de produtos.');

            return;
        }

        $attributeValue->delete();
        $this->loadValues();

        $this->dispatch('notify', type: 'success', message: 'Valor removido!');
    }

    /**
     * Reorder values based on drag-and-drop.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorderValues(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            AttributeValue::where('id', $id)->update(['position' => $position + 1]);
        }

        $this->loadValues();

        $this->dispatch('notify', type: 'success', message: 'Ordem atualizada!');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.attribute-manager');
    }
}
