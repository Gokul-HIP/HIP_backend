@extends('receptionist-admin.layout.receptionistadmin')

@section('title', 'Diagnostic Booking Details')
@section('breadcrumb', 'Diagnostic Booking Details')

@section('content')
@livewire('receptionist-admin.bookings.diagnostic-appointment-details', ['id' => $id])
@livewire('receptionist-admin.bookings.diagnostic-add-note')
@livewire('receptionist-admin.bookings.diagnostic-edit-note')
@endsection
