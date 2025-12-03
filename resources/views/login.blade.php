{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión - Boletera</title>

    {{-- Fuentes y estilos --}}
    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body id="kt_body" class="app-blank">

    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">

            <!-- Columna izquierda: formulario -->
            <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
                <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                    <div class="w-lg-500px p-10">

                        {{-- Formulario de login --}}
                        <form class="form w-100" method="POST" action="{{ route('login') }}">
                            @csrf

                            <!-- Título -->
                            <div class="text-center mb-11">
                                <h1 class="text-gray-900 fw-bolder mb-3">Iniciar Sesión</h1>
                                <div class="text-gray-500 fw-semibold fs-6">Accede con tu cuenta</div>
                            </div>

                            <!-- Botones sociales -->
                            <div class="row g-3 mb-9">
                                <div class="col-md-12">
                                    <a href="{{ url('/google-auth/redirect') }}" 
                                       class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center w-100">
                                        <img alt="Google" src="{{ asset('assets/media/svg/brand-logos/google-icon.svg') }}" class="h-15px me-3" />
                                        Iniciar con Google
                                    </a>
                                </div>
                            </div>

                            <!-- Separador -->
                            <div class="separator separator-content my-14">
                                <span class="w-125px text-gray-500 fw-semibold fs-7">O con tu correo</span>
                            </div>

                            <!-- Email -->
                            <div class="fv-row mb-8">
                                <input type="email" name="email" value="{{ old('email') }}"
                                       class="form-control bg-transparent" placeholder="Correo electrónico" required autofocus />
                                @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="fv-row mb-3">
                                <input type="password" name="password"
                                       class="form-control bg-transparent" placeholder="Contraseña" required />
                                @error('password')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Opciones -->
                            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                <div class="form-check">
                                    <input type="checkbox" name="remember" id="remember_me" class="form-check-input" />
                                    <label for="remember_me" class="form-check-label">Recordarme</label>
                                </div>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="link-primary">¿Olvidaste tu contraseña?</a>
                                @endif
                            </div>

                            <!-- Botón submit -->
                            <div class="d-grid mb-10">
                                <button type="submit" class="btn btn-primary">
                                    <span class="indicator-label">Ingresar</span>
                                    <span class="indicator-progress">Por favor espera...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>

                            <!-- Registro -->
                           
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: imagen -->
            <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2" 
                 style="background-image: url('{{ asset('assets/media/misc/auth-bg.png') }}')">
                <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                    <a href="{{ url('/') }}" class="mb-0 mb-lg-12">
                        <!--img alt="Logo" src="{{ asset('assets/media/logos/custom-1.png') }}" class="h-60px h-lg-75px" /-->
                    </a>
                    <!--img class="d-none d-lg-block mx-auto w-275px w-md-50 w-xl-500px mb-10 mb-lg-20"
                         src="{{ asset('assets/media/misc/auth-screens.png') }}" alt="" /-->
                    <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">
                        Bienvenido 
                    </h1>
                    <div class="d-none d-lg-block text-white fs-base text-center">
                        Gestiona y accede fácilmente a tu cuenta.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
</body>
</html>
