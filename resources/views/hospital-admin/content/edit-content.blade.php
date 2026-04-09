@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'Content & Reviews / Edit Content')

@section('content')

@livewire('hospital-admin.content.edit-content', ['id' => request()->route('id')])

@endsection
