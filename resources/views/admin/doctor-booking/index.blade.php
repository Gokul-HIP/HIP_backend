@extends('layouts.admin')

@section('title', 'Doctor Booking')
@section('breadcrumb', 'Dashboard / Doctor Booking')

@section('content')

@livewire('admin.doctor-booking.index')
@livewire('admin.doctor-booking.update-status')

@endsection