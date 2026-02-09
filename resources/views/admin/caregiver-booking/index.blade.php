@extends('layouts.admin')

@section('title', 'Caregiver Booking')
@section('breadcrumb', 'Dashboard / Caregiver Booking')

@section('content')

@livewire('admin.caregiver-booking.index')
@livewire('admin.caregiver-booking.update-status')

@endsection
