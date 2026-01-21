@extends('layouts.admin')

@section('title', 'Bulk Add Procedures')
@section('breadcrumb', 'Dashboard / Hospital / Procedures / Bulk Add')

@section('content')

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

<style>
/* Add your custom styles here */
</style>

<div class="max-w-7xl mx-auto py-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-6">Bulk Add Procedures</h2>
        
        {{-- Uncomment when you create the bulk add procedure Livewire component --}}
        {{-- @livewire('admin.organization.hospital.procedure.add-bulk-procedure', ['hospitalId' => request()->hospital_id]) --}}
        
        <p class="text-gray-500">Bulk Add Procedures form component will be implemented here</p>
    </div>
</div>

<script>
lucide.createIcons();
</script>

@endsection

