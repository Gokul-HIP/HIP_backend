@extends('pharmacist-admin.layout.pharmacistadmin')

@section('title', 'Prescription Order')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('pharmacist-admin.prescription-orders.show', ['order' => request()->route('order')])

@endsection
