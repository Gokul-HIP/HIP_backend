@extends('layouts.admin')

@section('title', 'Admins')
@section('breadcrumb', 'Dashboard / Admins')

@section('content')
@livewire('admin.admins.index')
@endsection
