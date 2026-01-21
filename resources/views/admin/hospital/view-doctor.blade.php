@extends('layouts.admin')

@section('title', 'View Doctor')
@section('breadcrumb', 'Dashboard / Organization / View Doctor')

@section('content')

@livewire('admin.organization.hospital.view-doctor.linked-doctor', ['hospitalId' => request()->id])
@livewire('admin.organization.hospital.view-doctor.assign-doctor', ['hospitalId' => request()->id])
@livewire('admin.organization.hospital.view-doctor.edit-doctor-assignment', ['hospitalId' => request()->id])

@endsection