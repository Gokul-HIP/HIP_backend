@extends('technician-admin.layout.technicianadmin')

@section('title', 'Diagnostic Test Bookings')
@section('breadcrumb', 'Diagnostic Bookings')

@section('content')
@livewire('technician-admin.diagnostic-test-booking.index')
@livewire('technician-admin.diagnostic-test-booking.update-status')
@endsection
