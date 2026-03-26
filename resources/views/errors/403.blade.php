@extends('layouts.error')

@section('title', '403')

@section('content')
    <div class="error-box">

        <div class="error-image">
            <img src="{{ asset('assets/img/error-404.png') }}" alt="403">
        </div>

        <div class="error-text">
            <h1>Acceso no permitido</h1>

            <p>
                No tienes permiso para acceder a esta sección.
                @auth
                    Si ya iniciaste sesión, usa los accesos de abajo para entrar a los módulos disponibles para tu perfil.
                @else
                    Inicia sesión para ver los módulos a los que tienes acceso.
                @endauth
            </p>

            @include('errors.partials.module-links')
        </div>

    </div>
@endsection
