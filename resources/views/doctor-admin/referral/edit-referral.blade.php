@extends('doctor-admin.layout.doctor-admin')

@section('title', 'Edit Referral')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('doctor-admin.referral.edit-referral', ['id' => $id])

@endsection
