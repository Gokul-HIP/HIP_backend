@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospital Location')
@section('breadcrumb', 'Dashboard')

@section('content')
    @livewire('hospital-admin.hospital-profile.steps.hospital-location', ['hospitalId' => request()->get('hospital_id')])
@endsection