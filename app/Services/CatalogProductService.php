<?php

namespace App\Services;

use App\Models\ProductBenefit;
use App\Models\Products;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogProductService
{
    public function getProductsPaginated(int $pharmacyId, array $filters = [], int $perPage = 10)
    {
        $query = Products::query()
            ->withCount('productBenefits')
            ->where('pharmacy_id', $pharmacyId);

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('product_name', 'like', "%{$term}%")
                    ->orWhere('product_code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status'] === 'active');
        }

        if (! empty($filters['in_stock']) && $filters['in_stock'] !== 'all') {
            $query->where('in_stock', $filters['in_stock'] === 'yes');
        }

        return $query->latest()->paginate($perPage);
    }

    public function getCounts(int $pharmacyId): array
    {
        $base = Products::query()->where('pharmacy_id', $pharmacyId);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', true)->count(),
        ];
    }

    public function findProduct(int $id): Products
    {
        return Products::with('productBenefits')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile|null>  $imageFiles
     * @param  array<int, array<string, mixed>>  $benefits
     * @param  array<int, UploadedFile|null>  $benefitIconFiles
     */
    public function createProduct(int $pharmacyId, array $data, array $imageFiles = [], array $benefits = [], array $benefitIconFiles = []): Products
    {
        return DB::transaction(function () use ($pharmacyId, $data, $imageFiles, $benefits, $benefitIconFiles) {
            $data['pharmacy_id'] = $pharmacyId;
            $data['product_code'] = $data['product_code'] ?? $this->generateProductCode($pharmacyId);
            $data['images'] = $this->storeImages($imageFiles);
            $data['status'] = (bool) ($data['status'] ?? true);
            $data['in_stock'] = (bool) ($data['in_stock'] ?? true);

            $product = Products::create($data);
            $this->syncBenefits($product, $benefits, $benefitIconFiles);

            return $product->load('productBenefits');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile|null>  $imageFiles
     * @param  array<int, string>  $keepImages
     * @param  array<int, array<string, mixed>>  $benefits
     * @param  array<int, UploadedFile|null>  $benefitIconFiles
     */
    public function updateProduct(
        int $id,
        array $data,
        array $imageFiles = [],
        array $keepImages = [],
        array $benefits = [],
        array $benefitIconFiles = []
    ): Products {
        return DB::transaction(function () use ($id, $data, $imageFiles, $keepImages, $benefits, $benefitIconFiles) {
            $product = Products::findOrFail($id);
            $existingImages = collect($product->images ?? []);

            $removedImages = $existingImages->diff($keepImages);
            foreach ($removedImages as $image) {
                $this->deleteImage((string) $image);
            }

            $newStored = $this->storeImages($imageFiles);
            $data['images'] = array_values(array_merge($keepImages, $newStored));
            $data['status'] = (bool) ($data['status'] ?? false);
            $data['in_stock'] = (bool) ($data['in_stock'] ?? false);

            $product->update($data);
            $this->syncBenefits($product, $benefits, $benefitIconFiles, true);

            return $product->load('productBenefits');
        });
    }

    public function deleteProduct(int $id): void
    {
        DB::transaction(function () use ($id) {
            $product = Products::with('productBenefits')->findOrFail($id);

            foreach ($product->images ?? [] as $image) {
                $this->deleteImage((string) $image);
            }

            foreach ($product->productBenefits as $benefit) {
                $this->deleteBenefitIcon($benefit->icon);
            }

            $product->delete();
        });
    }

    protected function generateProductCode(int $pharmacyId): string
    {
        do {
            $code = 'CAT-'.$pharmacyId.'-'.Str::upper(Str::random(8));
        } while (Products::where('product_code', $code)->exists());

        return $code;
    }

    /**
     * @param  array<int, UploadedFile|null>  $imageFiles
     * @return array<int, string>
     */
    protected function storeImages(array $imageFiles): array
    {
        $stored = [];

        foreach ($imageFiles as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $imageName = Str::uuid().'.'.$extension;
            $file->storeAs('pharmacy/catalog-products', $imageName, 'public');
            $stored[] = $imageName;
        }

        return $stored;
    }

    protected function deleteImage(string $imageName): void
    {
        if ($imageName === '') {
            return;
        }

        $path = 'pharmacy/catalog-products/'.$imageName;

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $benefits
     * @param  array<int, UploadedFile|null>  $benefitIconFiles
     */
    protected function syncBenefits(Products $product, array $benefits, array $benefitIconFiles, bool $replace = false): void
    {
        if ($replace) {
            $existing = $product->productBenefits()->get();
            $keptIds = collect($benefits)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

            foreach ($existing as $benefit) {
                if (! in_array($benefit->id, $keptIds, true)) {
                    $this->deleteBenefitIcon($benefit->icon);
                    $benefit->delete();
                }
            }
        }

        foreach ($benefits as $index => $benefitData) {
            $title = trim((string) ($benefitData['title'] ?? ''));
            $displayOrder = (int) ($benefitData['display_order'] ?? ($index + 1));
            $iconText = trim((string) ($benefitData['icon'] ?? ''));
            $iconFile = $benefitIconFiles[$index] ?? null;
            $benefitId = isset($benefitData['id']) ? (int) $benefitData['id'] : null;
            $removeIcon = (bool) ($benefitData['remove_icon'] ?? false);
            $existingBenefit = $benefitId ? ProductBenefit::find($benefitId) : null;

            $hasIcon = $iconFile instanceof UploadedFile
                || $iconText !== ''
                || ($existingBenefit && filled($existingBenefit->icon) && ! $removeIcon);

            if ($title === '' && ! $hasIcon) {
                continue;
            }

            $iconValue = $iconText;

            if ($iconFile instanceof UploadedFile) {
                if ($existingBenefit) {
                    $this->deleteBenefitIcon($existingBenefit->icon);
                }

                $extension = $iconFile->getClientOriginalExtension() ?: 'jpg';
                $iconName = Str::uuid().'.'.$extension;
                $iconFile->storeAs('pharmacy/product-benefits', $iconName, 'public');
                $iconValue = $iconName;
            } elseif ($removeIcon) {
                if ($existingBenefit) {
                    $this->deleteBenefitIcon($existingBenefit->icon);
                }
                $iconValue = $iconText !== '' ? $iconText : null;
            } elseif ($benefitId) {
                $iconValue = $existingBenefit?->icon ?? $iconText;
            }

            $payload = [
                'title' => $title !== '' ? $title : null,
                'icon' => $iconValue !== '' ? $iconValue : null,
                'display_order' => $displayOrder,
            ];

            if ($benefitId && ProductBenefit::where('product_id', $product->id)->where('id', $benefitId)->exists()) {
                ProductBenefit::where('id', $benefitId)->update($payload);
            } else {
                ProductBenefit::create(array_merge($payload, ['product_id' => $product->id]));
            }
        }
    }

    protected function deleteBenefitIcon(?string $icon): void
    {
        if ($icon === null || $icon === '' || str_contains($icon, 'fa-')) {
            return;
        }

        $path = str_starts_with($icon, 'pharmacy/')
            ? $icon
            : 'pharmacy/product-benefits/'.$icon;

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function imageUrl(?string $fileName): ?string
    {
        if ($fileName === null || $fileName === '') {
            return null;
        }

        return url('storage/pharmacy/catalog-products/'.$fileName);
    }

    public static function benefitIconUrl(?string $icon): ?string
    {
        if ($icon === null || $icon === '' || str_contains($icon, 'fa-')) {
            return null;
        }

        $path = str_starts_with($icon, 'pharmacy/')
            ? $icon
            : 'pharmacy/product-benefits/'.$icon;

        return url('storage/'.$path);
    }
}
