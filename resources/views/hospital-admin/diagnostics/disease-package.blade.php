@extends('layouts.admin')

@section('title', 'Disease Packages')
@section('breadcrumb', 'Dashboard / Diagnostics / Disease Packages')

@section('content')

@livewire('admin.organization.diagnostic.disease-package.disease-package-index', ['diagnosticId' => request()->id])

@endsection
