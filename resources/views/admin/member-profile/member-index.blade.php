@extends('layouts.admin')

@section('title', 'Patients')
@section('breadcrumb', 'Dashboard / Patients')

@section('content')

@livewire('admin.member-profile.member-index')
@livewire('admin.member-profile.view-member-profile')


@endsection