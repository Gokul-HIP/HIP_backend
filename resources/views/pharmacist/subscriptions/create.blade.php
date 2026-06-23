@extends('pharmacist-admin.layout.pharmacistadmin')

@section('title', 'New Subscription')
@section('breadcrumb', 'Subscriptions')

@section('content')

@livewire('pharmacist.subscriptions.create')

@endsection
