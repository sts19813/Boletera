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
            <span class="text-muted fs-7">
                Actualiza la información del evento
            </span>
        </div>

        <a href="{{ route('events.index') }}" class="btn btn-primary">
            <i class="ki-outline ki-arrow-left fs-2 me-2"></i>
            Regresar al listado
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

            <!-- =========================
                Información del evento
            ========================== -->
            <div class="card shadow-sm mb-5">
                <div class="card-header">
                    <h4 class="card-title fw-bold">Información del Evento</h4>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre del evento</label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   value="{{ old('name', $event->name) }}"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Descripción</label>
                            <textarea name="description"
                                      class="form-control"
                                      rows="2">{{ old('description', $event->description) }}</textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total de asientos</label>
                            <input type="number"
                                   name="total_asientos"
                                   class="form-control"
                                   min="1"
                                   value="{{ old('total_asientos', $event->total_asientos) }}"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha del evento</label>
                            <input type="date"
                                   name="event_date"
                                   class="form-control"
                                   value="{{ old('event_date', $event->event_date) }}"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Estatus</label>
                            <select name="status" class="form-select form-select-solid">
                                <option value="activo"
                                    {{ old('status', $event->status) == 'activo' ? 'selected' : '' }}>
                                    Activo
                                </option>
                                <option value="inactivo"
                                    {{ old('status', $event->status) == 'inactivo' ? 'selected' : '' }}>
                                    Inactivo
                                </option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <!-- =========================
                Ubicación NABOO
            ========================== -->
            <div class="card shadow-sm mb-5">
                <div class="card-header">
                    <h4 class="card-title fw-bold">Ubicación (Catálogo NABOO)</h4>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fuente</label>
                            <select name="source_type" id="source_type"
                                    class="form-select form-select-solid">
                                <option value="adara"
                                    {{ old('source_type', $event->source_type) == 'adara' ? 'selected' : '' }}>
                                    Adara
                                </option>
                                <option value="naboo"
                                    {{ old('source_type', $event->source_type) == 'naboo' ? 'selected' : '' }}>
                                    Naboo
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Proyecto</label>
                            <select name="project_id"
                                    id="project_id"
                                    class="form-select form-select-solid"
                                    data-selected="{{ $event->project_id }}">
                                <option value="">Seleccione un proyecto</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fase</label>
                            <select name="phase_id"
                                    id="phase_id"
                                    class="form-select form-select-solid"
                                    data-selected="{{ $event->phase_id }}">
                                <option value="">Seleccione una fase</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Etapa</label>
                            <select name="stage_id"
                                    id="stage_id"
                                    class="form-select form-select-solid"
                                    data-selected="{{ $event->stage_id }}">
                                <option value="">Seleccione una etapa</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <!-- =========================
                Imágenes
            ========================== -->
            <div class="card shadow-sm mb-5">
                <div class="card-header">
                    <h4 class="card-title fw-bold">Imágenes del Evento</h4>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-6">
                            <label class="form-label fw-bold">PNG (opcional)</label>
                            <input type="file"
                                   name="png_image"
                                   accept="image/png"
                                   class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">SVG (opcional)</label>
                            <input type="file"
                                   name="svg_image"
                                   accept=".svg"
                                   class="form-control">
                        </div>

                    </div>
                </div>
            </div>

            <!-- =========================
                Colores
            ========================== -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="card-title fw-bold">Colores del Tema</h4>
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
                            <label class="form-label">Selector modal</label>
                            <input type="text"
                                   name="modal_selector"
                                   class="form-control"
                                   value="{{ old('modal_selector', $event->modal_selector) }}">
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
<script src="/assets/js/events/events.js"></script>
<script src="/assets/js/events/procesarSVG.js"></script>

{{-- Pickr (igual que create) --}}
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
