@extends('layouts.admin')

@section('title', 'Organizations')
@section('breadcrumb', 'Dashboard / Organizations')

@section('content')

@livewire('admin.organization.organization')

@livewire('admin.organization.add-organization')
@livewire('admin.organization.edit-organization')
@livewire('admin.organization.delete-organization')


@endsection