@extends('pharmacist-admin.layout.pharmacistadmin')

@section('title', 'Manage Subscriptions')
@section('breadcrumb', 'Subscriptions')

@section('content')

@livewire('pharmacist.subscriptions.index')

@endsection
