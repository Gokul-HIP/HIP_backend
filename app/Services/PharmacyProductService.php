<?php

namespace App\Services;

use App\Models\PharmacyProducts;
use App\Models\MedicineMaster;
use App\Models\Pharmacy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PharmacyProductService
{
    public function getProductsPaginated($pharmacyId, array $filters = [], $perPage = 10)
    {
        $query = PharmacyProducts::query()
            ->where('pharmacy_id', $pharmacyId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('product_name', 'like', "%{$filters['search']}%")
                    ->orWhere('product_code', 'like', "%{$filters['search']}%")
                    ->orWhere('category', 'like', "%{$filters['search']}%")
                    ->orWhere('brand_name', 'like', "%{$filters['search']}%")
                    ->orWhere('dosage_form', 'like', "%{$filters['search']}%")
                    ->orWhere('strength', 'like', "%{$filters['search']}%")
                    ->orWhere('pack_size', 'like', "%{$filters['search']}%")
                    ->orWhere('mrp', 'like', "%{$filters['search']}%")
                    ->orWhere('selling_price', 'like', "%{$filters['search']}%")
                    ->orWhere('discount', 'like', "%{$filters['search']}%")
                    ->orWhere('stock_quantity', 'like', "%{$filters['search']}%")
                    ->orWhere('expiry_date', 'like', "%{$filters['search']}%")
                    ->orWhere('batch_number', 'like', "%{$filters['search']}%")
                    ->orWhere('prescription_required', 'like', "%{$filters['search']}%")
                    ->orWhere('product_image', 'like', "%{$filters['search']}%")
                    ->orWhere('product_description', 'like', "%{$filters['search']}%")
                    ->orWhere('product_status', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('product_status', $filters['status']);
        }

        if (!empty($filters['brand_name'])) {
            $query->where('brand_name', $filters['brand_name']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['expiry_date'])) {
            $query->whereDate('expiry_date', $filters['expiry_date']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findProduct($id)
    {
        return PharmacyProducts::findOrFail($id);
    }

    public function createProduct(array $data, $imageFile = null)
    {
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('pharmacy/products', $imageName, 'public');
            $data['product_image'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['product_status'] = $data['status'] ? 'active' : 'inactive';
            unset($data['status']);
        }

        return PharmacyProducts::create($data);
    }

    public function updateProduct($id, array $data, $imageFile = null)
    {
        $product = PharmacyProducts::findOrFail($id);

        if ($imageFile) {
            if ($product->product_image) {
                $oldImagePath = 'pharmacy/products/' . $product->product_image;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . time() . '.' . $extension;
            $imageFile->storeAs('pharmacy/products', $imageName, 'public');
            $data['product_image'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['product_status'] = $data['status'] ? 'active' : 'inactive';
            unset($data['status']);
        }

        // Convert empty strings to null for nullable fields
        $nullableFields = ['mrp', 'selling_price', 'discount', 'stock_quantity', 'expiry_date', 'batch_number'];
        foreach ($nullableFields as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        $product->update($data);

        return $product->fresh();
    }

    public function deleteProduct($id)
    {
        $product = PharmacyProducts::findOrFail($id);

        if ($product->product_image) {
            $imagePath = 'pharmacy/products/' . $product->product_image;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $product->delete();
    }

    public function getProductCounts($pharmacyId)
    {
        return [
            'total' => PharmacyProducts::where('pharmacy_id', $pharmacyId)->count(),
            'active' => PharmacyProducts::where('pharmacy_id', $pharmacyId)
                ->where('product_status', 'active')
                ->count(),
        ];
    }

    public function createBulkProductsFromMedicineMaster($pharmacyId, array $medicineMasterIds)
    {
        $medicineMasters = MedicineMaster::whereIn('id', $medicineMasterIds)->get();
        
        $createdCount = 0;
        $skippedCount = 0;
        
        foreach ($medicineMasters as $master) {
            // Check if product already exists for this pharmacy
            $existingProduct = PharmacyProducts::where('pharmacy_id', $pharmacyId)
                ->where('medicine_master_id', $master->id)
                ->first();

            if ($existingProduct) {
                $skippedCount++;
                continue;
            }

            // Generate unique product code
            $latestId = PharmacyProducts::latest('id')->value('id') ?? 0;
            $productCode = 'PROD-' . strtoupper(Str::random(8)) . '-' . str_pad($latestId + 1 + $createdCount, 4, '0', STR_PAD_LEFT);

            // Map MedicineMaster fields to PharmacyProducts
            $productData = [
                'pharmacy_id' => $pharmacyId,
                'medicine_master_id' => $master->id,
                'product_name' => $master->name,
                'product_code' => $productCode,
                'category' => $master->category,
                'brand_name' => $master->brand_name,
                'dosage_form' => $master->dosage_form,
                'strength' => $master->strength,
                'pack_size' => $master->pack_size,
                'mrp' => $master->mrp,
                'selling_price' => $master->selling_price,
                'discount' => $master->discount,
                'stock_quantity' => $master->stock_quantity ?? 0,
                'expiry_date' => $master->expiry_date,
                'batch_number' => $master->batch_number,
                'prescription_required' => $master->prescription_required ?? false,
                'product_image' => $master->image ? basename($master->image) : null,
                'product_description' => $master->description,
                'product_status' => 'inactive',
            ];

            PharmacyProducts::create($productData);
            $createdCount++;
        }

        return [
            'created' => $createdCount,
            'skipped' => $skippedCount,
        ];
    }
}