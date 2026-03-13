@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospital Capacity')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('hospital-admin.hospital-profile.steps.hospital-capacity', ['hospitalId' => request()->get('hospital_id')])

@endsection