@extends('layouts.app')

@section('title', 'Editar Desarrollo')

@section('content')
    <div class="d-flex flex-column flex-column-fluid">

        <!-- Encabezado -->
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-gray-800">
                    <i class="ki-outline ki-pencil fs-2 me-2 text-primary"></i>
                    {{ __('messages.edit_development_title') }}
                </h1>
                <span class="text-muted fs-7">{{ __('messages.edit_development_subtitle') }}</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.index') }}" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="ki-outline ki-arrow-left fs-2 me-2"></i> {{ __('messages.back_to_list') }}
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

            <!-- FORMULARIO -->
            <form action="{{ route('desarrollos.update', $lot->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- CARD: FORMULARIO DE DESARROLLO -->
                <div class="card shadow-sm mb-5">
                    <div class="card-header">
                        <h4 class="card-title fw-bold">{{ __('messages.development_form_title') }}</h4>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">

                            <!-- Nombre -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('messages.name_label') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $lot->name) }}"
                                    required />
                            </div>

                            <!-- Descripción -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('messages.description_label') }}</label>
                                <textarea name="description" class="form-control"
                                    rows="2">{{ old('description', $lot->description) }}</textarea>
                            </div>

                            <!-- Fuente de datos -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('messages.source_type_label') }}</label>
                                <select name="source_type" id="source_type" class="form-select form-select-solid" required>
                                    <option value="adara" {{ $lot->source_type == 'adara' ? 'selected' : '' }}>{{ __('messages.source_adara') }}
                                    </option>
                                    <option value="naboo" {{ $lot->source_type == 'naboo' ? 'selected' : '' }}>{{ __('messages.source_naboo') }}
                                    </option>
                                </select>
                            </div>

                            <!-- Proyecto -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('messages.project_label') }}</label>
                                <select name="project_id" id="project_id" class="form-select form-select-solid"
                                    data-adara-projects='@json($projects)'>
                                    <option value="">{{ __('messages.select_project') }}</option>

                                    @foreach ($projects as $project)
                                        <option value="{{ $project['id'] }}" {{ $lot->project_id == $project['id'] ? 'selected' : '' }}>
                                            {{ $project['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Fase -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('messages.phase_label') }}</label>
                                <select name="phase_id" id="phase_id" class="form-select form-select-solid">
                                    <option value="">{{ __('messages.select_phase') }}</option>
                                </select>
                            </div>

                            <!-- Etapa -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('messages.stage_label') }}</label>
                                <select name="stage_id" id="stage_id" class="form-select form-select-solid">
                                    <option value="">{{ __('messages.select_stage') }}</option>
                                </select>
                            </div>

                            <!-- Total lotes -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('messages.total_lots_label') }}</label>
                                <input type="number" name="total_lots" class="form-control"
                                    value="{{ old('total_lots', $lot->total_lots) }}" min="1" required />
                            </div>

                            <!-- Plusvalía -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('messages.plusvalia_label') }}</label>
                                <input type="number" step="0.01" name="plusvalia" class="form-control"
                                    value="{{ old('plusvalia', $lot->plusvalia) }}">
                            </div>

                            <!-- Financiamiento -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('messages.financing_months_label') }}</label>
                                <input type="number" name="financing_months" class="form-control"
                                    value="{{ old('financing_months', $lot->financing_months) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('messages.svg_image_label') }}</label>

                                @if ($lot->svg_image)
                                    <div class="border rounded p-2 mb-2 d-flex align-items-center justify-content-center"
                                        style="max-height:200px; overflow:hidden; background:#fff;">
                                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
                                            {!! str_replace(
                                                '<svg',
                                                '<svg style="width:100%; height:auto; max-height:200px; display:block;"',
                                                file_get_contents(public_path($lot->svg_image))
                                            ) !!}
                                        </div>
                                    </div>
                                @endif

                                <input type="file" name="svg_image" id="svg_input" accept=".svg" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('messages.png_image_label') }}</label>

                                @if ($lot->png_image)
                                    <div class="border rounded p-2 mb-2 d-flex align-items-center justify-content-center"
                                        style="max-height:200px; overflow:hidden; background:#fff;">
                                        <img src="{{ asset($lot->png_image) }}"
                                            style="max-width:100%; max-height:200px; object-fit:contain; display:block;"
                                            class="rounded">
                                    </div>
                                @endif

                                <input type="file" name="png_image" accept="image/png" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD: COLORES -->
                <div class="card mt-5 shadow-sm">
                    <div class="card-header">
                        <h4 class="card-title fw-bold">{{ __('messages.development_colors_title') }}</h4>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">

                            <div class="col-md-2">
                                <label class="form-label">{{ __('messages.modal_color_label') }}</label>
                                <input type="text" name="modal_color" id="modal_color" class="form-control color-picker"
                                    value="{{ old('modal_color', $lot->modal_color) }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('messages.primary_color_label') }}</label>
                                <input type="text" name="color_primario" id="color_primario"
                                    class="form-control color-picker"
                                    value="{{ old('color_primario', $lot->color_primario) }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('messages.accent_color_label') }}</label>
                                <input type="text" name="color_acento" id="color_acento" class="form-control color-picker"
                                    value="{{ old('color_acento', $lot->color_acento) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('messages.modal_selector_label') }}</label>
                                <input type="text" name="modal_selector" id="modal_selector" class="form-control"
                                    value="{{ old('modal_selector', $lot->modal_selector) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('messages.modal_selector_Plantilla') }}</label>

                                <select name="iframe_template_modal" class="form-select" required>
                                    <option value="">Seleccione una plantilla</option>
                                    @php
                                        $templates = [
                                            'emedos' => 'Eme Dos Desarrollos',
                                            'portamarina' => 'Porta Marina',
                                        ];
                                    @endphp

                                    @foreach ($templates as $value => $label)
                                        <option value="{{ $value }}" {{ $lot->iframe_template_modal == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD: REDIRECCIONES -->
                <div class="card mt-5 shadow-sm">
                    <div class="card-header">
                        <h4 class="card-title fw-bold">{{ __('messages.redirects_title') }}</h4>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">

                            <div class="col-md-4">
                                <label class="form-label">{{ __('messages.redirect_return_label') }}</label>
                                <select name="redirect_return" class="form-select">
                                    <option value="">Seleccione una opción</option>
                                    @foreach ($desarrollos as $desarrollo)
                                        <option value="{{ $desarrollo['id'] }}" {{ $lot->redirect_return == $desarrollo['id'] ? 'selected' : '' }}>
                                            {{ $desarrollo['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('messages.redirect_next_label') }}</label>
                                <select name="redirect_next" class="form-select">
                                    <option value="">Seleccione una opción</option>
                                    @foreach ($desarrollos as $desarrollo)
                                        <option value="{{ $desarrollo['id'] }}" {{ $lot->redirect_next == $desarrollo['id'] ? 'selected' : '' }}>
                                            {{ $desarrollo['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('messages.redirect_previous_label') }}</label>
                                <select name="redirect_previous" class="form-select">
                                    <option value="">{{ __('messages.select_option') }}</option>
                                    @foreach ($desarrollos as $desarrollo)
                                        <option value="{{ $desarrollo['id'] }}" {{ $lot->redirect_previous == $desarrollo['id'] ? 'selected' : '' }}>
                                            {{ $desarrollo['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="text-end mt-5">
                    <button type="submit" class="btn btn-primary">
                        {{ __('messages.update_development') }}
                    </button>
                </div>

            </form>
                <div class="text-end mt-5">
                       <!-- BOTÓN ELIMINAR -->
                    <form action="{{ route('desarrollos.destroy', $lot->id) }}" method="POST"
                        onsubmit="return confirm({{ __('messages.delete_development_confirm') }});" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">{{ __('messages.delete_development') }}</button>
                    </form>
                </div>
        </div>
    </div>
@endsection


@push('scripts')
    <script>
        window.selectedPhase = "{{ $lot->phase_id }}";
        window.selectedStage = "{{ $lot->stage_id }}";
        window.selectedSource = "{{ $lot->source_type }}";
        window.selectedProject = "{{ $lot->project_id }}";
    </script>

    <script src="/assets/js/desarrollo.js"></script>
    <script src="/assets/js/desarrollo/procesarSVG.js"></script>

    <!-- Pickr inicialization igual que en crear -->
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
@endpushz