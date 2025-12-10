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

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('events.index') }}" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="ki-outline ki-arrow-left fs-2 me-2"></i> Regresar al listado
            </a>
        </div>
    </div>

    <div class="card-body pt-0">

        {{-- Mostrar errores --}}
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

            <!-- Datos principales -->
            <div class="card shadow-sm mb-5">
                <div class="card-header">
                    <h4 class="card-title fw-bold">Información del Evento</h4>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <!-- Nombre -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre del evento</label>
                            <input type="text" name="name" class="form-control" required />
                        </div>

                        <!-- Descripción -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Descripción</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>

                        <!-- Total Asientos -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total de asientos</label>
                            <input type="number" name="total_asientos" class="form-control" min="1" required />
                        </div>

                        <!-- Fecha -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha del evento</label>
                            <input type="date" name="event_date" class="form-control" required />
                        </div>

                        <!-- Estatus -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Estatus</label>
                            <select name="status" class="form-select form-select-solid" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Catálogo NABOO -->
            <div class="card shadow-sm mb-5">
                <div class="card-header">
                    <h4 class="card-title fw-bold">Ubicación (Catálogo NABOO)</h4>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <!-- Fuente -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fuente de datos</label>
                            <select name="source_type" id="source_type" class="form-select form-select-solid" required>
                                <option value="adara">Adara</option>
                                <option value="naboo" selected>Naboo</option>
                            </select>
                        </div>

                        <!-- Proyecto -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Proyecto</label>
                            <select name="project_id" id="project_id"
                                class="form-select form-select-solid">
                                <option value="">Seleccione un proyecto</option>
                            </select>
                        </div>

                        <!-- Fase -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fase</label>
                            <select name="phase_id" id="phase_id" class="form-select form-select-solid" disabled>
                                <option value="">Seleccione una fase</option>
                            </select>
                        </div>

                        <!-- Etapa -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Etapa</label>
                            <select name="stage_id" id="stage_id" class="form-select form-select-solid" disabled>
                                <option value="">Seleccione una etapa</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Imágenes -->
            <div class="card shadow-sm mb-5">
                <div class="card-header">
                    <h4 class="card-title fw-bold">Imágenes del Evento</h4>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Imagen PNG</label>
                            <input type="file" name="png_image" accept="image/png" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Imagen SVG</label>
                            <input type="file" id="svg_input" name="svg_image" accept=".svg" class="form-control">
                        </div>

                    </div>
                </div>
            </div>

            <!-- Colores -->
            <div class="card mt-5 shadow-sm">
                <div class="card-header">
                    <h4 class="card-title fw-bold">Colores del Tema</h4>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-2">
                            <label class="form-label">Color del modal</label>
                            <input type="text" name="modal_color" id="modal_color"
                                class="form-control form-control-solid color-picker" placeholder="rgba(0,0,0,0.5)">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Color primario</label>
                            <input type="text" name="color_primario" id="color_primario"
                                class="form-control form-control-solid color-picker" placeholder="#0044CC">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Color acento</label>
                            <input type="text" name="color_acento" id="color_acento"
                                class="form-control form-control-solid color-picker" placeholder="#FFAA00">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Selector del modal</label>
                            <input type="text" name="modal_selector" id="modal_selector" class="form-control"
                                placeholder="svg *">
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
@endpush
