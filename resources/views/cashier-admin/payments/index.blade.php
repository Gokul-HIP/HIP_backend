@extends('cashier-admin.layout.cashieradmin')

@section('title', 'Payments')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('cashier-admin.payments.index')

@endsection