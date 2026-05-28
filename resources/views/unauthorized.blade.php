<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>No Autorizado</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body id="kt_body" class="app-blank">

    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-column flex-center flex-column-fluid p-10">
            <div class="card card-flush w-lg-650px py-5">
                <div class="card-body text-center">
                    <!-- Icono -->
                    <div class="mb-3">
                        <i class="ki-outline ki-shield-cross fs-5x text-danger"></i>
                    </div>

                    <!-- Título -->
                    <h1 class="fw-bolder text-gray-900 mb-3">Acceso no autorizado</h1>

                    <!-- Mensaje -->
                    <div class="fw-semibold fs-6 text-gray-500 mb-7">
                        No tienes permisos para acceder a esta página.
                    </div>

                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="{{ url('/') }}" class="btn btn-primary">
                            Ir al inicio
                        </a>

                        @auth
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-light-danger">
                                    Cerrar sesión
                                </button>
                            </form>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
</body>
</html>
