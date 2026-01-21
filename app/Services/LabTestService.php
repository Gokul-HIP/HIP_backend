<?php

namespace App\Services;

use App\Models\DiagnosticLabTest;
use App\Models\Diagnostic;
use App\Models\LabTestMaster;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class LabTestService
{
    /**
     * Get lab tests by diagnostic ID with pagination
     */
    public function getLabTestsByDiagnostic($diagnosticId, $perPage = 10)
    {
        return DiagnosticLabTest::where('diagnostic_id', $diagnosticId)
            ->with(['diagnostic', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all lab tests by diagnostic ID
     */
    public function getAllLabTestsByDiagnostic($diagnosticId)
    {
        return DiagnosticLabTest::where('diagnostic_id', $diagnosticId)
            ->with(['diagnostic', 'organization'])
            ->latest()
            ->get();
    }

    /**
     * Search and filter lab tests with pagination
     */
    public function searchLabTestsPaginated($diagnosticId, array $filters = [], $perPage = 10)
    {
        $query = DiagnosticLabTest::where('diagnostic_id', $diagnosticId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('test_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('test_category', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('test_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('test_description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('test_price', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('test_discount', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('test_status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('test_category', $filters['category']);
        }

        return $query
            ->with(['diagnostic', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a lab test by ID
     */
    public function findLabTest($id)
    {
        return DiagnosticLabTest::with(['diagnostic', 'organization'])->findOrFail($id);
    }

    /**
     * Get diagnostic information
     */
    public function getDiagnostic($diagnosticId)
    {
        return Diagnostic::findOrFail($diagnosticId);
    }

    /**
     * Create a new lab test
     */
    public function createLabTest(array $data, $imageFile = null)
    {
        // Handle image upload
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('diagnostic-lab-test', $imageName, 'public');
            $data['test_image'] = $imageName;
        }

        // Handle status conversion
        if (isset($data['status']) && is_bool($data['status'])) {
            $data['test_status'] = $data['status'] ? 'active' : 'inactive';
        } elseif (isset($data['status'])) {
            $data['test_status'] = $data['status'];
        }

        // Get organization ID from diagnostic if not provided
        if (!isset($data['organization_id']) && isset($data['diagnostic_id'])) {
            $diagnostic = Diagnostic::find($data['diagnostic_id']);
            if ($diagnostic) {
                $data['organization_id'] = $diagnostic->organization_id;
            }
        }

        return DiagnosticLabTest::create($data);
    }

    /**
     * Update an existing lab test
     */
    public function updateLabTest($id, array $data, $imageFile = null)
    {
        $labTest = DiagnosticLabTest::findOrFail($id);

        // Handle image removal (if image is explicitly set to null)
        if (isset($data['test_image']) && $data['test_image'] === null) {
            if ($labTest->test_image) {
                $oldImagePath = 'diagnostic-lab-test/' . $labTest->test_image;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }
            // Image will be set to null by the update
        } elseif ($imageFile) {
            // Delete old image if exists
            if ($labTest->test_image) {
                $oldImagePath = 'diagnostic-lab-test/' . $labTest->test_image;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            // Upload new image
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $imageFile->storeAs('diagnostic-lab-test', $imageName, 'public');
            $data['test_image'] = $imageName;
        }
        // If no image file and image is not null, keep existing image (don't modify it)

        // Handle status conversion
        if (isset($data['status']) && is_bool($data['status'])) {
            $data['test_status'] = $data['status'] ? 'active' : 'inactive';
        } elseif (isset($data['status'])) {
            $data['test_status'] = $data['status'];
        }

        $labTest->update($data);

        return $labTest->fresh();
    }

    /**
     * Delete a lab test
     */
    public function deleteLabTest($id)
    {
        $labTest = DiagnosticLabTest::findOrFail($id);

        // Delete associated image
        if ($labTest->test_image) {
            $imagePath = 'diagnostic-lab-test/' . $labTest->test_image;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        return $labTest->delete();
    }

    /**
     * Bulk create lab tests from master tests
     */
    public function bulkCreateLabTests(array $masterTestIds, $diagnosticId, $organizationId = null)
    {
        // Get organization ID from diagnostic if not provided
        if (!$organizationId) {
            $diagnostic = Diagnostic::find($diagnosticId);
            if ($diagnostic) {
                $organizationId = $diagnostic->organization_id;
            }
        }

        if (!$organizationId) {
            throw new \Exception('Organization ID is required');
        }

        $masterTests = LabTestMaster::whereIn('id', $masterTestIds)->get();
        
        if ($masterTests->isEmpty()) {
            throw new \Exception('No master tests found');
        }

        $createdTests = [];
        $latestId = DiagnosticLabTest::latest('id')->value('id') ?? 0;

        DB::beginTransaction();
        try {
            foreach ($masterTests as $index => $master) {
                // Generate unique test code
                $testCode = $this->generateTestCode($diagnosticId, $latestId + $index + 1);

                $labTestData = [
                    'diagnostic_id' => $diagnosticId,
                    'organization_id' => $organizationId,
                    'test_name' => $master->test_name,
                    'test_category' => $master->test_category,
                    'test_code' => $master->test_code,
                    'test_description' => $master->test_description,
                    'test_price' => $master->test_price,
                    'test_discount' => $master->test_discount,
                    'test_image' => $master->test_image ? basename($master->test_image) : null,
                    'test_status' => 'inactive',
                ];

                $createdTests[] = DiagnosticLabTest::create($labTestData);
            }

            DB::commit();
            return $createdTests;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate unique test code
     */
    private function generateTestCode($diagnosticId, $sequenceNumber)
    {
        $diagnostic = Diagnostic::find($diagnosticId);
        $diagnosticName = $diagnostic ? Str::slug($diagnostic->diagnostic_center_name, '') : 'DIAG';
        
        return strtoupper($diagnosticName) . '-TEST-' . date('Y') . '-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get lab test masters with search and pagination
     */
    public function getLabTestMasters(array $filters = [], $perPage = 10)
    {
        $query = LabTestMaster::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('test_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('test_category', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('test_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('test_description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['category'])) {
            $query->where('test_category', $filters['category']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('test_status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get lab test masters by IDs
     */
    public function getLabTestMastersByIds(array $ids)
    {
        return LabTestMaster::whereIn('id', $ids)->get();
    }
}
