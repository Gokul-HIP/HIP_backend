@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Packages')
@section('breadcrumb', 'Hospital Admin / Diagnostics / Packages')

@section('content')

@livewire('admin.organization.diagnostic.package.package-index', ['diagnosticId' => request()->id])

@endsection
