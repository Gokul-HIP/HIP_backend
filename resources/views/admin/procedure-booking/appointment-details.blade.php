@extends('layouts.admin')

@section('title', 'Procedure Booking Appointment Details')
@section('breadcrumb', 'Dashboard / Procedure Booking / Appointment Details')

@section('content')

@livewire('admin.procedure-booking.appointment-details', ['id' => $id])
@livewire('admin.procedure-booking.add-note', ['id' => $id])
@livewire('admin.procedure-booking.edit-note', ['id' => $id])

@endsection

