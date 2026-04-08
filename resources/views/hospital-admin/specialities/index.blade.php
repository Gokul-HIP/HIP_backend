@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospital Specialities')
@section('breadcrumb', 'Hospitals / Specialities')

@section('content')
@livewire('hospital-admin.specialities.index', ['hospitalId' => request()->id])
@livewire('admin.organization.hospital.specialitie.add-specialitie', ['hospitalId' => request()->id])
@livewire('admin.organization.hospital.specialitie.bulk-add-specialitie')
@livewire('admin.organization.hospital.specialitie.edit-specialitie')
@endsection
