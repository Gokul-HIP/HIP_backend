@extends('technician-admin.layout.technicianadmin')

@section('title', 'Patient Documents')
@section('breadcrumb', 'Patient Documents')

@section('content')
@livewire('technician-admin.patient-documents.view-document', ['booking_id' => $booking_id])
@endsection
