<?php

namespace App\Livewire\Admin\Disease;

use App\Models\Disease;
use App\Models\DiseaseDepartment;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';
    public string $diseaseSearch = '';
    public int $perPage = 10;
    public ?int $departmentId = null;
    public ?int $deleteId = null;
    public string $departmentName = '';
    public $departmentImage;
    public ?string $existingImage = null;
    public array $selectedDiseases = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('department-form')->show();
    }

    public function openEdit(int $id): void
    {
        $department = DiseaseDepartment::findOrFail($id);

        $this->resetValidation();
        $this->departmentId = $department->id;
        $this->departmentName = $department->department_name ?? '';
        $this->existingImage = $department->department_image;
        $this->selectedDiseases = collect($department->diseases ?? [])
            ->map(fn ($diseaseId) => (string) $diseaseId)
            ->all();
        $this->departmentImage = null;
        $this->diseaseSearch = '';

        Flux::modal('department-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'departmentName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('disease_departments', 'department_name')->ignore($this->departmentId),
            ],
            'departmentImage' => [
                $this->departmentId ? 'nullable' : 'required',
                'image',
                'max:2048',
            ],
            'selectedDiseases' => ['required', 'array', 'min:1'],
            'selectedDiseases.*' => ['integer', 'exists:diseases,id'],
        ], [
            'selectedDiseases.required' => 'Please select at least one disease.',
            'selectedDiseases.min' => 'Please select at least one disease.',
        ]);

        $imagePath = $this->existingImage;

        if ($this->departmentImage) {
            $imagePath = $this->departmentImage->store('disease-departments', 'public');

            if ($this->existingImage && Storage::disk('public')->exists($this->existingImage)) {
                Storage::disk('public')->delete($this->existingImage);
            }
        }

        DiseaseDepartment::updateOrCreate(
            ['id' => $this->departmentId],
            [
                'department_name' => $validated['departmentName'],
                'department_image' => $imagePath,
                'diseases' => collect($validated['selectedDiseases'])
                    ->map(fn ($diseaseId) => (int) $diseaseId)
                    ->values()
                    ->all(),
            ]
        );

        $message = $this->departmentId
            ? 'Disease department updated successfully.'
            : 'Disease department added successfully.';

        Flux::modal('department-form')->close();
        Flux::toast(text: $message, variant: 'success');
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        Flux::modal('delete-department')->show();
    }

    public function destroy(): void
    {
        $department = DiseaseDepartment::findOrFail($this->deleteId);

        if ($department->department_image && Storage::disk('public')->exists($department->department_image)) {
            Storage::disk('public')->delete($department->department_image);
        }

        $department->delete();
        Flux::modal('delete-department')->close();
        Flux::toast(text: 'Disease department deleted successfully.', variant: 'success');
        $this->deleteId = null;
    }

    public function closeForm(): void
    {
        Flux::modal('department-form')->close();
        $this->resetForm();
    }

    public function closeDelete(): void
    {
        Flux::modal('delete-department')->close();
        $this->deleteId = null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'departmentId',
            'departmentName',
            'departmentImage',
            'existingImage',
            'selectedDiseases',
            'diseaseSearch',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        $departments = DiseaseDepartment::query()
            ->when($this->search, function ($query) {
                $query->where('department_name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate($this->perPage);

        $diseases = Disease::query()
            ->where('is_active', true)
            ->when($this->diseaseSearch, function ($query) {
                $query->where('name', 'like', '%' . $this->diseaseSearch . '%');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $departmentDiseaseIds = $departments->getCollection()
            ->flatMap(fn (DiseaseDepartment $department) => $department->diseases ?? [])
            ->filter()
            ->unique();

        $diseaseNames = Disease::whereIn('id', $departmentDiseaseIds)->pluck('name', 'id');

        return view('livewire.admin.disease.index', compact(
            'departments',
            'diseases',
            'diseaseNames'
        ));
    }
}
