@extends('layouts.admin')

@section('title', 'Caregiver Booking Details')
@section('breadcrumb', 'Dashboard / Caregiver Booking / Details')

@section('content')

@livewire('admin.caregiver-booking.appointment-details', ['id' => $id])

@livewire('admin.caregiver-booking.add-note', ['id' => $id])

@livewire('admin.caregiver-booking.edit-note', ['id' => $id])

@endsection
