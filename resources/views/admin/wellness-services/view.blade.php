@extends('layouts.admin')

@section('title', 'Wellness Centre Details')
@section('breadcrumb', 'Dashboard / Wellness Services / View Details')

@section('content')

@livewire('admin.wellness-services.view-wellness-center', ['id' => request()->route('id')])

@endsection

