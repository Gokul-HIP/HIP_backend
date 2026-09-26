@extends('receptionist-admin.layout.receptionistadmin')

@section('title', 'Doctor Bookings')
@section('breadcrumb', 'Doctor Bookings')

@section('content')
@livewire('receptionist-admin.bookings.doctor-booking')
@livewire('receptionist-admin.bookings.doctor-update-status')
@livewire('receptionist-admin.bookings.doctor-reschedule')
@endsection
