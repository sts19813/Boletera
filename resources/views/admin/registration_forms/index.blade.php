@extends('layouts.app')

@section('title', 'Formularios de inscripción')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold text-gray-800">Formularios de inscripción</h1>
        <a href="{{ route('admin.registration-forms.create') }}" class="btn btn-primary">Nuevo formulario</a>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Nombre</th><th>Slug</th><th>Campos</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @forelse($forms as $f)
                            <tr>
                                <td>{{ $f->name }}</td>
                                <td>{{ $f->slug }}</td>
                                <td>{{ count($f->schema['fields'] ?? []) }}</td>
                                <td>{{ $f->is_active ? 'Activo' : 'Inactivo' }}</td>
                                <td class="text-end d-flex gap-2 justify-content-end">
                                    <a href="{{ route('admin.registration-forms.edit', $f) }}" class="btn btn-sm btn-light-primary">Editar</a>
                                    <form method="POST" action="{{ route('admin.registration-forms.destroy', $f) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light-danger" onclick="return confirm('¿Eliminar formulario?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Sin formularios</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $forms->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
