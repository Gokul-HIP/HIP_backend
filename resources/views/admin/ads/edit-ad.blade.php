@extends('layouts.admin')

@section('title', 'Ads / Edit')
@section('breadcrumb', 'Ads / Edit')

@section('content')
@livewire('admin.ads.edit-ad', ['id' => $id])
@endsection
