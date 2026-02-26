@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="mb-5">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" value="{{ $user->name }}" class="form-control" />
                </div>

                <div class="mb-5">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ $user->email }}" class="form-control" />
                </div>

                <div class="mb-5">
                    <label class="form-label">Rol</label>

                    <select name="role" class="form-select">
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-primary">Actualizar</button>
            </form>
        </div>
    </div>

@endsection