@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospitals')
@section('breadcrumb', 'Hospitals')

@section('content')

    @livewire('hospital-admin.hospitals.index')
    @livewire('hospital-admin.hospitals.add-hospital')
    @livewire('hospital-admin.hospitals.edit-hospital')
    
@endsection
