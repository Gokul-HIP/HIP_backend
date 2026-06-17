@extends('layouts.admin')

@section('title', 'Membership Subscriptions')
@section('breadcrumb', 'Dashboard / Settings / Membership Packages / Subscriptions')

@section('content')
@livewire('admin.family-package.subscription-list')
@endsection
