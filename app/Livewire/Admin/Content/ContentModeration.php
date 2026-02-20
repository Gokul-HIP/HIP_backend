<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;
use App\Models\ContentModeration as ContentModerationModel;
use Livewire\WithPagination;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;

class ContentModeration extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $status = 'all';
    public $category = 'all';

    public $delete_id;

    public function deleteContent($id){
        $this->delete_id = $id;
        Flux::modal('delete-content')->show();
    }

    public function destroy(){

        $content = ContentModerationModel::find($this->delete_id);
        if($content){
            $file = $content->media_file;
            if($file){
                Storage::disk('public')->delete($file);
            }
        }
        $content->delete();
        Flux::modal('delete-content')->close();
        $this->dispatch('toast', type: 'success', message: 'Content deleted successfully!');
        $this->resetPage();
        $this->dispatch('reloadContent');
    }

    public function closeModal(){
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
