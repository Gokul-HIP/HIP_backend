<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;
use App\Models\ContentModeration as ContentModerationModel;
use Livewire\WithPagination;

class ContentModeration extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $status = 'all';
    public $category = 'all';

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
        $query = ContentModerationModel::with(['organization', 'hospital', 'doctor', 'speciality'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('organization', function ($orgQuery) {
                            $orgQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('hospital', function ($hospitalQuery) {
                            $hospitalQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('doctor', function ($doctorQuery) {
                            $doctorQuery->where('name', 'like', '%' . $this->search . '%');
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

        return view('livewire.admin.content.content-moderation', [
            'contents' => $contents,
        ]);
    }
}
