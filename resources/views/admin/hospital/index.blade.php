@extends('layouts.admin')

@section('title', 'Hospitals')
@section('breadcrumb', 'Dashboard / Organization / Hospitals')

@section('content')

@livewire('admin.organization.hospital.hospital-index', ['orgId' => request()->id])
@livewire('admin.organization.hospital.add-hospital', ['orgId' => request()->id])
@livewire('admin.organization.hospital.edit-hospital')
@livewire('admin.organization.hospital.credentials',['orgId' => request()->id])
@endsection