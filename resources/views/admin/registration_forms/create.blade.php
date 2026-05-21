@extends('layouts.app')

@section('title', 'Crear formulario')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold text-gray-800">Crear formulario</h1>
        <a href="{{ route('admin.registration-forms.index') }}" class="btn btn-primary">Regresar</a>
    </div>
    <form method="POST" action="{{ route('admin.registration-forms.store') }}">
        @csrf
        @include('admin.registration_forms._form')
    </form>
</div>
@endsection
