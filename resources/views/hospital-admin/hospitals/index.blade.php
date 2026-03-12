@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospitals')
@section('breadcrumb', 'Hospitals')

@section('content')

    @livewire('hospital-admin.hospitals.index')
    
@endsection
