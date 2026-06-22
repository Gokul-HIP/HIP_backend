@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Second Opinion Bookings')

@section('content')
    @livewire('hospital-admin.bookings.second-opinion-booking')
    @livewire('hospital-admin.bookings.second-opinion-update-status')
@endsection
