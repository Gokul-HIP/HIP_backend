@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Diagnostics')
@section('breadcrumb', 'Hospital Admin / Diagnostics')

@section('content')

@php($orgId = auth('filament')->user()?->organization_id)

@livewire('admin.organization.diagnostic.diagnostic', ['orgId' => $orgId])
@livewire('admin.organization.diagnostic.add-diagnostic', ['orgId' => $orgId])
@livewire('admin.organization.diagnostic.edit-diagnostic')

@endsection
