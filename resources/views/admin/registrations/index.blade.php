@extends('layouts.app')

@section('title', 'Ventas y Registros')

@section('content')
    @php
        $webColumns = collect($columns ?? [])->reject(fn($column) => ($column['key'] ?? null) === 'record_data')->values();
        $canEditTickets = $canEditTickets ?? false;
    @endphp

    <div class="card shadow-sm">
        <div class="card-header d-flex flex-wrap align-items-center gap-3">
            <div class="card-title m-0">
                <h3 class="fw-bold mb-0">Ventas y registros</h3>
            </div>

            <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
                <form method="GET" action="{{ route('admin.registrations.index') }}"
                    class="d-flex align-items-center gap-2">
                    <select name="event_id" class="form-select form-select-sm w-auto"
                        onchange="if(this.value){ window.location='{{ url('/registrations') }}/'+this.value; } else { window.location='{{ route('admin.registrations.index') }}'; }">
                        <option value="">Selecciona evento</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" {{ optional($selectedEvent)->id === $event->id ? 'selected' : '' }}>
                                {{ $event->name }}
                            </option>
                        @endforeach
                    </select>
                </form>

                @if(!empty($selectedEvent) && ($canExportReports ?? false))
                    <a href="{{ route('admin.registrations.export', $selectedEvent->id) }}" class="btn btn-sm btn-success">
                        Exportar CSV
                    </a>
                @endif

                @if(!empty($selectedEvent) && ($canEditReport ?? false))
                    <a href="{{ route('events.edit', $selectedEvent->id) }}#report-config" class="btn btn-sm btn-light-primary">
                        Configurar reporte
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success mb-6">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mb-6">
                    <ul class="mb-0 ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(empty($selectedEvent))
                <div class="alert alert-info mb-0">
                    Selecciona un evento para ver su reporte homologado por transacción.
                </div>
            @elseif(empty($rows))
                <div class="alert alert-warning mb-0">
                    No hay registros para este evento.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_registrations">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                @foreach($webColumns as $column)
                                    <th>{{ $column['label'] }}</th>
                                    @endforeach
                                <th class="d-none">Busqueda</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    @foreach($webColumns as $column)
                                        <td>{{ $row[$column['key']] ?? '-' }}</td>
                                    @endforeach
                                    <td class="d-none">{{ $row['search_blob'] ?? ($row['record_data'] ?? '') }}</td>
                                    <td class="text-end">

                                        @if($row['raw_sale_type'] === 'registration')

                                            @if(!empty($row['registration_entries']))
                                                <button type="button"
                                                    class="btn btn-sm btn-light-info btn-view-registration-details"
                                                    data-entries='@json($row['registration_entries'])'>
                                                    Ver registros
                                                </button>
                                            @else
                                                <a target="_blank" href="{{ route('admin.registrations.reprint', $row['instance_id']) }}"
                                                    class="btn btn-sm btn-light-primary">
                                                    Reimprimir
                                                </a>
                                            @endif

                                        @else

                                            @if((int) ($row['items_count'] ?? 1) > 1)
                                                <button type="button"
                                                    class="btn btn-sm btn-light-info btn-view-ticket-details"
                                                    data-entries='@json($row['ticket_entries'] ?? [])'>
                                                    Ver boletos
                                                </button>
                                            @else
                                                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                    <a target="_blank" href="{{ route('admin.ticket_instances.reprint', $row['instance_id']) }}"
                                                        class="btn btn-sm btn-light-primary">
                                                        Reimprimir
                                                    </a>
                                                    @if($canEditTickets)
                                                        <button type="button"
                                                            class="btn btn-sm btn-light-warning btn-edit-ticket"
                                                            data-entry='@json(($row['ticket_entries'] ?? [])[0] ?? [])'>
                                                            Editar
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif

                                        @endif

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="registrationEntriesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registros de la compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="registrationEntriesBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ticketEntriesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Boletos de la compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="ticketEntriesBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @if($canEditTickets)
        <div class="modal fade" id="editTicketModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <form method="POST" id="editTicketForm" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar ticket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label for="editTicketNombre" class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="editTicketNombre" class="form-control" maxlength="255">
                        </div>

                        <div class="mb-4">
                            <label for="editTicketEmail" class="form-label">Email</label>
                            <input type="text" name="email" id="editTicketEmail" class="form-control" maxlength="255">
                        </div>

                        <div class="mb-4">
                            <label for="editTicketPrice" class="form-label">Precio</label>
                            <input type="number" name="price" id="editTicketPrice" class="form-control" min="0"
                                step="0.01" required>
                        </div>

                        <div class="mb-4">
                            <label for="editTicketPaymentMethod" class="form-label">Método de pago</label>
                            <select name="payment_method" id="editTicketPaymentMethod" class="form-select" required>
                                <option value="card">Tarjeta</option>
                                <option value="cash">Efectivo</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="editTicketSaleChannel" class="form-label">Canal de venta</label>
                            <select name="sale_channel" id="editTicketSaleChannel" class="form-select" required>
                                <option value="stripe">Web</option>
                                <option value="taquilla">Taquilla</option>
                            </select>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" name="is_cortesia" value="1" id="editTicketIsCortesia"
                                class="form-check-input">
                            <label for="editTicketIsCortesia" class="form-check-label">Es cortesía</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('kt_registrations');
            if (!table) {
                return;
            }

            $('#kt_registrations').DataTable({
                pageLength: 25,
                ordering: true,
                order: [],
                searching: true,
                dom: 'frtip',
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
                }
            });

            const registrationModalElement = document.getElementById('registrationEntriesModal');
            const registrationModalBody = document.getElementById('registrationEntriesBody');
            const registrationModal = registrationModalElement ? new bootstrap.Modal(registrationModalElement) : null;
            const reprintUrlTemplate = @json(route('admin.registrations.reprint', ['instance' => '__INSTANCE__']));
            const ticketModalElement = document.getElementById('ticketEntriesModal');
            const ticketModalBody = document.getElementById('ticketEntriesBody');
            const ticketModal = ticketModalElement ? new bootstrap.Modal(ticketModalElement) : null;
            const ticketReprintUrlTemplate = @json(route('admin.ticket_instances.reprint', ['instance' => '__INSTANCE__']));
            const canEditTickets = @json($canEditTickets);
            const ticketEditUrlTemplate = canEditTickets
                ? @json(route('admin.registrations.tickets.update', ['instance' => '__INSTANCE__']))
                : null;
            const editTicketModalElement = document.getElementById('editTicketModal');
            const editTicketModal = editTicketModalElement ? new bootstrap.Modal(editTicketModalElement) : null;
            const editTicketForm = document.getElementById('editTicketForm');
            const editTicketNombre = document.getElementById('editTicketNombre');
            const editTicketEmail = document.getElementById('editTicketEmail');
            const editTicketPrice = document.getElementById('editTicketPrice');
            const editTicketPaymentMethod = document.getElementById('editTicketPaymentMethod');
            const editTicketSaleChannel = document.getElementById('editTicketSaleChannel');
            const editTicketIsCortesia = document.getElementById('editTicketIsCortesia');

            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const extractFieldValue = (entry, label) => {
                const fields = Array.isArray(entry?.fields) ? entry.fields : [];
                const field = fields.find((item) => String(item?.label ?? '').toLowerCase() === label);

                return field?.value ?? '';
            };

            const syncCourtesyEmail = () => {
                if (!editTicketEmail || !editTicketIsCortesia) {
                    return;
                }

                if (editTicketIsCortesia.checked) {
                    editTicketEmail.dataset.previousEmail = editTicketEmail.value === 'CORTESIA'
                        ? (editTicketEmail.dataset.previousEmail ?? '')
                        : editTicketEmail.value;
                    editTicketEmail.value = 'CORTESIA';
                    editTicketEmail.readOnly = true;
                    return;
                }

                editTicketEmail.readOnly = false;
                if (editTicketEmail.value === 'CORTESIA') {
                    editTicketEmail.value = editTicketEmail.dataset.previousEmail ?? '';
                }
            };

            const openEditTicketModal = (entry) => {
                if (!editTicketModal || !editTicketForm || !ticketEditUrlTemplate) {
                    return;
                }

                const instanceId = entry?.instance_id ?? '';
                if (!instanceId) {
                    return;
                }

                const email = entry?.email ?? extractFieldValue(entry, 'email');
                editTicketForm.action = ticketEditUrlTemplate.replace('__INSTANCE__', encodeURIComponent(instanceId));
                editTicketNombre.value = entry?.nombre ?? extractFieldValue(entry, 'nombre');
                editTicketEmail.value = email;
                editTicketEmail.dataset.previousEmail = email === 'CORTESIA' ? '' : email;
                editTicketPrice.value = Number(entry?.price ?? 0).toFixed(2);
                editTicketPaymentMethod.value = entry?.payment_method ?? 'card';
                editTicketSaleChannel.value = entry?.sale_channel ?? 'stripe';
                editTicketIsCortesia.checked = Boolean(entry?.is_cortesia) || email === 'CORTESIA';
                syncCourtesyEmail();
                if (ticketModalElement?.classList.contains('show')) {
                    ticketModal.hide();
                }
                editTicketModal.show();
            };

            if (editTicketIsCortesia) {
                editTicketIsCortesia.addEventListener('change', syncCourtesyEmail);
            }

            $(document).on('click', '.btn-view-registration-details', function () {
                if (!registrationModal || !registrationModalBody) {
                    return;
                }

                let entries = $(this).attr('data-entries') ?? '[]';
                try {
                    entries = JSON.parse(entries);
                } catch (e) {
                    entries = [];
                }

                if (!Array.isArray(entries) || entries.length === 0) {
                    registrationModalBody.innerHTML = '<div class="alert alert-warning mb-0">No hay registros disponibles para esta compra.</div>';
                    registrationModal.show();
                    return;
                }

                let html = '';

                entries.forEach((entry, index) => {
                    const title = escapeHtml(entry?.title ?? `Registro ${index + 1}`);
                    const instanceId = entry?.instance_id ?? '';
                    const fields = Array.isArray(entry?.fields) ? entry.fields : [];
                    const reprintUrl = reprintUrlTemplate.replace('__INSTANCE__', encodeURIComponent(instanceId));

                    let rowsHtml = '';
                    fields.forEach((field) => {
                        const label = escapeHtml(field?.label ?? 'Campo');
                        const value = escapeHtml(field?.value ?? '-');
                        rowsHtml += `<tr><th style="width: 220px;">${label}</th><td>${value}</td></tr>`;
                    });

                    if (rowsHtml === '') {
                        rowsHtml = '<tr><td colspan="2" class="text-muted">Sin datos de formulario</td></tr>';
                    }

                    html += `
                        <div class="card card-bordered mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">${title}</h6>
                                <a target="_blank" href="${reprintUrl}" class="btn btn-sm btn-light-primary">Reimprimir</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-row-dashed mb-0">
                                        <tbody>${rowsHtml}</tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                });

                registrationModalBody.innerHTML = html;
                registrationModal.show();
            });

            $(document).on('click', '.btn-view-ticket-details', function () {
                if (!ticketModal || !ticketModalBody) {
                    return;
                }

                let entries = $(this).attr('data-entries') ?? '[]';
                try {
                    entries = JSON.parse(entries);
                } catch (e) {
                    entries = [];
                }

                if (!Array.isArray(entries) || entries.length === 0) {
                    ticketModalBody.innerHTML = '<div class="alert alert-warning mb-0">No hay boletos disponibles para esta compra.</div>';
                    ticketModal.show();
                    return;
                }

                let html = '';

                entries.forEach((entry, index) => {
                    const title = escapeHtml(entry?.title ?? `Boleto ${index + 1}`);
                    const instanceId = entry?.instance_id ?? '';
                    const fields = Array.isArray(entry?.fields) ? entry.fields : [];
                    const reprintUrl = ticketReprintUrlTemplate.replace('__INSTANCE__', encodeURIComponent(instanceId));
                    const editButton = canEditTickets
                        ? `<button type="button" class="btn btn-sm btn-light-warning btn-edit-ticket" data-entry="${escapeHtml(JSON.stringify(entry))}">Editar</button>`
                        : '';

                    let rowsHtml = '';
                    fields.forEach((field) => {
                        const label = escapeHtml(field?.label ?? 'Campo');
                        const value = escapeHtml(field?.value ?? '-');
                        rowsHtml += `<tr><th style="width: 220px;">${label}</th><td>${value}</td></tr>`;
                    });

                    if (rowsHtml === '') {
                        rowsHtml = '<tr><td colspan="2" class="text-muted">Sin datos del boleto</td></tr>';
                    }

                    html += `
                        <div class="card card-bordered mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">${title}</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <a target="_blank" href="${reprintUrl}" class="btn btn-sm btn-light-primary">Reimprimir</a>
                                    ${editButton}
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-row-dashed mb-0">
                                        <tbody>${rowsHtml}</tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                });

                ticketModalBody.innerHTML = html;
                ticketModal.show();
            });

            $(document).on('click', '.btn-edit-ticket', function () {
                let entry = $(this).attr('data-entry') ?? '{}';
                try {
                    entry = JSON.parse(entry);
                } catch (e) {
                    entry = {};
                }

                openEditTicketModal(entry);
            });
        });
    </script>
@endpush
