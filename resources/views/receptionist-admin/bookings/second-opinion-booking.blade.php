@extends('receptionist-admin.layout.receptionistadmin')

@section('title', 'Second Opinion Bookings')
@section('breadcrumb', 'Second Opinion Bookings')

@section('content')
@livewire('receptionist-admin.bookings.second-opinion-booking')
@livewire('receptionist-admin.bookings.second-opinion-update-status')
@endsection
