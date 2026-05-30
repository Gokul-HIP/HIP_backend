<?php

namespace App\Services;

use App\Models\DiseasePackage;
use App\Models\Diagnostic;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DiseasePackageService
{
    public function searchPackages($diagnosticId, array $filters = [])
    {
        $query = DiseasePackage::where('diagnostic_id', $diagnosticId);

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('code', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('price', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('discount', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('weight', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query
            ->with(['diagnostic', 'organization', 'disease'])
            ->latest()
            ->get();
    }

    public function findPackage($id)
    {
        return DiseasePackage::with(['diagnostic', 'organization', 'disease'])->findOrFail($id);
    }

    public function getDiagnostic($diagnosticId)
    {
        return Diagnostic::findOrFail($diagnosticId);
    }

    public function generatePackageCode($diagnosticId)
    {
        $diagnostic = Diagnostic::findOrFail($diagnosticId);
        $latestId = DiseasePackage::latest('id')->value('id') ?? 0;

        return 'DPKG-' . strtoupper(substr($diagnostic->name, 0, 3)) . '-' . date('Y') . '-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
    }

    public function createPackage(array $data, $imageFile = null)
    {
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('disease-packages', $imageName, 'public');
            $data['image'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        if (isset($data['lab_tests']) && is_array($data['lab_tests'])) {
            $data['lab_tests'] = json_encode($data['lab_tests']);
        }

        if (! isset($data['organization_id']) && isset($data['diagnostic_id'])) {
            $diagnostic = Diagnostic::find($data['diagnostic_id']);
            if ($diagnostic) {
                $data['organization_id'] = $diagnostic->organization_id;
            }
        }

        if (! isset($data['code']) || empty($data['code'])) {
            $data['code'] = $this->generatePackageCode($data['diagnostic_id']);
        }

        return DiseasePackage::create($data);
    }

    public function updatePackage($id, array $data, $imageFile = null)
    {
        $package = DiseasePackage::findOrFail($id);

        if (isset($data['image']) && $data['image'] === null) {
            if ($package->image) {
                $oldImagePath = 'disease-packages/' . $package->image;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }
        } elseif ($imageFile) {
            if ($package->image) {
                $oldImagePath = 'disease-packages/' . $package->image;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('disease-packages', $imageName, 'public');
            $data['image'] = $imageName;
        }

        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'inactive';
        }

        if (isset($data['lab_tests']) && is_array($data['lab_tests'])) {
            $data['lab_tests'] = json_encode($data['lab_tests']);
        }

        $package->update($data);

        return $package->fresh();
    }

    public function deletePackage($id)
    {
        $package = DiseasePackage::findOrFail($id);

        if ($package->image) {
            $imagePath = 'disease-packages/' . $package->image;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $package->delete();
    }
}
