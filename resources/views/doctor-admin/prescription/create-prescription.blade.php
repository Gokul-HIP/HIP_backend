@extends('doctor-admin.layout.doctor-admin')

@section('title', 'Create Prescription')
@section('breadcrumb', 'Prescription / Create Prescription')

@section('content')

@livewire('doctor-admin.prescription.create-prescription', ['patient_id' => request()->route('patient_id')])

@endsection