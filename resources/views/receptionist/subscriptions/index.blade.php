@extends('receptionist-admin.layout.receptionistadmin')

@section('title', 'Manage Subscriptions')
@section('breadcrumb', 'Subscriptions')

@section('content')

@livewire('receptionist.subscriptions.index')

@endsection
