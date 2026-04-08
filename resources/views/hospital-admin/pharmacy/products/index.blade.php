@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Pharmacy Products')
@section('breadcrumb', 'Hospital Admin / Pharmacy / Products')

@section('content')

@livewire('admin.organization.pharmacy.products.index', ['pharmacyId' => request()->id])
@livewire('admin.organization.pharmacy.products.create', ['pharmacyId' => request()->id])
@livewire('admin.organization.pharmacy.products.edit', ['pharmacyId' => request()->id])
@livewire('admin.organization.pharmacy.products.add-bulk-medicines')

@endsection
