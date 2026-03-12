@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Doctor Profile')
@section('breadcrumb', 'Healthcare / Doctors / Profile')

@section('content')
@livewire('hospital-admin.users.doctor-profile', ['id' => $id])
@livewire('hospital-admin.bookings.doctor-update-status')
@endsection
