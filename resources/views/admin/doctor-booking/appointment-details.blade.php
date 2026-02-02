@extends('layouts.admin')

@section('title', 'Doctor Booking Details')
@section('breadcrumb', 'Dashboard / Doctor Booking / Details')

@section('content')

@livewire('admin.doctor-booking.appointment-details', ['id' => $id])

@livewire('admin.doctor-booking.add-note', ['id' => $id])

@livewire('admin.doctor-booking.edit-note', ['id' => $id])

@endsection