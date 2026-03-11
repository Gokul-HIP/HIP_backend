@extends('doctor-admin.layout.doctor-admin')

@section('title', 'Referral Details')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('doctor-admin.referral.referral-details', ['id' => $id])

@endsection
