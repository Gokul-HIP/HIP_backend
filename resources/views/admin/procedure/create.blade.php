@extends('layouts.admin')

@section('title', 'Add Procedure')
@section('breadcrumb', 'Dashboard / Hospital / Procedures / Add')

@section('content')

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

<style>
/* Add your custom styles here */
</style>

<div class="max-w-7xl mx-auto py-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-6">Add New Procedure</h2>
        
        {{-- Uncomment when you create the add procedure Livewire component --}}
        {{-- @livewire('admin.organization.hospital.procedure.add-procedure', ['hospitalId' => request()->hospital_id]) --}}
        
        <p class="text-gray-500">Add Procedure form component will be implemented here</p>
    </div>
</div>

<script>
lucide.createIcons();
</script>

@endsection

