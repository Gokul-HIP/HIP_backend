@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Contact Details')
@section('breadcrumb', 'Dashboard')

@section('content')
    @livewire('hospital-admin.hospital-profile.steps.contact-details', ['hospitalId' => request()->get('hospital_id')])
@endsection
