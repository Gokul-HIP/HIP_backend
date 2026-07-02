@extends('technician-admin.layout.technicianadmin')

@section('title', 'Patients')
@section('breadcrumb', 'Patients')

@section('content')
@livewire('technician-admin.patients.index')
@endsection
