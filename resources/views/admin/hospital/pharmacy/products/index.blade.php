@extends('layouts.admin')

@section('title', 'Pharmacy Products')
@section('breadcrumb', 'Dashboard / Organization / Pharmacy / Products')

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

@livewire('admin.organization.pharmacy.products.index',['pharmacyId' => request()->id])
@livewire('admin.organization.pharmacy.products.create',['pharmacyId' => request()->id])
@livewire('admin.organization.pharmacy.products.edit',['pharmacyId' => request()->id])
@livewire('admin.organization.pharmacy.products.add-bulk-medicines')

@endsection