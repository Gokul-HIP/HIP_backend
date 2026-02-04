@extends('layouts.admin')

@section('title', 'Stem Cell Booking Appointment Details')
@section('breadcrumb', 'Dashboard / Stem Cell Booking / Appointment Details')

@section('content')

@livewire('admin.stem-cell-booking.appointment-details', ['id' => $id])
@livewire('admin.stem-cell-booking.add-note', ['id' => $id])
@livewire('admin.stem-cell-booking.edit-note', ['id' => $id])

@endsection

