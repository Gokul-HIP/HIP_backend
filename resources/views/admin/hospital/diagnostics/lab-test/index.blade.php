@extends('layouts.admin')

@section('title', 'Lab Tests')
@section('breadcrumb', 'Dashboard / Organization / Diagnostics / Lab Tests')

@section('content')

@livewire('admin.organization.diagnostic.test.lab-test-index', ['diagnosticId' => request()->id])
@livewire('admin.organization.diagnostic.test.add-lab-test', ['diagnosticId' => request()->id])
@livewire('admin.organization.diagnostic.test.edit-lab-test')
@livewire('admin.organization.diagnostic.test.add-bulk-test', ['diagnosticId' => request()->id])

@endsection
