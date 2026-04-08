@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospital Procedures')
@section('breadcrumb', 'Hospitals / Procedures')

@section('content')
@livewire('hospital-admin.procedures.index', ['hospitalId' => request()->id])
@livewire('admin.organization.hospital.procedure.add-procedure', ['hospitalId' => request()->id])
@livewire('admin.organization.hospital.procedure.edit-procedure')
@livewire('admin.organization.hospital.procedure.bulk-add-procedure', ['hospitalId' => request()->id])
@endsection
