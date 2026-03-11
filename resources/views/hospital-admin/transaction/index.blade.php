@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospital Transactions')
@section('breadcrumb', 'Hospitals / Transactions')

@section('content')

@livewire('hospital-admin.transaction.index')

@endsection
