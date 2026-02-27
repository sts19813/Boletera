@extends('layouts.app')

@section('title', 'Editar Rol')

@section('content')

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title">
                <h2>Editar Rol</h2>
            </div>
        </div>

        <div class="card-body pt-0">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')

                <div class="mb-10">
                    <label class="form-label required">Nombre del Rol</label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}"
                        class="form-control form-control-solid">
                </div>

                <div class="mb-10">
                    <label class="form-label">Permisos</label>

                    <div class="row">
                        @foreach($permissions as $permission)
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="permissions[]"
                                        value="{{ $permission->name }}" {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>

                                    <label class="form-check-label">
                                        {{ $permission->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('roles.index') }}" class="btn btn-light me-3">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Actualizar Rol
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection