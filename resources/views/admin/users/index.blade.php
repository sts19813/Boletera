@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Usuarios</h3>

        <a href="{{ route('users.create') }}" class="btn btn-primary">
            Nuevo Usuario
        </a>
    </div>

    <div class="card-body">
        <table class="table align-middle table-row-dashed">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Eventos</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge badge-light-primary">
                                {{ $user->getRoleNames()->first() }}
                            </span>
                        </td>
                        <td>
                            {{ $user->events->count() }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('users.edit', $user) }}"
                               class="btn btn-sm btn-light-primary me-2">
                                Editar
                            </a>

                            <form action="{{ route('users.destroy', $user) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?')">
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

        {{ $users->links() }}
    </div>
</div>

@endsection