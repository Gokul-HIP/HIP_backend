<?php

namespace App\Services;

use App\Models\Pharmacy;
use App\Models\Organization;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PharmacyService
{
    public function getPharmaciesByOrganization($organizationId)
    {
        return Pharmacy::where('organization_id', $organizationId)
            ->with('organization')
            ->latest()
            ->get();
    }

    public function getPharmaciesByOrganizationPaginated($organizationId, $perPage = 10)
    {
        return Pharmacy::where('organization_id', $organizationId)
            ->with('organization')
            ->latest()
            ->paginate($perPage);
    }

    public function findPharmacy($id)
    {
        return Pharmacy::with('organization')->findOrFail($id);
    }

    public function generatePharmacyId($organizationId)
    {
        $organization = Organization::findOrFail($organizationId);
        $latestId = Pharmacy::latest('id')->value('id') ?? 0;
        
        return $organization->org_name . '-' . 'PHARMACY' . '-' . date('Y') . '-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
    }

    public function createPharmacy(array $data, $imageFile = null)
    {
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('pharmacy', $imageName, 'public');
            $data['pharmacy_logo'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        return Pharmacy::create($data);
    }

    public function updatePharmacy($id, array $data, $imageFile = null)
    {
        $pharmacy = Pharmacy::findOrFail($id);

        // Handle image removal (if image is explicitly set to null)
        if (isset($data['pharmacy_logo']) && $data['pharmacy_logo'] === null) {
            if ($pharmacy->pharmacy_logo) {
                $oldImagePath = 'pharmacy/' . $pharmacy->pharmacy_logo;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }
            // Image will be set to null by the update
        } elseif ($imageFile) {
            if ($pharmacy->pharmacy_logo) {
                $oldImagePath = 'pharmacy/' . $pharmacy->pharmacy_logo;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('pharmacy', $imageName, 'public');
            $data['pharmacy_logo'] = $imageName;
        }
        // If no image file and image is not null, keep existing image (don't modify it)

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        $pharmacy->update($data);

        return $pharmacy->fresh();
    }

    public function deletePharmacy($id)
    {
        $pharmacy = Pharmacy::findOrFail($id);

        if ($pharmacy->pharmacy_logo) {
            $imagePath = 'pharmacy/' . $pharmacy->pharmacy_logo;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $pharmacy->delete();
    }

    // public function searchPharmacies($organizationId, array $filters = [])
    // {
    //     $query = Pharmacy::where('organization_id', $organizationId);

    //     if (!empty($filters['search'])) {
    //         $query->where(function ($q) use ($filters) {
    //             $q->where('pharmacy_name', 'like', '%' . $filters['search'] . '%')
    //               ->orWhere('pharmacy_id', 'like', '%' . $filters['search'] . '%')
    //               ->orWhere('pharmacy_address', 'like', '%' . $filters['search'] . '%')
    //               ->orWhere('pharmacy_license_number', 'like', '%' . $filters['search'] . '%');
    //         });
    //     }

    //     if (!empty($filters['status'])) {
    //         $query->where('status', $filters['status']);
    //     }

    //     return $query
    //         ->with('organization')
    //         ->latest()
    //         ->get();
    // }

    public function searchPharmaciesPaginated($organizationId, array $filters = [], $perPage = 10)
    {
        $query = Pharmacy::where('organization_id', $organizationId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('pharmacy_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('pharmacy_id', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('pharmacy_address', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('pharmacy_license_number', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->with('organization')
            ->latest()
            ->paginate($perPage);
    }
}