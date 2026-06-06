@extends('layouts.admin')

@section('title', 'Diseases')
@section('breadcrumb', 'Dashboard / Diseases')

@section('content')

@livewire('admin.disease.index')

@endsection