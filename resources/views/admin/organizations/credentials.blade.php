@extends('layouts.admin')

@section('title', 'Organization Admin Credentials')
@section('breadcrumb', 'Dashboard / Organization / Admin Credentials')

@section('content')
@livewire('admin.organization.credentials', ['orgId' => request()->id])
@endsection
