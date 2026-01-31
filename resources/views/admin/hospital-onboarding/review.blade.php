@extends('layouts.admin')

@section('title', 'Hospital Onboarding Review')
@section('breadcrumb', 'Dashboard / Hospital Onboarding / Review')

@section('content')

@livewire('admin.hospital-onboarding.review', ['id' => request()->id])

@endsection