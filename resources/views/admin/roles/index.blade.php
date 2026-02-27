@extends('layouts.app')

@section('title', 'Roles')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Crear Rol</h3>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf

                <div class="mb-5">
                    <label class="form-label">Nombre del Rol</label>
                    <input type="text" name="name" class="form-control">
                </div>

                <div class="mb-5">
                    <label class="form-label">Permisos</label>

                    @foreach($permissions as $permission)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                value="{{ $permission->name }}">

                            <label class="form-check-label">
                                {{ $permission->name }}
                            </label>
                        </div>
                    @endforeach
                </div>

                <button class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>

    <hr>

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Roles existentes</h3>
        </div>

        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Permisos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>
                                @foreach($role->permissions as $permission)
                                    <span class="badge badge-light-primary">
                                        {{ $permission->name }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="text-end">
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-light-primary me-2">
                                    <i class="ki-duotone ki-pencil fs-5"></i>
                                    Editar
                                </a>

                                <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este rol?')">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-light-danger">
                                        <i class="ki-duotone ki-trash fs-5"></i>
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