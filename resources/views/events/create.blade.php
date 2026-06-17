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
                                <input type="number" id="total_asientos" name="total_asientos" min="1" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Mapa de asientos</label>
                                <select name="has_seat_mapping" id="has_seat_mapping" class="form-select">
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold d-block">Venta en línea</label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="stop_online_sales" value="1"
                                        {{ old('stop_online_sales', 0) ? 'checked' : '' }}>
                                    <span class="form-check-label ms-2">
                                        Detener venta en línea (solo taquilla/admin podrán vender)
                                    </span>
                                </label>
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

                            <div class="col-md-3">
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

                            <div class="col-md-4 d-none" id="registration_max_checkins_wrapper">
                                <label class="form-label fw-bold">Check-ins por registro</label>
                                <input type="number" min="1" name="registration_max_checkins" class="form-control"
                                    value="{{ old('registration_max_checkins', 1) }}" placeholder="Ej. 3">
                            </div>

                            <div class="col-md-4 d-none" id="template_wrapper">
                                <label class="form-label fw-bold">Plantilla</label>
                                <select name="template" class="form-select">
                                    <option value="registration">Registro / Inscripción</option>
                                    <option value="default">Default</option>
                                </select>
                            </div>

                            <div class="col-md-3 d-none registration-field">
                                <label class="form-label fw-bold">Modo de formulario</label>
                                <select name="registration_form_mode" id="registration_form_mode" class="form-select">
                                    <option value="manual" {{ old('registration_form_mode', 'manual') === 'manual' ? 'selected' : '' }}>Manual</option>
                                    <option value="builder" {{ old('registration_form_mode') === 'builder' ? 'selected' : '' }}>Builder</option>
                                </select>
                            </div>

                            <div class="col-md-3 d-none registration-field" id="builder_form_wrapper">
                                <label class="form-label fw-bold">Formulario builder</label>
                                <select name="registration_form_id" class="form-select">
                                    <option value="">Selecciona formulario</option>
                                    @foreach(($registrationForms ?? collect()) as $registrationForm)
                                        <option value="{{ $registrationForm->id }}" {{ old('registration_form_id') === $registrationForm->id ? 'selected' : '' }}>
                                            {{ $registrationForm->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 d-none registration-field" id="manual_form_wrapper">
                                <label class="form-label fw-bold">Tipo de formulario</label>
                                <select name="template_form" class="form-select">
                                    <option value="golf_team">Equipo de Golf (3 jugadores)</option>
                                    <option value="cena_gala">Cena Gala (multiples personas)</option>
                                    <option value="whatsapp_direct">Registro WhatsApp Directo (EAFC 26)</option>
                                    <option value="dia_padres_cumbres">Registro directo día padres cumbres</option>
                                </select>
                            </div>

                            <div class="col-md-3 d-none registration-field">
                                <label class="form-label fw-bold d-block">
                                    <input type="checkbox" name="allows_multiple_registrations" value="1">
                                    Permite múltiples inscripciones en una sola compra
                                </label>
                            </div>

                            <div class="col-md-6 d-none registration-field">
                                <label class="form-label fw-bold">Link grupo WhatsApp</label>
                                <input type="url"
                                    name="whatsapp_group_link"
                                    class="form-control"
                                    value="{{ old('whatsapp_group_link') }}"
                                    placeholder="https://chat.whatsapp.com/...">
                            </div>

                        </div>
                    </div>
                </div>

                @if($canEditReports ?? false)
                    <div class="card shadow-sm mb-5" id="report-config">
                        <div class="card-header">
                            <h4 class="card-title fw-bold">Configuración de reporte</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">
                                Define columnas visibles y orden para reportes y exportaciones de este evento.
                            </p>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Columna</th>
                                            <th class="text-center">Mostrar</th>
                                            <th style="width: 140px;">Orden</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reportColumns as $key => $label)
                                            <tr>
                                                <td>{{ $label }}</td>
                                                <td class="text-center">
                                                    <input type="checkbox"
                                                        name="report_columns[{{ $key }}][enabled]"
                                                        value="1"
                                                        {{ old("report_columns.$key.enabled", ($reportColumnConfig[$key] ?? false) ? 1 : 0) ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        min="1"
                                                        max="999"
                                                        name="report_columns[{{ $key }}][order]"
                                                        class="form-control"
                                                        value="{{ old("report_columns.$key.order", $reportColumnOrder[$key] ?? $loop->iteration) }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @php
                    $couponRows = old('coupons', ($eventCoupons ?? collect())->map(function ($coupon) {
                        return [
                            'id' => $coupon->id,
                            'code' => $coupon->code,
                            'auto_apply' => $coupon->auto_apply ? 1 : 0,
                            'discount_type' => $coupon->discount_type,
                            'discount_value' => $coupon->discount_value,
                            'min_qty' => $coupon->min_qty ?? 1,
                            'max_tickets' => $coupon->max_tickets,
                            'starts_at' => optional($coupon->starts_at)->format('Y-m-d\\TH:i'),
                            'ends_at' => optional($coupon->ends_at)->format('Y-m-d\\TH:i'),
                            'is_active' => $coupon->is_active ? 1 : 0,
                        ];
                    })->toArray());

                    if (empty($couponRows)) {
                        $couponRows = [[]];
                    }
                @endphp

                <div class="card shadow-sm mb-5">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title fw-bold">Descuentos y cupones</h4>
                        <button type="button" id="addCouponRowBtn" class="btn btn-light-primary btn-sm">Agregar regla</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Auto</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Mín boletos</th>
                                        <th>Máx boletos</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Activo</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="couponRows">
                                    @foreach($couponRows as $i => $couponRow)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="coupons[{{ $i }}][id]" value="{{ $couponRow['id'] ?? '' }}">
                                                <input type="text" name="coupons[{{ $i }}][code]" class="form-control" value="{{ $couponRow['code'] ?? '' }}">
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="coupons[{{ $i }}][auto_apply]" value="1" {{ !empty($couponRow['auto_apply']) ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <select name="coupons[{{ $i }}][discount_type]" class="form-select">
                                                    <option value="">Selecciona</option>
                                                    <option value="percentage" {{ ($couponRow['discount_type'] ?? '') === 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                                                    <option value="fixed" {{ ($couponRow['discount_type'] ?? '') === 'fixed' ? 'selected' : '' }}>Monto fijo</option>
                                                    <option value="unit_price" {{ ($couponRow['discount_type'] ?? '') === 'unit_price' ? 'selected' : '' }}>Precio final c/u</option>
                                                </select>
                                            </td>
                                            <td><input type="number" step="0.01" min="0.01" name="coupons[{{ $i }}][discount_value]" class="form-control" value="{{ $couponRow['discount_value'] ?? '' }}"></td>
                                            <td><input type="number" min="1" name="coupons[{{ $i }}][min_qty]" class="form-control" value="{{ $couponRow['min_qty'] ?? 1 }}"></td>
                                            <td><input type="number" min="1" name="coupons[{{ $i }}][max_tickets]" class="form-control" value="{{ $couponRow['max_tickets'] ?? '' }}"></td>
                                            <td><input type="datetime-local" name="coupons[{{ $i }}][starts_at]" class="form-control" value="{{ $couponRow['starts_at'] ?? '' }}"></td>
                                            <td><input type="datetime-local" name="coupons[{{ $i }}][ends_at]" class="form-control" value="{{ $couponRow['ends_at'] ?? '' }}"></td>
                                            <td class="text-center">
                                                <input type="checkbox" name="coupons[{{ $i }}][is_active]" value="1" {{ !empty($couponRow['is_active']) ? 'checked' : '' }}>
                                            </td>
                                            <td><button type="button" class="btn btn-light-danger btn-sm remove-coupon-row">Quitar</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
                                <input type="file" id="svg_input" name="svg_image" accept=".svg" class="form-control">
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
                                <input type="text" id="modal_selector" name="modal_selector" class="form-control" placeholder="svg *">
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
            const registrationMaxCheckinsWrapper = document.getElementById('registration_max_checkins_wrapper');

            const registrationFields = document.querySelectorAll('.registration-field');
            const registrationFormMode = document.getElementById('registration_form_mode');
            const manualFormWrapper = document.getElementById('manual_form_wrapper');
            const builderFormWrapper = document.getElementById('builder_form_wrapper');

            const totalAsientos = document.querySelector('[name="total_asientos"]');
            const hasSeatMapping = document.getElementById('has_seat_mapping');

            function toggleRegistrationMode() {

                const isReg = isRegistration.value === '1';

                // 🔥 Mostrar / ocultar wrappers individuales
                priceWrapper.classList.toggle('d-none', !isReg);
                templateWrapper.classList.toggle('d-none', !isReg);
                maxCapacityWrapper.classList.toggle('d-none', !isReg);
                registrationMaxCheckinsWrapper.classList.toggle('d-none', !isReg);

                // 🔥 Mostrar / ocultar los campos adicionales
                registrationFields.forEach(field => {
                    field.classList.toggle('d-none', !isReg);
                });

                const mode = registrationFormMode?.value ?? 'manual';
                if (manualFormWrapper) manualFormWrapper.classList.toggle('d-none', !isReg || mode !== 'manual');
                if (builderFormWrapper) builderFormWrapper.classList.toggle('d-none', !isReg || mode !== 'builder');

                if (isReg) {

                    totalAsientos.value = 1;
                    totalAsientos.removeAttribute('required');
                    totalAsientos.setAttribute('readonly', true);

                    hasSeatMapping.value = 0;
                    hasSeatMapping.setAttribute('disabled', true);

                } else {
                    totalAsientos.removeAttribute('readonly');
                    totalAsientos.setAttribute('required', true);

                    hasSeatMapping.removeAttribute('disabled');
                }
            }

            isRegistration.addEventListener('change', toggleRegistrationMode);
            registrationFormMode?.addEventListener('change', toggleRegistrationMode);
            toggleRegistrationMode();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rowsContainer = document.getElementById('couponRows');
            const addBtn = document.getElementById('addCouponRowBtn');

            if (!rowsContainer || !addBtn) {
                return;
            }

            const rowTemplate = (index) => `
                <tr>
                    <td>
                        <input type="hidden" name="coupons[${index}][id]" value="">
                        <input type="text" name="coupons[${index}][code]" class="form-control">
                    </td>
                    <td class="text-center"><input type="checkbox" name="coupons[${index}][auto_apply]" value="1"></td>
                    <td>
                        <select name="coupons[${index}][discount_type]" class="form-select">
                            <option value="">Selecciona</option>
                            <option value="percentage">Porcentaje</option>
                            <option value="fixed">Monto fijo</option>
                            <option value="unit_price">Precio final c/u</option>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" min="0.01" name="coupons[${index}][discount_value]" class="form-control"></td>
                    <td><input type="number" min="1" name="coupons[${index}][min_qty]" class="form-control" value="1"></td>
                    <td><input type="number" min="1" name="coupons[${index}][max_tickets]" class="form-control"></td>
                    <td><input type="datetime-local" name="coupons[${index}][starts_at]" class="form-control"></td>
                    <td><input type="datetime-local" name="coupons[${index}][ends_at]" class="form-control"></td>
                    <td class="text-center"><input type="checkbox" name="coupons[${index}][is_active]" value="1"></td>
                    <td><button type="button" class="btn btn-light-danger btn-sm remove-coupon-row">Quitar</button></td>
                </tr>
            `;

            const refreshIndices = () => {
                [...rowsContainer.querySelectorAll('tr')].forEach((row, index) => {
                    row.querySelectorAll('input, select').forEach((input) => {
                        const name = input.getAttribute('name');
                        if (!name) {
                            return;
                        }
                        input.setAttribute('name', name.replace(/coupons\[\d+\]/, `coupons[${index}]`));
                    });
                });
            };

            addBtn.addEventListener('click', function () {
                rowsContainer.insertAdjacentHTML('beforeend', rowTemplate(rowsContainer.querySelectorAll('tr').length));
            });

            rowsContainer.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-coupon-row');
                if (!button) {
                    return;
                }
                button.closest('tr')?.remove();
                refreshIndices();
            });
        });
    </script>
@endpush
