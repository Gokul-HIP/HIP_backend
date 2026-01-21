@extends('layouts.admin')

@section('title', 'Diagnostics')
@section('breadcrumb', 'Dashboard / Organization / Diagnostics')

@section('content')

@livewire('admin.organization.diagnostic.diagnostic' , ['orgId' => request()->id])
@livewire('admin.organization.diagnostic.add-diagnostic' , ['orgId' => request()->id])
@livewire('admin.organization.diagnostic.edit-diagnostic')

@endsection
