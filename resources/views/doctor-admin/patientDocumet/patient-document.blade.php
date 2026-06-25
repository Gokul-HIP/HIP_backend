@extends('doctor-admin.layout.doctor-admin')

@section('title', 'Patient Documents')
@section('breadcrumb', 'Patient Document')

@section('content')

@livewire('doctor-admin.patientDocument.patient-document')

@endsection