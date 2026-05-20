@extends('layouts.app')

@section('title', 'Ventas y Registros')

@section('content')
    @php
        $webColumns = collect($columns ?? [])->reject(fn($column) => ($column['key'] ?? null) === 'record_data')->values();
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
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    @foreach($webColumns as $column)
                                        <td>{{ $row[$column['key']] ?? '-' }}</td>
                                    @endforeach
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

                                            <a target="_blank" href="{{ route('admin.ticket_instances.reprint', $row['instance_id']) }}"
                                                class="btn btn-sm btn-light-primary">
                                                Reimprimir
                                            </a>

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
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
                }
            });

            const registrationModalElement = document.getElementById('registrationEntriesModal');
            const registrationModalBody = document.getElementById('registrationEntriesBody');
            const registrationModal = registrationModalElement ? new bootstrap.Modal(registrationModalElement) : null;
            const reprintUrlTemplate = @json(route('admin.registrations.reprint', ['instance' => '__INSTANCE__']));

            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

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
        });
    </script>
@endpush
