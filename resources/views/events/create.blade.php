@extends('layouts.app')

@section('title', 'Crear Evento')
@section('content')
    <div class="d-flex flex-column flex-column-fluid">

        <!-- Encabezado -->
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-gray-800">
                    <i class="ki-outline ki-plus fs-2 me-2 text-primary"></i>
                    Crear Evento
                </h1>
                <span class="text-muted fs-7">Registra un nuevo evento en el sistema</span>
            </div>

            <a href="{{ route('events.index') }}" class="btn btn-primary">
                <i class="ki-outline ki-arrow-left fs-2 me-2"></i> Regresar
            </a>
        </div>

        <div class="card-body pt-0">

            {{-- Errores --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('events.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- ===============================
                                         INFORMACIÓN GENERAL
                                    ================================ -->
                <div class="card shadow-sm mb-5">
                    <div class="card-header">
                        <h4 class="card-title fw-bold">Información del Evento</h4>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ubicación (texto libre)</label>
                                <input type="text" name="location" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Descripción</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Fecha</label>
                                <input type="date" name="event_date" class="form-control" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Hora inicio</label>
                                <input type="time" name="hora_inicio" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Hora fin</label>
                                <input type="time" name="hora_fin" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Total de asientos</label>
                                <input type="number" name="total_asientos" min="1" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Mapa de asientos</label>
                                <select name="has_seat_mapping" id="has_seat_mapping" class="form-select">
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ===============================
                             TIPO DE EVENTO
                        ================================ -->
                <div class="card shadow-sm mb-5">
                    <div class="card-header">
                        <h4 class="card-title fw-bold">Tipo de evento</h4>
                    </div>

                    <div class="card-body">
                        <div class="row g-4 align-items-end">

                            <div class="col-md-4">
                                <label class="form-label fw-bold">¿Es inscripción?</label>
                                <select name="is_registration" id="is_registration" class="form-select">
                                    <option value="0" selected>No (boletos / asientos)</option>
                                    <option value="1">Sí, es inscripción</option>
                                </select>
                            </div>

                            <div class="col-md-4 d-none" id="price_wrapper">
                                <label class="form-label fw-bold">Precio de inscripción</label>
                                <input type="number" step="0.01" min="0" name="price" class="form-control"
                                    placeholder="Ej. 8250.00">
                            </div>

                            <div class="col-md-4 d-none" id="max_capacity_wrapper">
                                <label class="form-label fw-bold">Cupo máximo</label>
                                <input type="number" min="1" name="max_capacity" class="form-control" placeholder="Ej. 150">
                            </div>

                            <div class="col-md-4 d-none" id="template_wrapper">
                                <label class="form-label fw-bold">Plantilla</label>
                                <select name="template" class="form-select">
                                    <option value="registration">Registro / Inscripción</option>
                                    <option value="default">Default</option>
                                </select>
                            </div>

                        </div>

                    </div>
                </div>


                <!-- ===============================
                                         CATÁLOGO NABOO
                                    ================================ -->
                <div class="card shadow-sm mb-5">
                    <div class="card-header">
                        <h4 class="card-title fw-bold">Ubicación (Catálogo de boletos)</h4>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Proyecto</label>
                                <select name="project_id" id="project_id" class="form-select">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Fase</label>
                                <select name="phase_id" id="phase_id" class="form-select" disabled>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Etapa</label>
                                <select name="stage_id" id="stage_id" class="form-select" disabled>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ===============================
                                         IMÁGENES
                                    ================================ -->
                <div class="card shadow-sm mb-5">
                    <div class="card-header">
                        <h4 class="card-title fw-bold">Imágenes (para el mapeo de boletos con los asientos)</h4>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Imagen PNG</label>
                                <input type="file" name="png_image" accept="image/png" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Imagen SVG</label>
                                <input type="file" name="svg_image" accept=".svg" class="form-control">
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ===============================
                                         COLORES Y SELECTORES
                                    ================================ -->
                <div class="card shadow-sm mb-5">
                    <div class="card-header">
                        <h4 class="card-title fw-bold">Tema visual</h4>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">

                            <div class="col-md-2">
                                <label class="form-label">Color modal</label>
                                <input type="text" name="modal_color" class="form-control color-picker">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Color primario</label>
                                <input type="text" name="color_primario" class="form-control color-picker">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Color acento</label>
                                <input type="text" name="color_acento" class="form-control color-picker">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Selector SVG / Modal</label>
                                <input type="text" name="modal_selector" class="form-control" placeholder="svg *">
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ===============================
                                         REDIRECCIONES
                                    ================================ -->
                <div class="card shadow-sm mb-5">
                    <div class="card-header">
                        <h4 class="card-title fw-bold">Redirecciones</h4>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">

                            <div class="col-md-4">
                                <label class="form-label">URL regresar</label>
                                <input type="text" name="redirect_return" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">URL siguiente</label>
                                <input type="text" name="redirect_next" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">URL anterior</label>
                                <input type="text" name="redirect_previous" class="form-control">
                            </div>

                        </div>
                    </div>
                </div>

                <div class="text-end mt-5">
                    <button type="submit" class="btn btn-primary">
                        Guardar Evento
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="/assets/js/events/events.js"></script>
    <script src="/assets/js/events/procesarSVG.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pickers = document.querySelectorAll('.color-picker');

            pickers.forEach(input => {
                const pickrContainer = document.createElement('div');
                input.parentNode.insertBefore(pickrContainer, input.nextSibling);

                const pickr = Pickr.create({
                    el: pickrContainer,
                    theme: 'classic',
                    default: input.value || '#30362D',
                    components: {
                        preview: true,
                        opacity: false,
                        hue: true,
                        interaction: {
                            hex: true,
                            input: true,
                            save: true
                        }
                    }
                });

                pickr.on('change', (color) => {
                    const hex = color.toHEXA().slice(0, 3).map(c => c.toString(16).padStart(2, '0')).join('');
                    input.value = `#${hex}`;
                    input.style.backgroundColor = input.value;
                });

                pickr.on('save', (color) => {
                    if (!color) return;
                    const hex = color.toHEXA().slice(0, 3).map(c => c.toString(16).padStart(2, '0')).join('');
                    input.value = `#${hex}`;
                    input.style.backgroundColor = input.value;
                    pickr.hide();
                });

                if (input.value) {
                    input.style.backgroundColor = input.value;
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const isRegistration = document.getElementById('is_registration');
            const priceWrapper = document.getElementById('price_wrapper');
            const templateWrapper = document.getElementById('template_wrapper');
            const maxCapacityWrapper = document.getElementById('max_capacity_wrapper');

            const totalAsientos = document.querySelector('[name="total_asientos"]');
            const hasSeatMapping = document.getElementById('has_seat_mapping');

            function toggleRegistrationMode() {
                const isReg = isRegistration.value === '1';

                // Mostrar / ocultar campos de inscripción
                priceWrapper.classList.toggle('d-none', !isReg);
                templateWrapper.classList.toggle('d-none', !isReg);
                maxCapacityWrapper.classList.toggle('d-none', !isReg);

                if (isReg) {
                    // Inscripción → sin asientos ni mapa
                    totalAsientos.value = 0;
                    totalAsientos.removeAttribute('required');
                    totalAsientos.setAttribute('readonly', true);

                    hasSeatMapping.value = 0;
                    hasSeatMapping.setAttribute('disabled', true);
                } else {
                    // Evento normal
                    totalAsientos.removeAttribute('readonly');
                    totalAsientos.setAttribute('required', true);

                    hasSeatMapping.removeAttribute('disabled');
                }
            }

            isRegistration.addEventListener('change', toggleRegistrationMode);
            toggleRegistrationMode(); // init
        });
    </script>
@endpush