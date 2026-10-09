@extends('layouts.admin')

@section('title', 'Patient')
@section('breadcrumb', 'Dashboard / Patients')

@section('content')
@livewire('admin.member-profile.view-member-profile', ['user' => $user])
@endsection
