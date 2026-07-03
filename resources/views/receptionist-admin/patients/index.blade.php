@extends('receptionist-admin.layout.receptionistadmin')

@section('title', 'Patients')
@section('breadcrumb', 'Patients')

@section('content')
@livewire('receptionist-admin.patients.index')
@endsection
