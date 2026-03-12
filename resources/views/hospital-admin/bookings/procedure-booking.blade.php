@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Procedure Bookings')
@section('breadcrumb', 'Procedure Bookings')

@section('content')

@livewire('hospital-admin.bookings.procedure-booking')
@livewire('hospital-admin.bookings.procedure-update-status')

@endsection
