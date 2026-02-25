@extends('layouts.app')

@section('title', 'Crear Usuario')

@section('content')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div class="mb-5">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" />
                </div>

                <div class="mb-5">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" />
                </div>

                <div class="mb-5">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" />
                </div>

                <div class="mb-5">
                    <label class="form-label">Rol</label>
                    <select name="role" class="form-select">
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>

@endsection