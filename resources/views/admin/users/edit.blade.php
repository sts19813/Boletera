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

            <div class="mb-5">
                <label class="form-label">Eventos disponibles</label>

                @foreach($events as $event)
                    <div class="form-check">
                        <input class="form-check-input"
                               type="checkbox"
                               name="events[]"
                               value="{{ $event->id }}"
                               {{ $user->events->contains($event->id) ? 'checked' : '' }}>

                        <label class="form-check-label">
                            {{ $event->name }}
                        </label>
                    </div>
                @endforeach
            </div>

            <button class="btn btn-primary">Actualizar</button>
        </form>
    </div>
</div>

@endsection