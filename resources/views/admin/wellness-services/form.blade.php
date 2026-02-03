@extends('layouts.admin')

@php
    $id = request()->route('id');
    $isEdit = !empty($id);
@endphp

@section('title', $isEdit ? 'Edit Wellness Centre' : 'Add Wellness Centre')
@section('breadcrumb', 'Dashboard / Wellness Services / ' . ($isEdit ? 'Edit' : 'Add'))

@section('content')

@livewire('admin.wellness-services.form', ['id' => $id])

@endsection

