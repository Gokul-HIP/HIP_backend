<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class OrganizationService
{
    public function getOrganizationsPaginated(array $filters = [], $perPage = 10)
    {
        $query = Organization::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('org_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('org_city', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['location']) && $filters['location'] !== 'all') {
            $query->where('org_city', $filters['location']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findOrganization($id)
    {
        return Organization::findOrFail($id);
    }

    public function createOrganization(array $data, $imageFile = null)
    {
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('organization', $imageName, 'public');
            $data['org_logo'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        return Organization::create($data);
    }

    public function updateOrganization($id, array $data, $imageFile = null, $removeImage = false)
    {
        $organization = Organization::findOrFail($id);
        $oldPath = 'organization/' . $organization->org_logo;

        // Handle image removal
        if ($removeImage && !$imageFile) {
            if ($organization->org_logo && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $data['org_logo'] = null;
        } elseif ($imageFile) {
            // Upload new image
            if ($organization->org_logo && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('organization', $imageName, 'public');
            $data['org_logo'] = $imageName;
        }
        // If no new image and not removing, keep existing image (don't modify it)

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        $organization->update($data);

        return $organization->fresh();
    }

    public function deleteOrganization($id)
    {
        $organization = Organization::findOrFail($id);

        if ($organization->org_logo) {
            $imagePath = 'organization/' . $organization->org_logo;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $organization->delete();
    }
}