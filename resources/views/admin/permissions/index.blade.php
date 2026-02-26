@extends('layouts.app')

@section('title', 'Permisos')

@section('content')

    <div class="card card-flush">

        <div class="card-header">
            <h3 class="card-title">Crear Permiso</h3>
        </div>

        <div class="card-body">

            <form method="POST" action="{{ route('permissions.store') }}">
                @csrf

                <div class="mb-5">
                    <label class="form-label">Nombre del permiso</label>
                    <input type="text" name="name" class="form-control">
                </div>

                <button class="btn btn-primary">Guardar</button>
            </form>

        </div>
    </div>

    <div class="card card-flush mt-5">
        <div class="card-header">
            <h3 class="card-title">Permisos existentes</h3>
        </div>

        <div class="card-body">
            <table class="table table-row-dashed">
                <thead>
                    <tr>
                        <th>Permiso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $permission)
                        <tr>
                            <td>{{ $permission->name }}</td>
                            <td>
                                <form method="POST" action="{{ route('permissions.destroy', $permission) }}">
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