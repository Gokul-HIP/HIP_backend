<?php

namespace App\Livewire\Admin\Organization\Pharmacy\Products;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\PharmacyProductService;
use Flux\Flux;

class Create extends Component
{
    use WithFileUploads;

    public $pharmacy_id;

    public $product_name;
    public $product_code;
    public $category;
    public $brand_name;
    public $dosage_form;
    public $strength;
    public $pack_size;

    public $mrp;
    public $selling_price;
    public $discount;

    public $stock_quantity;
    public $expiry_date;
    public $batch_number;
    public bool $prescription_required = false;

    public $product_image;
    public $product_description;
    public $status = false;

    protected $pharmacyProductService;

    public function boot(PharmacyProductService $pharmacyProductService)
    {
        $this->pharmacyProductService = $pharmacyProductService;
    }

    protected $rules = [
        'product_name' => 'required|string|max:255',
        'product_code' => 'required|string|max:100|unique:pharmacy_products,product_code',
        'category' => 'nullable|string|max:100',
        'brand_name' => 'nullable|string|max:100',
        'dosage_form' => 'nullable|string|max:100',
        'strength' => 'nullable|string|max:100',
        'pack_size' => 'nullable|string|max:100',

        'mrp' => 'nullable|numeric|min:0',
        'selling_price' => 'nullable|numeric|min:0',
        'discount' => 'nullable|numeric|min:0',

        'stock_quantity' => 'nullable|integer|min:0',
        'expiry_date' => 'nullable|date',
        'batch_number' => 'nullable|string|max:100',

        'prescription_required' => 'boolean',
        'product_image' => 'nullable|image|max:2048',
        'product_description' => 'nullable|string',
    ];

    public function mount($pharmacyId)
    {
        $this->pharmacy_id = $pharmacyId;
    }

    public function removeImage()
    {
        $this->product_image = null;
    }

    public function addPharmacyProduct()
    {
        $this->validate();
        $productName = $this->product_name;

        $data = [
            'pharmacy_id' => $this->pharmacy_id,
            'product_name' => $this->product_name,
            'product_code' => $this->product_code,
            'category' => $this->category,
            'brand_name' => $this->brand_name,
            'dosage_form' => $this->dosage_form,
            'strength' => $this->strength,
            'pack_size' => $this->pack_size,
            'mrp' => $this->mrp,
            'selling_price' => $this->selling_price,
            'discount' => $this->discount,
            'stock_quantity' => $this->stock_quantity,
            'expiry_date' => $this->expiry_date,
            'batch_number' => $this->batch_number,
            'prescription_required' => $this->prescription_required,
            'product_description' => $this->product_description,
            'status' => $this->status,
        ];

        $this->pharmacyProductService->createProduct($data, $this->product_image);

        $this->resetInput();

        Flux::modal('add-pharmacy-product')->close();
        $this->dispatch('refresh-products');

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Product '.$productName.' added successfully!'
        );

    }

    public function messages()
    {
        return [
            'product_name.required' => 'Product name is required',
            'product_code.required' => 'Product code is required',
            'product_image.image' => 'Product image must be an image',
            'product_image.max' => 'Product image must be less than 2MB',
        ];
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('add-pharmacy-product')->close();
    }

    public function resetInput()
    {
        $this->reset([
            'product_name',
            'product_code',
            'category',
            'brand_name',
            'dosage_form',
            'strength',
            'pack_size',
            'mrp',
            'selling_price',
            'discount',
            'stock_quantity',
            'expiry_date',
            'batch_number',
            'prescription_required',
            'product_image',
            'product_description',
            'status',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.organization.pharmacy.products.create');
    }
}
