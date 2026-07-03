@extends('receptionist-admin.layout.receptionistadmin')

@section('title', 'Doctor Booking Details')
@section('breadcrumb', 'Doctor Booking Details')

@section('content')
@livewire('receptionist-admin.bookings.doctor-appointment-details', ['id' => $id])
@livewire('receptionist-admin.bookings.doctor-add-note')
@livewire('receptionist-admin.bookings.doctor-edit-note')
@endsection
