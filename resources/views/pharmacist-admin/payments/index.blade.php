@extends('pharmacist-admin.layout.pharmacistadmin')

@section('title', 'Payments')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('pharmacist-admin.payments.index')

@endsection