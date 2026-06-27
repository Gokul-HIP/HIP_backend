<?php

namespace App\Livewire\Admin\Organization\Pharmacy\CatalogProducts;

use App\Services\CatalogProductService;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public int $pharmacyId;

    public string $product_name = '';

    public ?string $mrp = null;

    public ?string $selling_price = null;

    public ?string $discount = null;

    public string $description = '';

    public bool $in_stock = true;

    public bool $status = true;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newImages = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $stagedImages = [];

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
        $this->resetBenefits();
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
            'stagedImages.*' => 'nullable|image|max:4096',
            'benefits.*.title' => 'nullable|string|max:255',
            'benefits.*.icon' => 'nullable|string|max:255',
            'benefits.*.display_order' => 'nullable|integer|min:0',
            'benefits.*.icon_upload' => 'nullable|image|max:2048',
        ];
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

    public function updatedNewImages(): void
    {
        if ($this->newImages === []) {
            return;
        }

        $this->validateOnly('newImages.*');

        foreach ($this->newImages as $image) {
            if ($image) {
                $this->stagedImages[] = $image;
            }
        }

        $this->newImages = [];
        $this->dispatch('reset-catalog-file-input');
    }

    public function removeNewImage(int $index): void
    {
        unset($this->stagedImages[$index]);
        $this->stagedImages = array_values($this->stagedImages);
    }

    public function saveProduct(): void
    {
        $this->validate();

        $benefitIconFiles = collect($this->benefits)
            ->map(fn ($benefit) => $benefit['icon_upload'] ?? null)
            ->all();

        $this->catalogProductService->createProduct(
            $this->pharmacyId,
            [
                'product_name' => $this->product_name,
                'mrp' => $this->mrp !== null && $this->mrp !== '' ? $this->mrp : null,
                'selling_price' => $this->selling_price !== null && $this->selling_price !== '' ? $this->selling_price : null,
                'discount' => $this->discount !== null && $this->discount !== '' ? $this->discount : 0,
                'description' => $this->description !== '' ? $this->description : null,
                'in_stock' => $this->in_stock,
                'status' => $this->status,
            ],
            $this->stagedImages,
            $this->benefits,
            $benefitIconFiles
        );

        Flux::modal('add-catalog-product')->close();
        $this->resetInput();
        $this->dispatch('refresh-catalog-products');
        $this->dispatch('toast', type: 'success', message: 'Product added successfully.');
    }

    public function closeModal(): void
    {
        $this->resetInput();
        Flux::modal('add-catalog-product')->close();
    }

    public function resetInput(): void
    {
        $this->reset([
            'product_name',
            'mrp',
            'selling_price',
            'discount',
            'description',
            'newImages',
            'stagedImages',
        ]);
        $this->in_stock = true;
        $this->status = true;
        $this->resetBenefits();
        $this->resetValidation();
        $this->dispatch('reset-catalog-images');
        $this->dispatch('reset-benefit-icons');
    }

    protected function resetBenefits(): void
    {
        $this->benefits = [
            [
                'title' => '',
                'icon' => '',
                'display_order' => 1,
                'icon_upload' => null,
                'remove_icon' => false,
            ],
        ];
        $this->dispatch('reset-benefit-icons');
    }

    public function render()
    {
        return view('livewire.admin.organization.pharmacy.catalog-products.create');
    }
}
