@extends('receptionist-admin.layout.receptionistadmin')

@section('title', 'Patient Bookings')
@section('breadcrumb', 'Patient Bookings')

@section('content')
@livewire('receptionist-admin.patient-bookings.index')
@endsection
