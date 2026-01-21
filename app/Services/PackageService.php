<?php

namespace App\Services;

use App\Models\DiagnosticPackage;
use App\Models\Diagnostic;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PackageService
{
    /**
     * Get packages by diagnostic ID with pagination
     */
    public function getPackagesByDiagnostic($diagnosticId, $perPage = 10)
    {
        return DiagnosticPackage::where('diagnostic_id', $diagnosticId)
            ->with(['diagnostic', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all packages by diagnostic ID
     */
    public function getAllPackagesByDiagnostic($diagnosticId)
    {
        return DiagnosticPackage::where('diagnostic_id', $diagnosticId)
            ->with(['diagnostic', 'organization'])
            ->latest()
            ->get();
    }

    /**
     * Search and filter packages
     */
    public function searchPackages($diagnosticId, array $filters = [])
    {
        $query = DiagnosticPackage::where('diagnostic_id', $diagnosticId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('price', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('discount', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('weight', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query
            ->with(['diagnostic', 'organization'])
            ->latest()
            ->get();
    }

    /**
     * Find a package by ID
     */
    public function findPackage($id)
    {
        return DiagnosticPackage::with(['diagnostic', 'organization'])->findOrFail($id);
    }

    /**
     * Get diagnostic information
     */
    public function getDiagnostic($diagnosticId)
    {
        return Diagnostic::findOrFail($diagnosticId);
    }

    /**
     * Generate unique package code
     */
    public function generatePackageCode($diagnosticId)
    {
        $diagnostic = Diagnostic::findOrFail($diagnosticId);
        $latestId = DiagnosticPackage::latest('id')->value('id') ?? 0;
        
        return 'PKG-' . strtoupper(substr($diagnostic->diagnostic_center_name, 0, 3)) . '-' . date('Y') . '-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new package
     */
    public function createPackage(array $data, $imageFile = null)
    {
        // Handle image upload
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('diagnostic-packages', $imageName, 'public');
            $data['image'] = $imageName;
        }

        // Handle status conversion
        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        // Convert lab_tests array to JSON
        if (isset($data['lab_tests']) && is_array($data['lab_tests'])) {
            $data['lab_tests'] = json_encode($data['lab_tests']);
        }

        // Get organization ID from diagnostic if not provided
        if (!isset($data['organization_id']) && isset($data['diagnostic_id'])) {
            $diagnostic = Diagnostic::find($data['diagnostic_id']);
            if ($diagnostic) {
                $data['organization_id'] = $diagnostic->organization_id;
            }
        }

        // Generate code if not provided
        if (!isset($data['code']) || empty($data['code'])) {
            $data['code'] = $this->generatePackageCode($data['diagnostic_id']);
        }

        return DiagnosticPackage::create($data);
    }

    /**
     * Update an existing package
     */
    public function updatePackage($id, array $data, $imageFile = null)
    {
        $package = DiagnosticPackage::findOrFail($id);

        // Handle image removal (if image is explicitly set to null)
        if (isset($data['image']) && $data['image'] === null) {
            if ($package->image) {
                $oldImagePath = 'diagnostic-packages/' . $package->image;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }
            // Image will be set to null by the update
        } elseif ($imageFile) {
            // Delete old image if exists
            if ($package->image) {
                $oldImagePath = 'diagnostic-packages/' . $package->image;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            // Upload new image
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('diagnostic-packages', $imageName, 'public');
            $data['image'] = $imageName;
        }
        // If no image file and image is not null, keep existing image (don't modify it)

        // Handle status conversion
        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        // Convert lab_tests array to JSON
        if (isset($data['lab_tests']) && is_array($data['lab_tests'])) {
            $data['lab_tests'] = json_encode($data['lab_tests']);
        }

        $package->update($data);

        return $package->fresh();
    }

    /**
     * Delete a package
     */
    public function deletePackage($id)
    {
        $package = DiagnosticPackage::findOrFail($id);

        // Delete image if exists
        if ($package->image) {
            $imagePath = 'diagnostic-packages/' . $package->image;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $package->delete();
    }
}