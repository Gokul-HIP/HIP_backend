@extends('layouts.admin')

@section('title', 'Member Profile')
@section('breadcrumb', 'Dashboard / Member Profile')

@section('content')

@livewire('admin.member-profile.member-index')
@livewire('admin.member-profile.view-member-profile')


@endsection