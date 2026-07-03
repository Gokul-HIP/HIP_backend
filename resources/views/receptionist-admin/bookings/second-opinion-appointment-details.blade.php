@extends('receptionist-admin.layout.receptionistadmin')

@section('title', 'Second Opinion Details')
@section('breadcrumb', 'Second Opinion Details')

@section('content')
@livewire('receptionist-admin.bookings.second-opinion-appointment-details', ['id' => $id])
@livewire('receptionist-admin.bookings.second-opinion-add-note')
@livewire('receptionist-admin.bookings.second-opinion-edit-note')
@endsection
