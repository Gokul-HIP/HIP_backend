@extends('technician-admin.layout.technicianadmin')

@section('title', 'Upload Report')
@section('breadcrumb', 'Upload Report / Create')

@section('content')
@livewire('technician-admin.upload-report.create', ['booking_id' => $booking_id])
@endsection
