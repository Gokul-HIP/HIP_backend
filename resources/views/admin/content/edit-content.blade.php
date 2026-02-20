@extends('layouts.admin')

@section('title', 'Content & Reviews / Edit Content')
@section('breadcrumb', 'Content & Reviews / Edit Content')

@section('content')

@livewire('admin.content.edit-content', ['id' => request()->id])

@endsection