@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Ads / Edit Ad')

@section('content')

@livewire('hospital-admin.ads.edit-ad', ['id' => request()->route('id')])

@endsection
