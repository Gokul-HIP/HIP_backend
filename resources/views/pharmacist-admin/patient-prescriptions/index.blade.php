@extends('pharmacist-admin.layout.pharmacistadmin')

@section('title', 'Patient Prescription')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('pharmacist-admin.patient-prescriptions.index')

@endsection
