@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospital Specialities')
@section('breadcrumb', 'Hospitals / Specialities')

@section('content')
@livewire('hospital-admin.specialities.index', ['hospitalId' => request()->id])
@endsection
