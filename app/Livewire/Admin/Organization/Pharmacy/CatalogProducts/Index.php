<?php

namespace App\Livewire\Admin\Organization\Pharmacy\CatalogProducts;

use App\Models\Pharmacy;
use App\Services\CatalogProductService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public int $pharmacyId;

    public string $search = '';

    public string $status = 'all';

    public string $inStock = 'all';

    public ?int $deleteProductId = null;

    public int $totalProducts = 0;

    public int $activeProducts = 0;

    protected CatalogProductService $catalogProductService;

    public function boot(CatalogProductService $catalogProductService): void
    {
        $this->catalogProductService = $catalogProductService;
    }

    public function mount(int $pharmacyId): void
    {
        $this->pharmacyId = $pharmacyId;
        $this->loadCounts();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingInStock(): void
    {
        $this->resetPage();
    }

    public function editProduct(int $id): void
    {
        $this->dispatch('editCatalogProduct', id: $id);
    }

    public function deleteProduct(int $id): void
    {
        $this->deleteProductId = $id;
        Flux::modal('delete-catalog-product')->show();
    }

    public function closeModal(): void
    {
        $this->deleteProductId = null;
        Flux::modal('delete-catalog-product')->close();
    }

    public function destroy(): void
    {
        if (! $this->deleteProductId) {
            return;
        }

        $this->catalogProductService->deleteProduct($this->deleteProductId);
        $this->closeModal();
        $this->loadCounts();
        $this->dispatch('toast', type: 'success', message: 'Product deleted successfully.');
    }

    #[On('refresh-catalog-products')]
    public function refreshList(): void
    {
        $this->loadCounts();
        $this->resetPage();
    }

    protected function loadCounts(): void
    {
        $counts = $this->catalogProductService->getCounts($this->pharmacyId);
        $this->totalProducts = $counts['total'];
        $this->activeProducts = $counts['active'];
    }

    public function render()
    {
        $pharmacy = Pharmacy::find($this->pharmacyId);

        $products = $this->catalogProductService->getProductsPaginated($this->pharmacyId, [
            'search' => $this->search,
            'status' => $this->status,
            'in_stock' => $this->inStock,
        ], 10);

        return view('livewire.admin.organization.pharmacy.catalog-products.index', [
            'pharmacy' => $pharmacy,
            'products' => $products,
        ]);
    }
}
