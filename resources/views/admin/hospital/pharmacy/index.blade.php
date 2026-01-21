@extends('layouts.admin')

@section('title', 'Pharmacy')
@section('breadcrumb', 'Dashboard / Organization / Pharmacy')

@section('content')

<style>
/* Action Menu Positioning */
.action-menu {
    position: fixed;
    width: 260px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    z-index: 9999;
}

.action-menu::-webkit-scrollbar {
    width: 0;
}
.action-menu {
    scrollbar-width: none;
}

.action-btn {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

</style>

@livewire('admin.organization.pharmacy.pharmacy',['orgId' => request()->id])
@livewire('admin.organization.pharmacy.add-pharmacy',['orgId' => request()->id])
@livewire('admin.organization.pharmacy.edit-pharmacy')

@endsection