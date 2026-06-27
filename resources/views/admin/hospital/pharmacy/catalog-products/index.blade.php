@extends('layouts.admin')

@section('title', 'Pharmacy Catalog Products')
@section('breadcrumb', 'Dashboard / Organization / Pharmacy / Catalog Products')

@section('content')

@livewire('admin.organization.pharmacy.catalog-products.index', ['pharmacyId' => request()->id])
@livewire('admin.organization.pharmacy.catalog-products.create', ['pharmacyId' => request()->id])
@livewire('admin.organization.pharmacy.catalog-products.edit', ['pharmacyId' => request()->id])

@endsection
