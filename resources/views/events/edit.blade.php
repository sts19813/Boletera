@extends('layouts.app')

@section('title', 'Editar Evento')

@section('content')
<div class="d-flex flex-column flex-column-fluid">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-bold text-gray-800">
                <i class="ki-outline ki-pencil fs-2 me-2 text-primary"></i>
                Editar Evento
            </h1>
            <span class="text-muted fs-7">Actualiza la información del evento</span>
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

        <form action="{{ route('events.update', $event->id) }}"
              method="POST"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

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
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   value="{{ old('name', $event->name) }}"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ubicación (texto libre)</label>
                            <input type="text"
                                   name="location"
                                   class="form-control"
                                   value="{{ old('location', $event->location) }}">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Descripción</label>
                            <textarea name="description"
                                      class="form-control"
                                      rows="3">{{ old('description', $event->description) }}</textarea>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Fecha</label>
                            <input type="date"
                                   name="event_date"
                                   class="form-control"
                                   value="{{ old('event_date', optional($event->event_date)->format('Y-m-d')) }}"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Hora inicio</label>
                            <input type="time"
                                   name="hora_inicio"
                                   class="form-control"
                                   value="{{ old('hora_inicio', $event->hora_inicio) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Hora fin</label>
                            <input type="time"
                                   name="hora_fin"
                                   class="form-control"
                                   value="{{ old('hora_fin', $event->hora_fin) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Total de asientos</label>
                            <input type="number"
                                   name="total_asientos"
                                   min="1"
                                   class="form-control"
                                   value="{{ old('total_asientos', $event->total_asientos) }}"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Mapa de asientos</label>
                            <select name="has_seat_mapping" class="form-select">
                                <option value="0"
                                    {{ old('has_seat_mapping', $event->has_seat_mapping) == false ? 'selected' : '' }}>
                                    No
                                </option>
                                <option value="1"
                                    {{ old('has_seat_mapping', $event->has_seat_mapping) == true ? 'selected' : '' }}>
                                    Sí
                                </option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ===============================
                 UBICACIÓN NABOO
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
                                <option value="">Seleccione un proyecto</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fase</label>
                            <select name="phase_id" id="phase_id" class="form-select" disabled>
                                <option value="">Seleccione una fase</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Etapa</label>
                            <select name="stage_id" id="stage_id" class="form-select" disabled>
                                <option value="">Seleccione una etapa</option>
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
                    <h4 class="card-title fw-bold">Imágenes</h4>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Imagen PNG</label>
                            <input type="file"
                                   name="png_image"
                                   accept="image/png"
                                   class="form-control">
                            @if ($event->png_image)
                                <small class="text-muted d-block mt-1">
                                    Actual: {{ basename($event->png_image) }}
                                </small>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Imagen SVG</label>
                            <input type="file"
                                   name="svg_image"
                                   accept=".svg"
                                   class="form-control">
                            @if ($event->svg_image)
                                <small class="text-muted d-block mt-1">
                                    Actual: {{ basename($event->svg_image) }}
                                </small>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

            <!-- ===============================
                 TEMA VISUAL
            ================================ -->
            <div class="card shadow-sm mb-5">
                <div class="card-header">
                    <h4 class="card-title fw-bold">Colores y Selectores</h4>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-2">
                            <label class="form-label">Color modal</label>
                            <input type="text"
                                   name="modal_color"
                                   class="form-control color-picker"
                                   value="{{ old('modal_color', $event->modal_color) }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Color primario</label>
                            <input type="text"
                                   name="color_primario"
                                   class="form-control color-picker"
                                   value="{{ old('color_primario', $event->color_primario) }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Color acento</label>
                            <input type="text"
                                   name="color_acento"
                                   class="form-control color-picker"
                                   value="{{ old('color_acento', $event->color_acento) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Selector SVG / Modal</label>
                            <input type="text"
                                   name="modal_selector"
                                   class="form-control"
                                   value="{{ old('modal_selector', $event->modal_selector) }}">
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
                            <input type="text"
                                   name="redirect_return"
                                   class="form-control"
                                   value="{{ old('redirect_return', $event->redirect_return) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">URL siguiente</label>
                            <input type="text"
                                   name="redirect_next"
                                   class="form-control"
                                   value="{{ old('redirect_next', $event->redirect_next) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">URL anterior</label>
                            <input type="text"
                                   name="redirect_previous"
                                   class="form-control"
                                   value="{{ old('redirect_previous', $event->redirect_previous) }}">
                        </div>

                    </div>
                </div>
            </div>

            <div class="text-end mt-5">
                <button type="submit" class="btn btn-primary">
                    Actualizar Evento
                </button>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')

{{-- Precarga para JS NABOO --}}
<script>
    window.selectedProject = "{{ $event->project_id }}";
    window.selectedPhase   = "{{ $event->phase_id }}";
    window.selectedStage   = "{{ $event->stage_id }}";
</script>

<script src="/assets/js/events/events.js"></script>
<script src="/assets/js/events/procesarSVG.js"></script>

{{-- Pickr --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.color-picker').forEach(input => {

        const container = document.createElement('div');
        input.parentNode.insertBefore(container, input.nextSibling);

        const pickr = Pickr.create({
            el: container,
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

        pickr.on('save', color => {
            if (!color) return;
            const hex = color.toHEXA().slice(0, 3)
                .map(c => c.toString(16).padStart(2, '0')).join('');
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
