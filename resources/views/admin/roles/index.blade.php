@extends('layouts.app')

@section('title', 'Roles')

@section('content')

    {{-- ================= CREAR ROL ================= --}}
    <div class="card shadow-sm mb-7">
        <div class="card-header">
            <h3 class="card-title">Nuevo Rol</h3>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf

                {{-- Nombre --}}
                <div class="mb-6">
                    <label class="form-label fw-bold">Nombre del Rol</label>
                    <input type="text" name="name" class="form-control" placeholder="Ej. Organizador">
                </div>

                {{-- Permisos --}}
                <div class="mb-6">
                    <h5 class="fw-bold mb-4 text-primary">Permisos asignados</h5>

                    <div class="row">
                        @foreach($permissions as $permission)
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="permissions[]"
                                        value="{{ $permission->name }}">

                                    <label class="form-check-label">
                                        {{ $permission->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="text-end">
                    <button class="btn btn-primary px-6">
                        Crear Rol
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ================= LISTADO DE ROLES ================= --}}
    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title">Roles existentes</h3>
        </div>

        <div class="card-body">
            <table class="table align-middle table-row-dashed">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Permisos asignados</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td>
                                <span class="fw-bold">
                                    {{ ucfirst($role->name) }}
                                </span>
                            </td>

                            <td>
                                @if($role->permissions->count())
                                    @foreach($role->permissions as $permission)
                                        <span class="badge badge-light-primary me-1 mb-1">
                                            {{ $permission->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted">Sin permisos</span>
                                @endif
                            </td>

                            <td class="text-end" width="250">
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-light-primary me-2">
                                    Editar
                                </a>

                                <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este rol?')">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-light-danger">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection