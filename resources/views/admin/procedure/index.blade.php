@extends('layouts.admin')

@section('title', 'Procedures')
@section('breadcrumb', 'Dashboard / Organization / Procedures')

@section('content')

@livewire('admin.organization.hospital.procedure.procedure-index', ['hospitalId' => request()->id])

@livewire('admin.organization.hospital.procedure.add-procedure', ['hospitalId' => request()->id])

@livewire('admin.organization.hospital.procedure.edit-procedure')

@livewire('admin.organization.hospital.procedure.bulk-add-procedure', ['hospitalId' => request()->id])


@endsection