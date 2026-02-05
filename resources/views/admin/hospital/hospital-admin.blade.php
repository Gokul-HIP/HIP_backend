@extends('layouts.admin')

@section('title', 'Hospital Admin')
@section('breadcrumb', 'Dashboard / Organization / Hospital Admin')

@section('content')

@livewire('admin.organization.hospital.credentials',['orgId' => request()->id])

@endsection