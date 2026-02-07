@extends('layouts.admin')

@section('title', 'Edit Caregiver')
@section('breadcrumb', 'Dashboard / Caregiver / Edit Caregiver')

@section('content')

@livewire('admin.caregiver.edit-caregiver', ['id' => $id])

@endsection