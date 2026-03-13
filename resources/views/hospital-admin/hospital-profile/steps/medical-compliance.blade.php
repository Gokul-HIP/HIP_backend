@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Medical Compliance')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('hospital-admin.hospital-profile.steps.medical-compliance', ['hospitalId' => request()->get('hospital_id')])

@endsection