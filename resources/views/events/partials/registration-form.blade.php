<div class="card shadow-sm mb-8">
    <div class="card-header">
        <h3 class="card-title fw-bold">
            Formato de Inscripción
        </h3>
    </div>

    <div class="card-body">

        {{-- ===================== --}}
        {{-- DATOS DEL EQUIPO --}}
        {{-- ===================== --}}
        <div class="mb-8">
            <label class="form-label required">Nombre del equipo</label>
            <input type="text"
                   class="form-control form-control-solid"
                   name="team_name"
                   placeholder="Escribe el nombre del equipo"
                   required>
        </div>

        {{-- ===================== --}}
        {{-- JUGADORES --}}
        {{-- ===================== --}}
        @for($i = 0; $i < 3; $i++)
            <div class="card shadow-sm mb-7">
                <div class="card-header">
                    <h4 class="card-title fw-bold">
                        Jugador {{ $i + 1 }}
                        @if($i === 0)
                            · Capitán
                        @endif
                    </h4>
                </div>

                <div class="card-body">

                    {{-- Nombre --}}
                    <div class="mb-5">
                        <label class="form-label required">Nombre completo</label>
                        <input type="text"
                               class="form-control form-control-solid"
                               name="players[{{ $i }}][name]"
                               required>
                    </div>

                    {{-- Relación Cumbres --}}
                    <div class="mb-5">
                        <label class="form-label">Relación con Cumbres</label>
                        <div class="d-flex gap-6">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="players[{{ $i }}][cumbres][]"
                                       value="egresado">
                                <span class="form-check-label">Egresado</span>
                            </label>

                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="players[{{ $i }}][cumbres][]"
                                       value="padre">
                                <span class="form-check-label">Padre de familia</span>
                            </label>
                        </div>
                    </div>

                    {{-- Contacto --}}
                    <div class="row g-5 mb-6">
                        <div class="col-md-6">
                            <label class="form-label {{ $i === 0 ? 'required' : '' }}">Celular</label>
                            <input type="tel"
                                   class="form-control form-control-solid"
                                   name="players[{{ $i }}][phone]"
                                   {{ $i === 0 ? 'required' : '' }}>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label {{ $i === 0 ? 'required' : '' }}">Correo electrónico</label>
                            <input type="email"
                                   class="form-control form-control-solid"
                                   name="players[{{ $i }}][email]"
                                   {{ $i === 0 ? 'required' : '' }}>
                        </div>
                    </div>

                    {{-- Datos de Golf --}}
                    <div class="row g-5 mb-6">
                        <div class="col-md-4">
                            <label class="form-label">Campo donde juega</label>
                            <input type="text"
                                   class="form-control form-control-solid"
                                   name="players[{{ $i }}][campo]">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Hándicap al registrarse</label>
                            <input type="text"
                                   class="form-control form-control-solid"
                                   name="players[{{ $i }}][handicap]"
                                   placeholder="Ej. 12.4">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Número GHIN</label>
                            <input type="text"
                                   class="form-control form-control-solid"
                                   name="players[{{ $i }}][ghin]"
                                   placeholder="Opcional">
                        </div>
                    </div>

                    {{-- Talla --}}
                    <div class="mb-0">
                        <label class="form-label">Talla de camisa</label>
                        <input type="text"
                               class="form-control form-control-solid"
                               name="players[{{ $i }}][shirt]"
                               placeholder="Ej. M, G, XG">
                    </div>

                </div>
            </div>
        @endfor

    </div>
</div>
