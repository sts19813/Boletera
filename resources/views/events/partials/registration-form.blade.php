<div class="card shadow-sm mb-8">
    <div class="card-header">
        <h3 class="card-title fw-bold">
            Información del equipo
        </h3>
    </div>

    <div class="card-body">
        <div class="mb-0">
            <label class="form-label required">Nombre del equipo</label>
            <input type="text" class="form-control form-control-solid" name="team_name"
                placeholder="Escribe el nombre del equipo" required>
        </div>
    </div>
</div>


@for($i = 0; $i < 3; $i++)
    <div class="card shadow-sm mb-8">

        <div class="card-header bg-light">
            <h4 class="card-title fw-bold mb-0">
                Jugador {{ $i + 1 }}
                @if($i === 0)
                    <span class="text-muted fw-normal">· Capitán</span>
                @endif
            </h4>
        </div>

        <div class="card-body">

            {{-- Nombre --}}
            <div class="mb-5">
                <label class="form-label required">Nombre completo</label>
                <input type="text" class="form-control form-control-solid" name="players[{{ $i }}][name]" required>
            </div>

            {{-- Relación Cumbres --}}
            <div class="mb-5 cumbres-group" data-player="{{ $i }}">
                <label class="form-label required">Relación con Cumbres</label>

                <div class="d-flex gap-6">
                    <label class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input cumbres-checkbox" type="checkbox" name="players[{{ $i }}][cumbres][]"
                            value="egresado">
                        <span class="form-check-label">Egresado</span>
                    </label>

                    <label class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input cumbres-checkbox" type="checkbox" name="players[{{ $i }}][cumbres][]"
                            value="padre">
                        <span class="form-check-label">Padre de familia</span>
                    </label>
                    <label class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input cumbres-checkbox" type="checkbox" name="players[{{ $i }}][cumbres][]"
                            value="invitado">
                        <span class="form-check-label">Invitado</span>
                    </label>    
                </div>

                <div class="text-danger small mt-2 d-none cumbres-error">
                    Debes seleccionar al menos una opción.
                </div>
            </div>


            {{-- Contacto --}}
            <div class="row g-5 mb-6">
                <div class="col-md-6">
                    <label class="form-label required">Celular</label>
                    <input type="tel" class="form-control form-control-solid" name="players[{{ $i }}][phone]"
                        inputmode="numeric" pattern="[0-9]{10}" maxlength="10" placeholder="10 dígitos" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Correo electrónico</label>
                    <input type="email" class="form-control form-control-solid" name="players[{{ $i }}][email]"
                        placeholder="correo@ejemplo.com" required>
                </div>
            </div>

            {{-- Datos de Golf --}}
            <div class="row g-5 mb-6">
                <div class="col-md-4">
                    <label class="form-label required">Campo donde juega</label>
                    <input type="text" class="form-control form-control-solid" name="players[{{ $i }}][campo]" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label required">Hándicap al registrarse</label>
                    <input type="text" class="form-control form-control-solid" name="players[{{ $i }}][handicap]"
                        placeholder="Ej. 12.4" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label required">Número GHIN</label>
                    <input type="text" class="form-control form-control-solid" name="players[{{ $i }}][ghin]" placeholder=""
                        required>
                </div>
            </div>

            {{-- Talla --}}
            <div class="mb-0">
                <label class="form-label required">Talla de camisa</label>
                <input type="text" class="form-control form-control-solid" name="players[{{ $i }}][shirt]"
                    placeholder="Ej. M, G, XG" required>
            </div>

        </div>
    </div>
@endfor

<script>
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '');
        });
    });

    document.querySelector('form').addEventListener('submit', function (e) {
        let valid = true;

        document.querySelectorAll('.cumbres-group').forEach(group => {
            const checkboxes = group.querySelectorAll('.cumbres-checkbox');
            const error = group.querySelector('.cumbres-error');

            const checked = Array.from(checkboxes).some(cb => cb.checked);

            if (!checked) {
                valid = false;
                error.classList.remove('d-none');
            } else {
                error.classList.add('d-none');
            }
        });

        if (!valid) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
</script>