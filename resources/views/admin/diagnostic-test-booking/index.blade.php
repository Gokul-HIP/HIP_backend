@extends('layouts.admin')

@section('title', 'Diagnostic Test Booking')
@section('breadcrumb', 'Dashboard / Diagnostic Test Booking')

@section('content')

@livewire('admin.diagnostic-test-booking.index')
@livewire('admin.diagnostic-test-booking.update-status')

@endsection
