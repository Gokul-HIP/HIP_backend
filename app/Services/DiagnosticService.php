<?php

namespace App\Services;

use App\Models\Diagnostic;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DiagnosticService
{
    public function getDiagnosticsPaginated($organizationId, array $filters = [], $perPage = 10)
    {
        $query = Diagnostic::query()
            ->where('organization_id', $organizationId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('diagnostic_center_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('diagnostic_center_address', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['location']) && $filters['location'] !== 'all') {
            $query->where('diagnostic_center_address', 'like', '%' . $filters['location'] . '%');
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findDiagnostic($id)
    {
        return Diagnostic::findOrFail($id);
    }

    public function createDiagnostic($organizationId, array $data, $imageFile = null)
    {
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('diagnostic', $imageName, 'public');
            $data['diagnostic_logo'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        $data['organization_id'] = $organizationId;

        return Diagnostic::create($data);
    }

    public function updateDiagnostic($id, array $data, $imageFile = null, $removeImage = false)
    {
        $diagnostic = Diagnostic::findOrFail($id);
        $oldPath = 'diagnostic/' . $diagnostic->diagnostic_logo;

        // Handle image removal
        if ($removeImage && !$imageFile) {
            if ($diagnostic->diagnostic_logo && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $data['diagnostic_logo'] = null;
        } elseif ($imageFile) {
            // Upload new image
            if ($diagnostic->diagnostic_logo && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('diagnostic', $imageName, 'public');
            $data['diagnostic_logo'] = $imageName;
        }
        // If no new image and not removing, keep existing image (don't modify it)

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        $diagnostic->update($data);

        return $diagnostic->fresh();
    }

    public function deleteDiagnostic($id)
    {
        $diagnostic = Diagnostic::findOrFail($id);

        if ($diagnostic->diagnostic_logo) {
            $imagePath = 'diagnostic/' . $diagnostic->diagnostic_logo;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $diagnostic->delete();
    }
}