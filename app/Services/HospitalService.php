<?php

namespace App\Services;

use App\Models\Hospital;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class HospitalService
{
    public function getHospitalsByOrganization($organizationId)
    {
        return Hospital::where('organization_id', $organizationId)
            ->with('organization')
            ->latest()
            ->get();
    }

    public function getHospitalsByOrganizationPaginated($organizationId, $perPage = 10)
    {
        return Hospital::where('organization_id', $organizationId)
            ->with('organization')
            ->latest()
            ->paginate($perPage);
    }

    public function findHospital($id)
    {
        return Hospital::with('organization')->findOrFail($id);
    }

    public function createHospital(array $data, $imageFile = null)
    {
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('hospital', $imageName, 'public');
            $data['hospital_logo'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        return Hospital::create($data);
    }

    public function updateHospital($id, array $data, $imageFile = null)
    {
        $hospital = Hospital::findOrFail($id);

        // Handle image removal (if image is explicitly set to null)
        if (isset($data['hospital_logo']) && $data['hospital_logo'] === null) {
            if ($hospital->hospital_logo) {
                $oldImagePath = 'hospital/' . $hospital->hospital_logo;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }
            // Image will be set to null by the update
        } elseif ($imageFile) {
            if ($hospital->hospital_logo) {
                $oldImagePath = 'hospital/' . $hospital->hospital_logo;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('hospital', $imageName, 'public');
            $data['hospital_logo'] = $imageName;
        }
        // If no image file and image is not null, keep existing image (don't modify it)

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        $hospital->update($data);

        return $hospital->fresh();
    }

    public function deleteHospital($id)
    {
        $hospital = Hospital::findOrFail($id);

        if ($hospital->hospital_logo) {
            $imagePath = 'hospital/' . $hospital->hospital_logo;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $hospital->delete();
    }

    public function searchHospitals($organizationId, array $filters = [])
    {
        $query = Hospital::where('organization_id', $organizationId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('hospital_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('hospital_address', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('hospital_admin_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('hospital_admin_email', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('organization', function ($sq) use ($filters) {
                      $sq->where('org_name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['location'])) {
            $query->where('hospital_address', 'like', '%' . $filters['location'] . '%');
        }

        return $query
            ->with('organization')
            ->latest()
            ->get();
    }

    public function searchHospitalsPaginated($organizationId, array $filters = [], $perPage = 10)
    {
        $query = Hospital::where('organization_id', $organizationId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('hospital_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('hospital_address', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('hospital_admin_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('hospital_admin_email', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('organization', function ($sq) use ($filters) {
                      $sq->where('org_name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['location'])) {
            $query->where('hospital_address', 'like', '%' . $filters['location'] . '%');
        }

        return $query
            ->with('organization')
            ->latest()
            ->paginate($perPage);
    }
}
