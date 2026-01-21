@extends('layouts.admin')

@section('title', 'Specialities')
@section('breadcrumb', 'Dashboard / Hospital / Specialities')

@section('content')

@livewire('admin.organization.hospital.specialitie.specialitie-index', ['hospitalId' => request()->id])

@livewire('admin.organization.hospital.specialitie.add-specialitie', ['hospitalId' => request()->id])

@livewire('admin.organization.hospital.specialitie.bulk-add-specialitie')

@livewire('admin.organization.hospital.specialitie.edit-specialitie')

@endsection
