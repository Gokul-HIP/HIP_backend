@extends('layouts.admin')

@section('title', 'Second Opinion Details')
@section('breadcrumb', 'Dashboard / Second Opinion / Details')

@section('content')

@livewire('admin.second-opinion.appointment-details', ['id' => $id])

@livewire('admin.second-opinion.add-note', ['id' => $id])

@livewire('admin.second-opinion.edit-note', ['id' => $id])

@endsection
