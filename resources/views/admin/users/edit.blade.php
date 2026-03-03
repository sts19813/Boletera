@extends('layouts.app')

@section('title', isset($user) ? 'Editar Usuario' : 'Crear Usuario')

@section('content')

<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title">
            {{ isset($user) ? 'Editar Usuario' : 'Nuevo Usuario' }}
        </h3>
    </div>

    <div class="card-body">

        <form method="POST"
              action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if(isset($user))
                @method('PUT')
            @endif

            {{-- ================= DATOS BÁSICOS ================= --}}
            <div class="mb-7">
                <h5 class="fw-bold mb-4 text-primary">Información básica</h5>

                <div class="row">
                    <div class="col-md-6 mb-5">
                        <label class="form-label">Nombre</label>
                        <input type="text"
                               name="name"
                               value="{{ old('name', $user->name ?? '') }}"
                               class="form-control" />
                    </div>

                    <div class="col-md-6 mb-5">
                        <label class="form-label">Email</label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', $user->email ?? '') }}"
                               class="form-control" />
                    </div>
                </div>

                @if(!isset($user))
                    <div class="col-md-6 mb-5">
                        <label class="form-label">Contraseña</label>
                        <input type="password"
                               name="password"
                               class="form-control" />
                    </div>
                @endif
            </div>

            {{-- ================= ROL ================= --}}
            <div class="mb-7">
                <h5 class="fw-bold mb-4 text-primary">Rol del sistema</h5>

                <select name="role" class="form-select w-50">
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}"
                            {{ isset($user) && $user->hasRole($role->name) ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- ================= PERMISOS DIRECTOS ================= --}}
            <div class="mb-7">
                <h5 class="fw-bold mb-4 text-primary">
                    Permisos adicionales
                </h5>

                <div class="row">
                    @foreach($permissions as $permission)
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->name }}"
                                    {{ isset($user) && $user->hasDirectPermission($permission->name) ? 'checked' : '' }}>

                                <label class="form-check-label">
                                    {{ $permission->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <small class="text-muted">
                    Estos permisos se aplican adicionalmente al rol seleccionado.
                </small>
            </div>

            {{-- ================= EVENTOS ================= --}}
            <div class="mb-7">
                <h5 class="fw-bold mb-4 text-primary">Acceso a eventos</h5>

                <div class="row">
                    @foreach($events as $event)
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="events[]"
                                       value="{{ $event->id }}"
                                       {{ isset($user) && $user->events->contains($event->id) ? 'checked' : '' }}>

                                <label class="form-check-label">
                                    {{ $event->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <small class="text-muted">
                    Define a qué eventos podrá acceder este usuario.
                </small>
            </div>

            <div class="text-end">
                <button class="btn btn-primary px-6">
                    {{ isset($user) ? 'Actualizar Usuario' : 'Crear Usuario' }}
                </button>
            </div>

        </form>
    </div>
</div>

@endsection