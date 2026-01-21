<?php

namespace App\Livewire\Admin\Organization\Pharmacy\Products;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\PharmacyProductService;
use Livewire\Attributes\On;
use Flux\Flux;

class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public string $status = 'all';
    public string $location = 'all';

    public int $pharmacyId;
    public int $totalProducts = 0;
    public int $activeProducts = 0;
    public $product_id;

    protected $pharmacyProductService;

    public function boot(PharmacyProductService $pharmacyProductService)
    {
        $this->pharmacyProductService = $pharmacyProductService;
    }

    public function mount($pharmacyId)
    {
        $this->pharmacyId = (int) $pharmacyId;
        $this->loadCounts();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function editProduct($id)
    {
        $this->dispatch('editProduct', id: $id);
    }

    public function deleteProduct($id){

        $this->product_id = $id;
        Flux::modal('delete-pharmacy-product')->show();
    }

    public function closeModal()
    {
        Flux::modal('delete-pharmacy-product')->close();
    }

    public function destroy()
    {
        $this->pharmacyProductService->deleteProduct($this->product_id);
        Flux::modal('delete-pharmacy-product')->close();
        $this->loadCounts();
        $this->dispatch('toast', type: 'success', message: 'Product deleted successfully');
    }

    public function loadCounts(): void
    {
        $counts = $this->pharmacyProductService->getProductCounts($this->pharmacyId);
        $this->totalProducts = $counts['total'];
        $this->activeProducts = $counts['active'];
    }

    #[On('refresh-products')]
    public function relodePage(){

        $this->render();

    }

    public function render()
    {
        $filters = [
            'search' => $this->search,
            'status' => $this->status,
        ];

        $pharmacyProducts = $this->pharmacyProductService->getProductsPaginated($this->pharmacyId, $filters, 10);

        return view('livewire.admin.organization.pharmacy.products.index', compact(
            'pharmacyProducts'
        ));
    }
}
