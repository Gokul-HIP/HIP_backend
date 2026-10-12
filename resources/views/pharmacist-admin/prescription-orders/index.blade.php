@extends('pharmacist-admin.layout.pharmacistadmin')

@section('title', 'Prescription Orders')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('pharmacist-admin.prescription-orders.index')

@endsection
