@extends('layouts.admin')

@section('title', 'Doctor Booking')
@section('breadcrumb', 'Dashboard / Doctor Booking')

@section('content')

@livewire('admin.doctor-booking.index')

@endsection