@extends('layouts.admin')

@section('title', 'Procedure Booking')
@section('breadcrumb', 'Dashboard / Procedure Booking')

@section('content')

@livewire('admin.procedure-booking.index')
@livewire('admin.procedure-booking.update-status')

@endsection

