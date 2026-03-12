@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Diagnostic Bookings')
@section('breadcrumb', 'Diagnostic Bookings')

@section('content')

@livewire('hospital-admin.bookings.diagnostic-booking')
@livewire('hospital-admin.bookings.diagnostic-update-status')

@endsection
