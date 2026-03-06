@extends('layouts.admin')

@section('title', 'Hospital Reviews / Manage Hospital Reviews')
@section('breadcrumb', 'Hospital Reviews / Manage Hospital Reviews')

@section('content')

@livewire('admin.hospital-review.view-review', ['reviewId' => $id])

@endsection