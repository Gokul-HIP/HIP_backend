@extends('layouts.admin')

@section('title', 'Cashier Admin Credentials')
@section('breadcrumb', 'Dashboard / Organization / Cashier Admin Credentials')

@section('content')

@livewire('admin.organization.hospital.credentials',['orgId' => request()->id])

@endsection
