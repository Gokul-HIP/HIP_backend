@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Hospital Procedures')
@section('breadcrumb', 'Hospitals / Procedures')

@section('content')
@livewire('hospital-admin.procedures.index', ['hospitalId' => request()->id])
@endsection
