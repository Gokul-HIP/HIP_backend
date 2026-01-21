@extends('layouts.admin')

@section('title', 'Doctor Profile')
@section('breadcrumb', 'Dashboard / Doctor Profile')

@section('content')

@livewire('admin.doctor.doctor-profile')
@livewire('admin.doctor.add-doctor')
@livewire('admin.doctor.edit-doctor')
@livewire('admin.doctor.add-qualification')
@livewire('admin.doctor.doctor-details')

@endsection