@extends('layouts.admin')

@section('title', 'Doctor Profile')
@section('breadcrumb', 'Dashboard / Doctor Profile')

@section('content')

@livewire('admin.doctor.doctor-profile' , ['organization_id' => $organization_id])
@livewire('admin.doctor.add-doctor' , ['organization_id' => $organization_id])
@livewire('admin.doctor.edit-doctor' , ['organization_id' => $organization_id])
@livewire('admin.doctor.add-qualification' , ['organization_id' => $organization_id])
@livewire('admin.doctor.doctor-details' , ['organization_id' => $organization_id])
@livewire('admin.doctor.credentials' , ['organization_id' => $organization_id])

@endsection
