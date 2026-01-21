@extends('layouts.admin')

@section('title', 'Procedure Details')
@section('breadcrumb', 'Dashboard / Hospital / Procedures / Details')

@section('content')

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

<style>
/* Add your custom styles here */
.info-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.info-label {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 4px;
}

.info-value {
    font-size: 16px;
    color: #111827;
    font-weight: 500;
}
</style>

<div class="max-w-7xl mx-auto py-6 space-y-6">
    
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">Procedure Details</h2>
                <p class="text-gray-500 mt-1">View detailed information about this procedure</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.procedure.index', ['id' => request()->id]) }}" 
                   class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg">
                    <i data-lucide="arrow-left" class="w-4 h-4 inline"></i> Back
                </a>
                <button class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
                    <i data-lucide="edit" class="w-4 h-4 inline"></i> Edit
                </button>
            </div>
        </div>
    </div>

    {{-- Uncomment when you create the procedure details Livewire component --}}
    {{-- @livewire('admin.organization.hospital.procedure.procedure-details', ['procedureId' => $id]) --}}

    <!-- Placeholder Content -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="info-card">
            <div class="info-label">Procedure Name</div>
            <div class="info-value">-</div>
        </div>
        
        <div class="info-card">
            <div class="info-label">Procedure Code</div>
            <div class="info-value">-</div>
        </div>
        
        <div class="info-card">
            <div class="info-label">Speciality</div>
            <div class="info-value">-</div>
        </div>
        
        <div class="info-card">
            <div class="info-label">Estimated Time</div>
            <div class="info-value">-</div>
        </div>
        
        <div class="info-card">
            <div class="info-label">Cost</div>
            <div class="info-value">-</div>
        </div>
        
        <div class="info-card">
            <div class="info-label">Status</div>
            <div class="info-value">-</div>
        </div>
        
        <div class="info-card md:col-span-2">
            <div class="info-label">Description</div>
            <div class="info-value">-</div>
        </div>
    </div>

</div>

<script>
lucide.createIcons();
</script>

@endsection

