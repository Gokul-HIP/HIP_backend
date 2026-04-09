<?php

namespace App\Livewire\HospitalAdmin\Content;

use App\Models\ContentModeration as ContentModerationModel;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class ContentModeration extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';

    public $status = 'all';

    public $category = 'all';

    public $delete_id;

    protected function organizationId(): ?int
    {
        $id = Auth::guard('filament')->user()?->organization_id;

        return $id !== null ? (int) $id : null;
    }

    public function deleteContent($id)
    {
        $this->delete_id = $id;
        Flux::modal('delete-content')->show();
    }

    public function destroy()
    {
        $orgId = $this->organizationId();
        if ($orgId === null) {
            Flux::modal('delete-content')->close();
            $this->dispatch('toast', type: 'error', message: 'No organization is assigned to this account.');

            return;
        }

        $content = ContentModerationModel::query()
            ->where('organization_id', $orgId)
            ->find($this->delete_id);

        if (! $content) {
            Flux::modal('delete-content')->close();
            $this->dispatch('toast', type: 'error', message: 'Content already deleted or not found.');
            $this->resetPage();
            $this->dispatch('reloadContent');

            return;
        }

        DB::transaction(function () use ($content) {
            if ($content->media_file && Storage::disk('public')->exists($content->media_file)) {
                Storage::disk('public')->delete($content->media_file);
            }
            $content->targetAreas()->delete();
            $content->delete();
        });

        Flux::modal('delete-content')->close();
        $this->dispatch('toast', type: 'success', message: 'Content deleted successfully!');
        $this->resetPage();
        $this->dispatch('reloadContent');
    }

    public function closeModal()
    {
        Flux::modal('delete-content')->close();
        $this->dispatch('reloadContent');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingCategory()
    {
        $this->resetPage();
    }

    public function render()
    {
        $orgId = $this->organizationId();

        if ($orgId === null) {
            abort(403, 'No organization is assigned to this account.');
        }

        $query = ContentModerationModel::with(['organization', 'hospital', 'doctor', 'speciality', 'targetAreas.locationMaster'])
            ->where('organization_id', $orgId)
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhereHas('organization', function ($orgQuery) {
                            $orgQuery->where('name', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('hospital', function ($hospitalQuery) {
                            $hospitalQuery->where('name', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('doctor', function ($doctorQuery) {
                            $doctorQuery->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->category !== 'all', function ($q) {
                $q->where('category', $this->category);
            })
            ->orderBy('id', 'desc');

        $contents = $query->paginate(10);

        return view('livewire.hospital-admin.content.content-moderation', [
            'contents' => $contents,
        ]);
    }
}
