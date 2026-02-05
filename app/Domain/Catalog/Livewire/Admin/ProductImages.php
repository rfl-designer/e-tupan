<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Livewire\Admin;

use App\Domain\Catalog\Models\{Product, ProductImage};
use App\Domain\Catalog\Services\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;
use Livewire\{Component, WithFileUploads};

class ProductImages extends Component
{
    use WithFileUploads;

    public Product $product;

    /** @var Collection<int, ProductImage> */
    public Collection $images;

    /** @var array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    #[Validate(['newImages.*' => 'image|max:5120'])]
    public array $newImages = [];

    public ?int $editingImageId = null;

    public string $editingAltText = '';

    /**
     * Mount the component.
     */
    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadImages();
    }

    /**
     * Load images from the database.
     */
    public function loadImages(): void
    {
        $this->images = $this->product->images()
            ->productLevel()
            ->orderBy('position')
            ->get();
    }

    /**
     * Upload new images.
     */
    public function uploadImages(): void
    {
        $this->validate([
            'newImages'   => ['required', 'array', 'min:1'],
            'newImages.*' => ['image', 'max:5120'],
        ], [
            'newImages.required' => 'Selecione pelo menos uma imagem.',
            'newImages.*.image'  => 'O arquivo deve ser uma imagem.',
            'newImages.*.max'    => 'Cada imagem deve ter no máximo 5MB.',
        ]);

        $imageService = app(ImageService::class);
        $maxPosition  = $this->product->images()->max('position') ?? 0;
        $isFirst      = $this->images->isEmpty();

        foreach ($this->newImages as $index => $file) {
            $paths = $imageService->store($file);

            $this->product->images()->create([
                'path'       => $paths['large'],
                'alt_text'   => null,
                'position'   => $maxPosition + $index + 1,
                'is_primary' => $isFirst && $index === 0,
            ]);
        }

        $this->newImages = [];
        $this->loadImages();

        $this->dispatch('notify', type: 'success', message: 'Imagens enviadas com sucesso!');
    }

    /**
     * Set an image as primary.
     */
    public function setPrimary(int $imageId): void
    {
        $image = ProductImage::find($imageId);

        if ($image === null || $image->product_id !== $this->product->id) {
            return;
        }

        app(ImageService::class)->setPrimary($image);
        $this->loadImages();

        $this->dispatch('notify', type: 'success', message: 'Imagem principal definida!');
    }

    /**
     * Start editing an image's alt text.
     */
    public function startEditing(int $imageId): void
    {
        $image = ProductImage::find($imageId);

        if ($image === null) {
            return;
        }

        $this->editingImageId = $imageId;
        $this->editingAltText = $image->alt_text ?? '';
    }

    /**
     * Cancel editing.
     */
    public function cancelEditing(): void
    {
        $this->editingImageId = null;
        $this->editingAltText = '';
    }

    /**
     * Save the edited alt text.
     */
    public function saveAltText(): void
    {
        if ($this->editingImageId === null) {
            return;
        }

        $this->validate([
            'editingAltText' => ['nullable', 'string', 'max:255'],
        ], [
            'editingAltText.max' => 'O texto alternativo não pode ter mais de 255 caracteres.',
        ]);

        $image = ProductImage::find($this->editingImageId);

        if ($image === null) {
            $this->cancelEditing();

            return;
        }

        $image->update(['alt_text' => $this->editingAltText ?: null]);
        $this->cancelEditing();
        $this->loadImages();

        $this->dispatch('notify', type: 'success', message: 'Texto alternativo atualizado!');
    }

    /**
     * Delete an image.
     */
    public function deleteImage(int $imageId): void
    {
        $image = ProductImage::find($imageId);

        if ($image === null || $image->product_id !== $this->product->id) {
            $this->dispatch('notify', type: 'error', message: 'Imagem não encontrada.');

            return;
        }

        $wasPrimary = $image->is_primary;

        app(ImageService::class)->deleteProductImage($image);
        $this->loadImages();

        // If deleted image was primary, set first remaining as primary
        if ($wasPrimary && $this->images->isNotEmpty()) {
            $this->setPrimary($this->images->first()->id);
        }

        $this->dispatch('notify', type: 'success', message: 'Imagem removida!');
    }

    /**
     * Reorder images based on drag-and-drop.
     *
     * @param  array<int>  $orderedIds
     */
    public function reorderImages(array $orderedIds): void
    {
        app(ImageService::class)->reorder($orderedIds);
        $this->loadImages();

        $this->dispatch('notify', type: 'success', message: 'Ordem das imagens atualizada!');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.product-images');
    }
}
