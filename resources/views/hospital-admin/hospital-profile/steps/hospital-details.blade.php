@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospital Profile')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('hospital-admin.hospital-profile.steps.hospital-details', ['hospitalId' => request()->get('hospital_id')])

@endsection