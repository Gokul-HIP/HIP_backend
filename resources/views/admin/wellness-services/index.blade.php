@extends('layouts.admin')

@section('title', 'Wellness Services')
@section('breadcrumb', 'Dashboard / Wellness Services')

@section('content')

@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('toast', { type: 'success', message: '{{ session('success') }}' });
            }
        });
    </script>
@endif

@livewire('admin.wellness-services.index')

@endsection