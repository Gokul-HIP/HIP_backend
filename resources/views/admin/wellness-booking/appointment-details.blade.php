@extends('layouts.admin')

@section('title', 'Wellness Booking Appointment Details')
@section('breadcrumb', 'Dashboard / Wellness Booking / Appointment Details')

@section('content')

@livewire('admin.wellness-booking.appointment-details', ['id' => $id])
@livewire('admin.wellness-booking.add-note')
@livewire('admin.wellness-booking.edit-note')

@endsection

