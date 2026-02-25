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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection