@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Diagnostic Booking Details')

@section('content')
@livewire('hospital-admin.bookings.diagnostic-appointment-details', ['id' => $id])
@livewire('hospital-admin.bookings.diagnostic-add-note')
@livewire('hospital-admin.bookings.diagnostic-edit-note')
@endsection
