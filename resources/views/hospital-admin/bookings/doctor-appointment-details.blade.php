@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Doctor Booking Details')

@section('content')
@livewire('hospital-admin.bookings.doctor-appointment-details', ['id' => $id])
@livewire('hospital-admin.bookings.doctor-add-note')
@livewire('hospital-admin.bookings.doctor-edit-note')
@endsection
