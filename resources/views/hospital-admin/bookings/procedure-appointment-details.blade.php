@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Procedure Booking Details')

@section('content')
@livewire('hospital-admin.bookings.procedure-appointment-details', ['id' => $id])
@livewire('hospital-admin.bookings.procedure-add-note')
@livewire('hospital-admin.bookings.procedure-edit-note')
@endsection
