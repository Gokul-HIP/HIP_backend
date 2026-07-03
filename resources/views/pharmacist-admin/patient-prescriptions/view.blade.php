@extends('pharmacist-admin.layout.pharmacistadmin')

@section('title', 'Patient Prescriptions')
@section('breadcrumb', 'Patient Prescription')

@section('content')

@livewire('pharmacist-admin.patient-prescriptions.view-prescriptions', [
    'patient_id' => request('patient_id'),
    'member_id' => request('member_id'),
])

@endsection
