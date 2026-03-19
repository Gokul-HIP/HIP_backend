<?php

namespace App\Livewire\HospitalAdmin\Users;

use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MemberProfile extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $organizationId = (int) (Auth::user()->organization_id ?? 0);

        $hospitalIds = Hospital::query()
            ->where('organization_id', $organizationId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $paidInvoicesQuery = Invoice::query()
            ->join('healthinpocket_users as creators', 'creators.id', '=', 'invoices.created_by')
            ->whereIn('creators.hospital_id', $hospitalIds)
            ->where('invoices.status', 'completed');

        $memberPersonIds = (clone $paidInvoicesQuery)
            ->selectRaw('DISTINCT COALESCE(invoices.person_id, invoices.primary_person_id) as person_id')
            ->pluck('person_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $membersQuery = Persons::query()
            ->with('hipUser:id,hip_id')
            ->whereIn('id', $memberPersonIds);

        $search = trim($this->search);
        if ($search !== '') {
            $membersQuery->where(function (Builder $q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%' . $search . '%'])
                    ->orWhere('mobile', 'like', '%' . $search . '%')
                    ->orWhereHas('hipUser', function (Builder $hipQuery) use ($search) {
                        $hipQuery->where('hip_id', 'like', '%' . $search . '%');
                    });
            });
        }

        $members = $membersQuery
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'membersPage');

        return view('livewire.hospital-admin.users.member-profile', [
            'members' => $members,
        ]);
    }
}

