@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Second Opinion Details')

@section('content')
    @livewire('hospital-admin.bookings.second-opinion-appointment-details', ['id' => $id])
    @livewire('hospital-admin.bookings.second-opinion-add-note')
    @livewire('hospital-admin.bookings.second-opinion-edit-note')
@endsection
