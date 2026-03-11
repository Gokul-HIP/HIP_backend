@extends('doctor-admin.layout.doctor-admin')

@section('title', 'Members')
@section('breadcrumb', 'Dashboard')

@section('content')

@livewire('doctor-admin.members.index')

@endsection