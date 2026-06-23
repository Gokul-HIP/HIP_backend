@extends('technician-admin.layout.technicianadmin')

@section('title', 'Manage Subscriptions')
@section('breadcrumb', 'Subscriptions')

@section('content')

@livewire('technician.subscriptions.index')

@endsection
