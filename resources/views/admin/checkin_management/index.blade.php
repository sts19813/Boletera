@extends('layouts.app')

@section('title', 'Modulo Admin Checkin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-bold text-gray-800 mb-1">Modulo de Checkin para Administradores</h1>
            <div class="text-muted">Historico completo de escaneos y configuracion de limites.</div>
        </div>
    </div>

    <div id="checkin-management-alert" class="alert d-none mb-5"></div>

    @if(session('status'))
        <div class="alert alert-success mb-5">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-5">
            <ul class="mb-0 ps-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-flush">
        <div class="card-header pt-7">
            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-6 fw-bold"
                id="checkin-management-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'history' ? 'active' : '' }}" id="tab-history-btn"
                        data-bs-toggle="tab" data-bs-target="#tab-history" type="button" role="tab"
                        aria-controls="tab-history" aria-selected="{{ $activeTab === 'history' ? 'true' : 'false' }}">
                        Historico por item
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'tickets' ? 'active' : '' }}" id="tab-tickets-btn"
                        data-bs-toggle="tab" data-bs-target="#tab-tickets" type="button" role="tab"
                        aria-controls="tab-tickets" aria-selected="{{ $activeTab === 'tickets' ? 'true' : 'false' }}">
                        Limite maximo por ticket
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'registrations' ? 'active' : '' }}" id="tab-registrations-btn"
                        data-bs-toggle="tab" data-bs-target="#tab-registrations" type="button" role="tab"
                        aria-controls="tab-registrations"
                        aria-selected="{{ $activeTab === 'registrations' ? 'true' : 'false' }}">
                        Maximo de scans por registro
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body pt-6">
            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'history' ? 'show active' : '' }}" id="tab-history"
                    role="tabpanel" aria-labelledby="tab-history-btn">
                    <div class="d-flex flex-column mb-5">
                        <h3 class="fw-bold mb-1">Historico por item</h3>
                        <span class="text-muted fs-7">Cuantas veces se escaneo cada boleto o registro.</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed" id="kt_checkin_history_table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                    <th>Item</th>
                                    <th>Tipo</th>
                                    <th>Evento</th>
                                    <th>Comprador</th>
                                    <th>Exitosos</th>
                                    <th>Intentos</th>
                                    <th>Ultimo scan</th>
                                    <th class="text-end" width="150">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($historyItems as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $item->ticket?->name ?? 'Registro' }}</div>
                                            <div class="text-muted fs-8">{{ $item->id }}</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $item->ticket_id ? 'primary' : 'warning' }}">
                                                {{ $item->ticket_id ? 'Boleto' : 'Registro' }}
                                            </span>
                                        </td>
                                        <td>{{ $item->evento?->name ?? 'Sin evento' }}</td>
                                        <td>
                                            <div>{{ $item->nombre ?: 'Sin nombre' }}</div>
                                            <div class="text-muted fs-8">{{ $item->email ?: 'Sin email' }}</div>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $item->successful_scans }}/{{ $item->resolved_max_checkins }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $item->total_scans }}</span>
                                            <div class="text-muted fs-8">
                                                usados: {{ $item->used_scans }} | invalidos: {{ $item->invalid_scans }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($item->checkins_max_scanned_at)
                                                {{ \Illuminate\Support\Carbon::parse($item->checkins_max_scanned_at)->format('d/m/Y H:i:s') }}
                                            @else
                                                <span class="text-muted">Sin escaneos</span>
                                            @endif
                                        </td>
                                        <td class="text-end" width="150">
                                            <a href="{{ route('admin.checkin_management.show', ['instance' => $item->id, 'tab' => 'history']) }}"
                                                class="btn btn-sm btn-light-primary">
                                                Ver detalle
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'tickets' ? 'show active' : '' }}" id="tab-tickets"
                    role="tabpanel" aria-labelledby="tab-tickets-btn">
                    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-end mb-5 gap-4">
                        <div>
                            <h3 class="fw-bold mb-1">Limite maximo por ticket</h3>
                            <span class="text-muted fs-7">Actualizacion por AJAX sin recargar la pagina.</span>
                        </div>
                        <div class="w-100 w-md-300px">
                            <label class="form-label">Filtrar por evento</label>
                            <select class="form-select" id="ticket-event-filter">
                                <option value="">Todos</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}" @selected($selectedTicketEventId === (string) $event->id)>
                                        {{ $event->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_checkin_ticket_limits_table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                    <th class="d-none">EventId</th>
                                    <th>Ticket</th>
                                    <th>Evento</th>
                                    <th>Maximo actual</th>
                                    <th class="text-end">Guardar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticketLimits as $ticket)
                                    <tr>
                                        <td class="d-none">{{ $ticket->event_id }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $ticket->name }}</div>
                                            <div class="text-muted fs-8">{{ $ticket->id }}</div>
                                        </td>
                                        <td>{{ $ticket->event?->name ?? 'Sin evento' }}</td>
                                        <td><span class="fw-bold js-current-limit">{{ $ticket->max_checkins ?? 1 }}</span></td>
                                        <td class="text-end">
                                            <form method="POST"
                                                action="{{ route('admin.checkin_management.tickets.update', $ticket) }}"
                                                class="d-flex gap-2 align-items-center justify-content-end js-ajax-limit-form"
                                                data-update-field="max_checkins">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="max_checkins" class="form-control w-100px" min="1"
                                                    max="9999" value="{{ $ticket->max_checkins ?? 1 }}" required>
                                                <button class="btn btn-sm btn-light-primary" type="submit">Actualizar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'registrations' ? 'show active' : '' }}" id="tab-registrations"
                    role="tabpanel" aria-labelledby="tab-registrations-btn">
                    <div class="d-flex flex-column mb-5">
                        <h3 class="fw-bold mb-1">Maximo de scans por registro</h3>
                        <span class="text-muted fs-7">Aplica solo a eventos de tipo registro.</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_checkin_registration_limits_table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                    <th>Evento registro</th>
                                    <th>Maximo actual</th>
                                    <th class="text-end">Guardar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($registrationEvents as $event)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $event->name }}</div>
                                            <div class="text-muted fs-8">{{ $event->id }}</div>
                                        </td>
                                        <td><span class="fw-bold js-current-limit">{{ $event->registration_max_checkins ?? 1 }}</span></td>
                                        <td class="text-end">
                                            <form method="POST"
                                                action="{{ route('admin.checkin_management.events.update', $event) }}"
                                                class="d-flex gap-2 align-items-center justify-content-end js-ajax-limit-form"
                                                data-update-field="registration_max_checkins">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="registration_max_checkins" class="form-control w-100px"
                                                    min="1" max="9999" value="{{ $event->registration_max_checkins ?? 1 }}"
                                                    required>
                                                <button class="btn btn-sm btn-light-primary" type="submit">Actualizar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const languageConfig = {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
            };

            const commonConfig = {
                pageLength: 25,
                ordering: false,
                searching: true,
                dom: 'frtip',
                language: languageConfig
            };

            const historyTable = $('#kt_checkin_history_table').DataTable(commonConfig);
            const ticketsTable = $('#kt_checkin_ticket_limits_table').DataTable({
                ...commonConfig,
                columnDefs: [{ targets: 0, visible: false, searchable: true }]
            });
            const registrationsTable = $('#kt_checkin_registration_limits_table').DataTable(commonConfig);

            if (historyTable && ticketsTable && registrationsTable) {
                // Initialize all tables in this module.
            }

            const tabButtons = document.querySelectorAll('#checkin-management-tabs button[data-bs-toggle="tab"]');
            tabButtons.forEach((btn) => {
                btn.addEventListener('shown.bs.tab', function (event) {
                    const target = event.target.getAttribute('data-bs-target');
                    if (target) {
                        window.location.hash = target;
                    }
                });
            });

            const hashTab = window.location.hash;
            if (hashTab && document.querySelector(`#checkin-management-tabs button[data-bs-target="${hashTab}"]`)) {
                new bootstrap.Tab(document.querySelector(`#checkin-management-tabs button[data-bs-target="${hashTab}"]`)).show();
            }

            $('#ticket-event-filter').on('change', function () {
                const eventId = $(this).val();
                const exactFilter = eventId ? '^' + eventId + '$' : '';
                ticketsTable.column(0).search(exactFilter, true, false).draw();
            });

            $('#ticket-event-filter').trigger('change');

            const alertBox = document.getElementById('checkin-management-alert');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const showAlert = (type, message) => {
                alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
                alertBox.textContent = message;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            document.querySelectorAll('.js-ajax-limit-form').forEach((form) => {
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    const button = form.querySelector('button[type="submit"]');
                    const input = form.querySelector('input[type="number"]');
                    const row = form.closest('tr');
                    const limitCell = row.querySelector('.js-current-limit');
                    const fieldName = form.dataset.updateField;
                    const formData = new FormData(form);

                    button.disabled = true;
                    input.readOnly = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            const errorMessage = payload?.message
                                || Object.values(payload?.errors || {}).flat().join(' ')
                                || 'No fue posible actualizar el limite.';
                            throw new Error(errorMessage);
                        }

                        if (limitCell && fieldName && Object.prototype.hasOwnProperty.call(payload, fieldName)) {
                            limitCell.textContent = payload[fieldName];
                        }

                        showAlert('success', payload.message || 'Limite actualizado correctamente.');
                    } catch (error) {
                        showAlert('error', error.message || 'Ocurrio un error al actualizar.');
                    } finally {
                        button.disabled = false;
                        input.readOnly = false;
                    }
                });
            });
        });
    </script>
@endpush
