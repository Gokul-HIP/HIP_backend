@extends('technician-admin.layout.technicianadmin')

@section('title', 'Diagnostic Booking Details')
@section('breadcrumb', 'Diagnostic Bookings / Details')

@section('content')
@livewire('technician-admin.diagnostic-test-booking.appointment-details', ['id' => $id])
@livewire('technician-admin.diagnostic-test-booking.add-note')
@livewire('technician-admin.diagnostic-test-booking.edit-note')
@endsection
