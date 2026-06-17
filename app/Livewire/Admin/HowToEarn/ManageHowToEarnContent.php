<?php

namespace App\Livewire\Admin\HowToEarn;

use App\Models\HowToEarnContent;
use Flux\Flux;
use Livewire\Component;

class ManageHowToEarnContent extends Component
{
    public ?int $contentId = null;

    public string $type = '';

    public array $how_to_earn = [['title' => '', 'description' => '']];

    public array $terms_conditions = [['description' => '']];

    public bool $is_active = true;

    public function render()
    {
        $contents = HowToEarnContent::query()
            ->orderBy('type')
            ->get()
            ->keyBy('type');

        $usedTypes = $contents->keys()->all();
        $availableTypes = collect(HowToEarnContent::typeOptions())
            ->reject(fn ($label, $key) => in_array($key, $usedTypes, true) && $this->type !== $key)
            ->all();

        return view('livewire.admin.how-to-earn.manage-how-to-earn-content', [
            'contentsByType' => $contents,
            'availableTypes' => $availableTypes,
            'typeOptions' => HowToEarnContent::typeOptions(),
            'totalConfigured' => $contents->count(),
            'totalActive' => $contents->where('is_active', true)->count(),
            'typesRemaining' => count(HowToEarnContent::typeOptions()) - $contents->count(),
        ]);
    }

    public function openCreateModal(?string $presetType = null): void
    {
        $this->resetForm();

        if ($presetType && array_key_exists($presetType, HowToEarnContent::typeOptions())) {
            $this->type = $presetType;
        }

        Flux::modal('how-to-earn-form')->show();
    }

    public function openEditModal(int $id): void
    {
        $content = HowToEarnContent::findOrFail($id);

        $this->contentId = $content->id;
        $this->type = $content->type;
        $this->how_to_earn = $content->how_to_earn ?: [['title' => '', 'description' => '']];
        $this->terms_conditions = $content->terms_conditions ?: [['description' => '']];
        $this->is_active = (bool) $content->is_active;

        Flux::modal('how-to-earn-form')->show();
    }

    public function addHowToEarnRow(): void
    {
        $this->how_to_earn[] = ['title' => '', 'description' => ''];
    }

    public function removeHowToEarnRow(int $index): void
    {
        unset($this->how_to_earn[$index]);
        $this->how_to_earn = array_values($this->how_to_earn ?: [['title' => '', 'description' => '']]);

        if ($this->how_to_earn === []) {
            $this->how_to_earn = [['title' => '', 'description' => '']];
        }
    }

    public function addTermsRow(): void
    {
        $this->terms_conditions[] = ['description' => ''];
    }

    public function removeTermsRow(int $index): void
    {
        unset($this->terms_conditions[$index]);
        $this->terms_conditions = array_values($this->terms_conditions ?: [['description' => '']]);

        if ($this->terms_conditions === []) {
            $this->terms_conditions = [['description' => '']];
        }
    }

    public function saveContent(): void
    {
        $rules = [
            'type' => 'required|string|in:'.implode(',', array_keys(HowToEarnContent::typeOptions())),
            'how_to_earn' => 'required|array|min:1',
            'how_to_earn.*.title' => 'required|string|max:255',
            'how_to_earn.*.description' => 'required|string|max:2000',
            'terms_conditions' => 'required|array|min:1',
            'terms_conditions.*.description' => 'required|string|max:2000',
            'is_active' => 'boolean',
        ];

        if ($this->contentId) {
            $rules['type'] .= '|unique:how_to_earn_contents,type,'.$this->contentId;
        } else {
            $rules['type'] .= '|unique:how_to_earn_contents,type';
        }

        $validated = $this->validate($rules, [
            'type.required' => 'Please select a content type.',
            'type.unique' => 'Content for this type already exists.',
            'how_to_earn.required' => 'Add at least one How to Earn step.',
            'how_to_earn.*.title.required' => 'Each How to Earn step needs a title.',
            'how_to_earn.*.description.required' => 'Each How to Earn step needs a description.',
            'terms_conditions.required' => 'Add at least one Terms & Conditions item.',
            'terms_conditions.*.description.required' => 'Each Terms & Conditions item needs text.',
        ]);

        $payload = [
            'type' => $validated['type'],
            'how_to_earn' => collect($validated['how_to_earn'])
                ->map(fn ($row) => [
                    'title' => trim($row['title']),
                    'description' => trim($row['description']),
                ])
                ->values()
                ->all(),
            'terms_conditions' => collect($validated['terms_conditions'])
                ->map(fn ($row) => ['description' => trim($row['description'])])
                ->values()
                ->all(),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];

        if ($this->contentId) {
            HowToEarnContent::findOrFail($this->contentId)->update($payload);
            $message = 'How to Earn content updated successfully!';
        } else {
            HowToEarnContent::create($payload);
            $message = 'How to Earn content created successfully!';
        }

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function deleteContent(int $id): void
    {
        HowToEarnContent::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'How to Earn content deleted successfully!');
    }

    public function closeModal(): void
    {
        $this->resetForm();
        Flux::modal('how-to-earn-form')->close();
    }

    private function resetForm(): void
    {
        $this->reset([
            'contentId',
            'type',
            'how_to_earn',
            'terms_conditions',
            'is_active',
        ]);

        $this->how_to_earn = [['title' => '', 'description' => '']];
        $this->terms_conditions = [['description' => '']];
        $this->is_active = true;
    }
}
