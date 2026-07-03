@extends('receptionist-admin.layout.receptionistadmin')

@section('title', 'Diagnostic Test Bookings')
@section('breadcrumb', 'Diagnostic Bookings')

@section('content')
@livewire('receptionist-admin.bookings.diagnostic-booking')
@livewire('receptionist-admin.bookings.diagnostic-update-status')
@endsection
