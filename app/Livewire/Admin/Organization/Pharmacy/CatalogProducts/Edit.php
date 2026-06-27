<?php

namespace App\Livewire\Admin\Organization\Pharmacy\CatalogProducts;

use App\Services\CatalogProductService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public int $pharmacyId;

    public ?int $product_id = null;

    public string $product_name = '';

    public ?string $mrp = null;

    public ?string $selling_price = null;

    public ?string $discount = null;

    public string $description = '';

    public bool $in_stock = true;

    public bool $status = true;

    /** @var array<int, string> */
    public array $existingImages = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newImages = [];

    /** @var array<int, array{id?: int|null, title: string, icon: string, display_order: int, icon_upload: mixed}> */
    public array $benefits = [];

    protected CatalogProductService $catalogProductService;

    public function boot(CatalogProductService $catalogProductService): void
    {
        $this->catalogProductService = $catalogProductService;
    }

    public function mount(int $pharmacyId): void
    {
        $this->pharmacyId = $pharmacyId;
    }

    protected function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'mrp' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string|max:5000',
            'in_stock' => 'boolean',
            'status' => 'boolean',
            'newImages.*' => 'nullable|image|max:4096',
            'benefits.*.title' => 'nullable|string|max:255',
            'benefits.*.icon' => 'nullable|string|max:255',
            'benefits.*.display_order' => 'nullable|integer|min:0',
            'benefits.*.icon_upload' => 'nullable|image|max:2048',
        ];
    }

    #[On('editCatalogProduct')]
    public function loadProduct(int $id): void
    {
        $this->resetInput();

        $product = $this->catalogProductService->findProduct($id);

        if ((int) $product->pharmacy_id !== $this->pharmacyId) {
            $this->dispatch('toast', type: 'error', message: 'Product not found for this pharmacy.');

            return;
        }

        $this->product_id = $product->id;
        $this->product_name = $product->product_name;
        $this->mrp = $product->mrp !== null ? (string) $product->mrp : null;
        $this->selling_price = $product->selling_price !== null ? (string) $product->selling_price : null;
        $this->discount = $product->discount !== null ? (string) $product->discount : null;
        $this->description = $product->description ?? '';
        $this->in_stock = (bool) $product->in_stock;
        $this->status = (bool) $product->status;
        $this->existingImages = array_values($product->images ?? []);

        $this->benefits = $product->productBenefits
            ->sortBy('display_order')
            ->values()
            ->map(fn ($benefit) => [
                'id' => $benefit->id,
                'title' => $benefit->title ?? '',
                'icon' => $benefit->icon ?? '',
                'display_order' => (int) ($benefit->display_order ?? 0),
                'icon_upload' => null,
                'remove_icon' => false,
            ])
            ->all();

        if ($this->benefits === []) {
            $this->benefits = [
                [
                    'title' => '',
                    'icon' => '',
                    'display_order' => 1,
                    'icon_upload' => null,
                    'remove_icon' => false,
                ],
            ];
        }

        Flux::modal('edit-catalog-product')->show();
    }

    public function addBenefitRow(): void
    {
        $this->benefits[] = [
            'title' => '',
            'icon' => '',
            'display_order' => count($this->benefits) + 1,
            'icon_upload' => null,
            'remove_icon' => false,
        ];
    }

    public function clearBenefitIcon(int $index): void
    {
        if (! isset($this->benefits[$index])) {
            return;
        }

        $this->benefits[$index]['icon_upload'] = null;
        $this->benefits[$index]['icon'] = '';
        $this->benefits[$index]['remove_icon'] = true;
    }

    public function removeBenefitRow(int $index): void
    {
        unset($this->benefits[$index]);
        $this->benefits = array_values($this->benefits);
    }

    public function removeExistingImage(int $index): void
    {
        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    public function removeNewImage(int $index): void
    {
        unset($this->newImages[$index]);
        $this->newImages = array_values($this->newImages);
    }

    public function updateProduct(): void
    {
        $this->validate();

        if (! $this->product_id) {
            return;
        }

        $benefitIconFiles = collect($this->benefits)
            ->map(fn ($benefit) => $benefit['icon_upload'] ?? null)
            ->all();

        $this->catalogProductService->updateProduct(
            $this->product_id,
            [
                'product_name' => $this->product_name,
                'mrp' => $this->mrp !== null && $this->mrp !== '' ? $this->mrp : null,
                'selling_price' => $this->selling_price !== null && $this->selling_price !== '' ? $this->selling_price : null,
                'discount' => $this->discount !== null && $this->discount !== '' ? $this->discount : 0,
                'description' => $this->description !== '' ? $this->description : null,
                'in_stock' => $this->in_stock,
                'status' => $this->status,
            ],
            $this->newImages,
            $this->existingImages,
            $this->benefits,
            $benefitIconFiles
        );

        Flux::modal('edit-catalog-product')->close();
        $this->resetInput();
        $this->dispatch('refresh-catalog-products');
        $this->dispatch('toast', type: 'success', message: 'Product updated successfully.');
    }

    public function closeModal(): void
    {
        $this->resetInput();
        Flux::modal('edit-catalog-product')->close();
    }

    public function resetInput(): void
    {
        $this->reset([
            'product_id',
            'product_name',
            'mrp',
            'selling_price',
            'discount',
            'description',
            'existingImages',
            'newImages',
            'benefits',
        ]);
        $this->in_stock = true;
        $this->status = true;
        $this->resetValidation();
        $this->dispatch('reset-catalog-images');
        $this->dispatch('reset-benefit-icons');
    }

    public function render()
    {
        return view('livewire.admin.organization.pharmacy.catalog-products.edit');
    }
}
