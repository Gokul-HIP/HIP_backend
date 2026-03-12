@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Doctor Bookings')
@section('breadcrumb', 'Doctor Bookings')

@section('content')

@livewire('hospital-admin.bookings.doctor-booking')
@livewire('hospital-admin.bookings.doctor-update-status')

@endsection
