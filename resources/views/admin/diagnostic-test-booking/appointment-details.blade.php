@extends('layouts.admin')

@section('title', 'Diagnostic Test Booking Details')
@section('breadcrumb', 'Dashboard / Diagnostic Test Booking / Details')

@section('content')

@livewire('admin.diagnostic-test-booking.appointment-details', ['id' => $id])

@livewire('admin.diagnostic-test-booking.add-note', ['id' => $id])

@livewire('admin.diagnostic-test-booking.edit-note', ['id' => $id])

@endsection
