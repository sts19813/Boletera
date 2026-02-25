@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Usuarios</h3>
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->getRoleNames()->first() }}</td>
                            <td>
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-light-primary">Editar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $users->links() }}
        </div>
    </div>

@endsection