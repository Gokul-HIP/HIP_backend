@extends('cashier-admin.layout.cashieradmin')

@section('title', 'Manage Subscriptions')
@section('breadcrumb', 'Subscriptions')

@section('content')

@livewire('cashier.subscriptions.index')

@endsection
