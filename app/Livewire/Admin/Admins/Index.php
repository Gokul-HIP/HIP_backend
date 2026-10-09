<?php

namespace App\Livewire\Admin\Admins;

use App\Models\HIPUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = 'all';

    public string $statusFilter = 'all';

    public bool $showEditModal = false;

    public bool $showPasswordModal = false;

    public bool $showStatusDialog = false;

    public bool $showDeleteDialog = false;

    public ?string $editingId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $mobile_num = '';

    public string $roleName = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $statusUserId = null;

    public string $statusUserName = '';

    public bool $statusActivating = false;

    public ?string $deleteUserId = null;

    public string $deleteUserName = '';

    public string $actionError = '';

    public function updatingSearch(): void
    {
        $this->resetPage(pageName: 'adminsPage');
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage(pageName: 'adminsPage');
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage(pageName: 'adminsPage');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = 'all';
        $this->statusFilter = 'all';
        $this->resetPage(pageName: 'adminsPage');
    }

    #[On('openAdminEdit')]
    public function openEdit(string $id): void
    {
        $user = $this->adminQuery()->with('roles')->findOrFail($id);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->actionError = '';
        $this->editingId = (string) $user->id;
        $this->first_name = (string) ($user->first_name ?? '');
        $this->last_name = (string) ($user->last_name ?? '');
        $this->email = (string) ($user->email ?? '');
        $this->mobile_num = (string) ($user->mobile_num ?? '');
        $this->roleName = (string) ($user->roles->first()->name ?? '');
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        $this->actionError = '';
        $user = $this->adminQuery()->findOrFail($this->editingId);
        $this->mobile_num = trim($this->mobile_num);

        $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('healthinpocket_users', 'email')->ignore($user->id)],
            'mobile_num' => ['nullable', 'string', 'max:20', Rule::unique('healthinpocket_users', 'mobile_num')->ignore($user->id)],
            'roleName' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'filament')],
        ]);

        if ($reason = $this->roleBlockReason($user, $this->roleName)) {
            $this->actionError = $reason;
            $this->dispatch('toast', type: 'error', message: $reason);

            return;
        }

        $role = Role::query()->where('name', $this->roleName)->where('guard_name', 'filament')->firstOrFail();

        DB::transaction(function () use ($user, $role): void {
            $user->update([
                'first_name' => trim($this->first_name),
                'last_name' => trim($this->last_name) !== '' ? trim($this->last_name) : null,
                'email' => trim($this->email),
                'mobile_num' => $this->mobile_num !== '' ? $this->mobile_num : null,
                'role' => $role->name,
            ]);
            $user->syncRoles([$role]);
        });

        $this->showEditModal = false;
        $this->dispatch('toast', type: 'success', message: 'Admin updated');
    }

    #[On('openAdminPassword')]
    public function openPassword(string $id): void
    {
        $user = $this->adminQuery()->findOrFail($id);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->actionError = '';
        $this->editingId = (string) $user->id;
        $this->password = '';
        $this->password_confirmation = '';
        $this->statusUserName = $this->displayName($user);
        $this->showPasswordModal = true;
    }

    public function savePassword(): void
    {
        $this->actionError = '';
        $user = $this->adminQuery()->findOrFail($this->editingId);

        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($this->password);
        $user->save();
        $user->tokens()->delete();

        $this->password = '';
        $this->password_confirmation = '';
        $this->showPasswordModal = false;
        $this->dispatch('toast', type: 'success', message: 'Password updated');
    }

    #[On('toggleAdminActive')]
    public function askStatusChange(string $id): void
    {
        $user = $this->adminQuery()->findOrFail($id);
        $this->actionError = '';
        $this->statusUserId = (string) $user->id;
        $this->statusUserName = $this->displayName($user);
        $this->statusActivating = ! (bool) $user->is_active;
        $this->showStatusDialog = true;
    }

    public function confirmStatusChange(): void
    {
        $this->actionError = '';
        if (! $this->statusUserId) {
            return;
        }

        $user = $this->adminQuery()->findOrFail($this->statusUserId);
        if ($reason = $this->statusBlockReason($user, $this->statusActivating)) {
            $this->actionError = $reason;
            $this->dispatch('toast', type: 'error', message: $reason);

            return;
        }

        DB::transaction(function () use ($user): void {
            $user->update([
                'is_active' => $this->statusActivating,
                'deactivated_at' => $this->statusActivating ? null : now(),
            ]);

            if (! $this->statusActivating) {
                $user->tokens()->delete();

                if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }
            }
        });

        $this->showStatusDialog = false;
        $this->dispatch('toast', type: 'success', message: $this->statusActivating ? 'Admin activated' : 'Admin deactivated');
    }

    #[On('askAdminDelete')]
    public function askDelete(string $id): void
    {
        $user = $this->adminQuery()->findOrFail($id);
        $this->actionError = '';
        $this->deleteUserId = (string) $user->id;
        $this->deleteUserName = $this->displayName($user);
        $this->showDeleteDialog = true;
    }

    public function confirmDelete(): void
    {
        $this->actionError = '';
        if (! $this->deleteUserId) {
            return;
        }

        $user = $this->adminQuery()->findOrFail($this->deleteUserId);
        if ($reason = $this->deleteBlockReason($user)) {
            $this->actionError = $reason;
            $this->dispatch('toast', type: 'error', message: $reason);

            return;
        }

        try {
            DB::transaction(function () use ($user): void {
                $user->tokens()->delete();
                DB::table('model_has_roles')->where('model_id', $user->id)->where('model_type', HIPUser::class)->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->delete();
            });
        } catch (QueryException) {
            $this->actionError = 'This admin has linked records — deactivate instead';
            $this->dispatch('toast', type: 'error', message: $this->actionError);

            return;
        }

        $this->showDeleteDialog = false;
        $this->resetPage(pageName: 'adminsPage');
        $this->dispatch('toast', type: 'success', message: 'Admin deleted');
    }

    public function render()
    {
        $admins = $this->filteredQuery()
            ->with('roles')
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->paginate(10, ['*'], 'adminsPage');

        $base = $this->adminQuery();

        return view('livewire.admin.admins.index', [
            'admins' => $admins,
            'roles' => Role::query()->where('guard_name', 'filament')->orderBy('name')->get(['id', 'name']),
            'kpiTotal' => (clone $base)->count(),
            'kpiActive' => (clone $base)->where('is_active', true)->count(),
            'kpiInactive' => (clone $base)->where('is_active', false)->count(),
        ]);
    }

    protected function adminQuery(): Builder
    {
        return HIPUser::query()->whereNotNull('password');
    }

    protected function filteredQuery(): Builder
    {
        $term = trim($this->search);

        return $this->adminQuery()
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('mobile_num', 'like', "%{$term}%")
                        ->orWhereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) like ?", ["%{$term}%"]);
                });
            })
            ->when($this->roleFilter === 'none', fn (Builder $query) => $query->whereDoesntHave('roles'))
            ->when($this->roleFilter !== 'all' && $this->roleFilter !== 'none', function (Builder $query): void {
                $query->whereHas('roles', function (Builder $roles): void {
                    $roles->where('name', $this->roleFilter)->where('guard_name', 'filament');
                });
            })
            ->when($this->statusFilter === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn (Builder $query) => $query->where('is_active', false));
    }

    protected function statusBlockReason(HIPUser $user, bool $activating): ?string
    {
        if (! $activating && $this->isSelf($user)) {
            return 'You cannot deactivate your own account';
        }

        if (! $activating && $this->isLastActiveSuperAdmin($user)) {
            return 'You cannot deactivate the last active super admin';
        }

        return null;
    }

    protected function deleteBlockReason(HIPUser $user): ?string
    {
        if ($this->isSelf($user)) {
            return 'You cannot delete your own account';
        }

        if ($this->isLastActiveSuperAdmin($user)) {
            return 'You cannot delete the last active super admin';
        }

        if ($this->hasLinkedRecords((string) $user->id)) {
            return 'This admin has linked records — deactivate instead';
        }

        return null;
    }

    protected function roleBlockReason(HIPUser $user, string $nextRole): ?string
    {
        foreach ($this->superAdminRoles() as $roleName) {
            if ($nextRole === $roleName) {
                continue;
            }

            if ($user->hasRole($roleName, 'filament') && $this->isLastActiveHolder($user, $roleName)) {
                return 'You cannot remove the super-admin role from the last active super admin';
            }
        }

        return null;
    }

    protected function isSelf(HIPUser $user): bool
    {
        $actorId = auth('filament')->id();

        return $actorId !== null && (string) $actorId === (string) $user->id;
    }

    protected function isLastActiveSuperAdmin(HIPUser $user): bool
    {
        foreach ($this->superAdminRoles() as $roleName) {
            if ($user->hasRole($roleName, 'filament') && $this->isLastActiveHolder($user, $roleName)) {
                return true;
            }
        }

        return false;
    }

    protected function isLastActiveHolder(HIPUser $user, string $roleName): bool
    {
        if (! (bool) $user->is_active) {
            return false;
        }

        $holders = HIPUser::query()
            ->where('is_active', true)
            ->whereHas('roles', function (Builder $query) use ($roleName): void {
                $query->where('name', $roleName)->where('guard_name', 'filament');
            })
            ->count();

        return $holders <= 1;
    }

    /**
     * @return list<string>
     */
    protected function superAdminRoles(): array
    {
        return ['super-admin', 'super-admin-hip'];
    }

    protected function hasLinkedRecords(string $userId): bool
    {
        $columns = DB::select("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME NOT IN ('healthinpocket_users', 'model_has_roles', 'personal_access_tokens', 'sessions')
              AND DATA_TYPE IN ('char', 'varchar')
              AND CHARACTER_MAXIMUM_LENGTH >= 36
              AND COLUMN_NAME IN ('hip_user_id', 'member_id', 'user_id', 'created_by', 'updated_by', 'changed_by', 'notes_by')
        ");

        foreach ($columns as $column) {
            if (! preg_match('/^[A-Za-z0-9_]+$/', $column->TABLE_NAME) || ! preg_match('/^[A-Za-z0-9_]+$/', $column->COLUMN_NAME)) {
                continue;
            }

            if (DB::table($column->TABLE_NAME)->where($column->COLUMN_NAME, $userId)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function displayName(HIPUser $user): string
    {
        $name = trim((string) $user->first_name.' '.(string) $user->last_name);

        return $name !== '' ? $name : ($user->email ?: 'this admin');
    }
}
