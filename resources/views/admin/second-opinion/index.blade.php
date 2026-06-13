@extends('layouts.admin')

@section('title', 'Second Opinion Bookings')
@section('breadcrumb', 'Dashboard / Second Opinion Bookings')

@section('content')

@livewire('admin.second-opinion.index')
@livewire('admin.second-opinion.update-status')

@endsection
