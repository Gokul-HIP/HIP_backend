@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Healthcare Doctors')
@section('breadcrumb', 'Healthcare / Doctors')

@section('content')

@livewire('hospital-admin.users.doctors')
@livewire('admin.doctor.add-doctor', ['organization_id' => auth('filament')->user()?->organization_id])
@livewire('admin.doctor.add-qualification', ['organization_id' => auth('filament')->user()?->organization_id])

@endsection