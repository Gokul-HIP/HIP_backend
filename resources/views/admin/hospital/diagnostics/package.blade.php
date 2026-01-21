@extends('layouts.admin')

@section('title', 'Packages')
@section('breadcrumb', 'Dashboard / Organization / Diagnostics / Packages')

@section('content')

@livewire('admin.organization.diagnostic.package.package-index' , ['diagnosticId' => request()->id])

@endsection
