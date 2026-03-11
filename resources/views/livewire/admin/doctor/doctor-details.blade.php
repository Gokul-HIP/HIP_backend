<flux:modal name="doctor-details" class="p-0">
    
<div class="p-6 space-y-6">

    @if (!$doctor)
        <div class="text-center text-gray-500 py-10">
            No doctor selected.
        </div>
    @else

        <!-- Doctor Header -->
        <div>
            <h1 class="text-2xl font-bold">Dr {{ ucfirst($doctor->name) }}</h1>
            <p class="text-sm text-gray-600">eember ID: HIP00{{ $doctor->id }}</p>
        </div>

        <!-- NAV TABS -->
        <flux:navbar class="border-b bg-white">

            <flux:navbar.item 
                wire:click="$set('tab','profile')" 
                :active="$tab==='profile'"
                class="{{ $tab==='profile' ? 'bg-[#0da2e7] text-black rounded-md px-4 py-1' : 'text-black px-4 py-1' }}">
                <i class="fa-solid fa-user-doctor mr-1 text-black"></i>
                <span class="hidden sm:inline text-black">Profile</span>
            </flux:navbar.item>

            <flux:navbar.item 
                wire:click="$set('tab','linked_hospitals')" 
                :active="$tab==='linked_hospitals'"
                class="{{ $tab==='linked_hospitals' ? 'bg-[#0da2e7] text-black rounded-md px-4 py-1' : 'text-black px-4 py-1' }}">
                <i class="fa-regular fa-hospital mr-1 text-black"></i>
                <span class="hidden sm:inline text-black"> Linked Hospitals</span>
            </flux:navbar.item>

            <flux:navbar.item 
                wire:click="$set('tab','appointments')" 
                :active="$tab==='appointments'"
                class="{{ $tab==='appointments' ? 'bg-[#0da2e7] text-black rounded-md px-4 py-1' : 'text-black px-4 py-1' }}">
                <i class="fa-regular fa-calendar-days mr-1 text-black"></i>
                <span class="hidden sm:inline text-black"> Appointment Bookings</span>
            </flux:navbar.item>

            <flux:navbar.item 
                wire:click="$set('tab','transactions')" 
                :active="$tab==='transactions'"
                class="{{ $tab==='transactions' ? 'bg-[#0da2e7] text-black rounded-md px-4 py-1' : 'text-black px-4 py-1' }}">
                <i class="fa-solid fa-indian-rupee-sign mr-1 text-black"></i>
                <span class="hidden sm:inline text-black"> eanage Transactions</span>
            </flux:navbar.item>

        </flux:navbar>


        <!-- TAB CONTENT -->
        <div class="pt-6">
            @if ($tab === 'profile')
                @include('livewire.admin.doctor.tabs.profile')
            @endif

            @if ($tab === 'linked_hospitals')
                @include('livewire.admin.doctor.tabs.linked-hospitals')
            @endif

            @if ($tab === 'appointments')
                @include('livewire.admin.doctor.tabs.appointments')
            @endif

            @if ($tab === 'transactions')
                @include('livewire.admin.doctor.tabs.transactions')
            @endif
        </div>

    @endif

</div>


</flux:modal>
