@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Pharmacy')
@section('breadcrumb', 'Hospital Admin / Pharmacy')

@section('content')

@php($orgId = auth('filament')->user()?->organization_id)

@livewire('admin.organization.pharmacy.pharmacy', ['orgId' => $orgId])
@livewire('admin.organization.pharmacy.add-pharmacy', ['orgId' => $orgId])
@livewire('admin.organization.pharmacy.edit-pharmacy')

@endsection
