<flux:modal name="view-member-profile"  class="p-0 max-w-7xl w-full">

    <div class="p-8 space-y-6 max-h-[85vh] overflow-y-auto">

        @if (!$member)
            <div class="text-center text-gray-500 py-10">
                No member selected.
            </div>
        @else

            <!-- HEADER -->
            <div>
                <h1 class="text-2xl font-bold">
                    {{ ucfirst($member->first_name) }} {{ ucfirst($member->last_name) }}
                </h1>
                <p class="text-sm text-gray-600">
                    Member ID: {{ $member->hip_id }}
                </p>
            </div>

            <!-- NAV TABS -->
            <flux:navbar class="border-b bg-white gap-2">

                <flux:navbar.item
                    wire:click="$set('tab','profile')"
                    :active="$tab==='profile'"
                    class="{{ $tab==='profile' ? 'bg-[#0da2e7] text-black rounded-md px-4 py-1' : 'px-4 py-1' }}">
                    <i class="fa-solid fa-user mr-1"></i>
                    Profile
                </flux:navbar.item>

                <flux:navbar.item
                    wire:click="$set('tab','dependents')"
                    :active="$tab==='dependents'"
                    class="{{ $tab==='dependents' ? 'bg-[#0da2e7] text-black rounded-md px-4 py-1' : 'px-4 py-1' }}">
                    <i class="fa-solid fa-users mr-1"></i>
                    Dependents
                </flux:navbar.item>

                <flux:navbar.item
                    wire:click="$set('tab','appointments')"
                    :active="$tab==='appointments'"
                    class="{{ $tab==='appointments' ? 'bg-[#0da2e7] text-black rounded-md px-4 py-1' : 'px-4 py-1' }}">
                    <i class="fa-regular fa-calendar-days mr-1"></i>
                    Appointments
                </flux:navbar.item>

                <flux:navbar.item
                    wire:click="$set('tab','transactions')"
                    :active="$tab==='transactions'"
                    class="{{ $tab==='transactions' ? 'bg-[#0da2e7] text-black rounded-md px-4 py-1' : 'px-4 py-1' }}">
                    <i class="fa-solid fa-indian-rupee-sign mr-1"></i>
                    Transactions
                </flux:navbar.item>

            </flux:navbar>

            <!-- TAB CONTENT -->
            <div class="pt-6">
                @if ($tab === 'profile')
                    @include('livewire.admin.member-profile.steps.profile')
                @endif

                @if ($tab === 'dependents')
                    @include('livewire.admin.member-profile.steps.manage-dependents')
                @endif

                @if ($tab === 'appointments')
                    @include('livewire.admin.member-profile.steps.appointment-booking')
                @endif

                @if ($tab === 'transactions')
                    @include('livewire.admin.member-profile.steps.manage-transactions')
                @endif
            </div>

        @endif

    </div>

</flux:modal>
