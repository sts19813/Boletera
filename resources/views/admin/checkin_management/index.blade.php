@extends('layouts.app')

@section('title', 'Modulo Admin Checkin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-bold text-gray-800 mb-1">Modulo de Checkin para Administradores</h1>
            <div class="text-muted">Historico completo de escaneos y configuracion de limites.</div>
        </div>
    </div>

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

    <div class="card mb-5">
        <div class="card-header border-0 pt-6">
            <div>
                <h3 class="card-title fw-bold">Historico por item</h3>
                <span class="text-muted fs-7">Cuantas veces se escaneo cada boleto o registro.</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <form method="GET" class="row g-3 mb-5">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="history_search" value="{{ $historySearch }}"
                        placeholder="ID, nombre, email, referencia...">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Evento</label>
                    <select class="form-select" name="history_event_id">
                        <option value="">Todos</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" @selected((string) $historyEventId === (string) $event->id)>
                                {{ $event->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="history_type">
                        <option value="">Todos</option>
                        <option value="ticket" @selected($historyType === 'ticket')>Boletos</option>
                        <option value="registration" @selected($historyType === 'registration')>Registros</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid w-100">
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-gray-500 fw-semibold text-uppercase fs-7">
                            <th>Item</th>
                            <th>Tipo</th>
                            <th>Evento</th>
                            <th>Comprador</th>
                            <th>Exitosos</th>
                            <th>Intentos</th>
                            <th>Ultimo scan</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historyItems as $item)
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
                                <td class="text-end">
                                    <a href="{{ route('admin.checkin_management.show', ['instance' => $item->id] + request()->query()) }}"
                                        class="btn btn-sm btn-light-primary">
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-6">No hay items para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $historyItems->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <div class="card mb-5">
        <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="card-title fw-bold">Limite maximo por ticket</h3>
                <span class="text-muted fs-7">Solo administradores pueden modificarlo.</span>
            </div>
        </div>

        <div class="card-body pt-0">
            <form method="GET" class="row g-3 mb-5">
                <input type="hidden" name="history_search" value="{{ $historySearch }}">
                <input type="hidden" name="history_event_id" value="{{ $historyEventId }}">
                <input type="hidden" name="history_type" value="{{ $historyType }}">
                <input type="hidden" name="registration_event_search" value="{{ $registrationEventSearch }}">

                <div class="col-md-4">
                    <label class="form-label">Filtrar por evento</label>
                    <select class="form-select" name="ticket_event_id">
                        <option value="">Todos</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" @selected((string) $ticketEventFilter === (string) $event->id)>
                                {{ $event->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid w-100">
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-gray-500 fw-semibold text-uppercase fs-7">
                            <th>Ticket</th>
                            <th>Evento</th>
                            <th>Maximo actual</th>
                            <th class="text-end">Guardar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ticketLimits as $ticket)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $ticket->name }}</div>
                                    <div class="text-muted fs-8">{{ $ticket->id }}</div>
                                </td>
                                <td>{{ $ticket->event?->name ?? 'Sin evento' }}</td>
                                <td>
                                    <span class="fw-bold">{{ $ticket->max_checkins ?? 1 }}</span>
                                </td>
                                <td class="text-end">
                                    <form method="POST"
                                        action="{{ route('admin.checkin_management.tickets.update', $ticket) }}"
                                        class="d-flex gap-2 align-items-center justify-content-end">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="max_checkins" class="form-control w-100px" min="1"
                                            max="9999" value="{{ $ticket->max_checkins ?? 1 }}" required>
                                    <button class="btn btn-sm btn-light-primary" type="submit">Actualizar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-6">No hay tickets para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $ticketLimits->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div>
                <h3 class="card-title fw-bold">Maximo de scans por registro</h3>
                <span class="text-muted fs-7">Aplica solo a eventos de tipo registro.</span>
            </div>
        </div>

        <div class="card-body pt-0">
            <form method="GET" class="row g-3 mb-5">
                <input type="hidden" name="history_search" value="{{ $historySearch }}">
                <input type="hidden" name="history_event_id" value="{{ $historyEventId }}">
                <input type="hidden" name="history_type" value="{{ $historyType }}">
                <input type="hidden" name="ticket_event_id" value="{{ $ticketEventFilter }}">

                <div class="col-md-4">
                    <label class="form-label">Buscar evento</label>
                    <input type="text" class="form-control" name="registration_event_search"
                        value="{{ $registrationEventSearch }}" placeholder="Nombre del evento...">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid w-100">
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-gray-500 fw-semibold text-uppercase fs-7">
                            <th>Evento registro</th>
                            <th>Maximo actual</th>
                            <th class="text-end">Guardar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registrationEvents as $event)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $event->name }}</div>
                                    <div class="text-muted fs-8">{{ $event->id }}</div>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $event->registration_max_checkins ?? 1 }}</span>
                                </td>
                                <td class="text-end">
                                    <form method="POST"
                                        action="{{ route('admin.checkin_management.events.update', $event) }}"
                                        class="d-flex gap-2 align-items-center justify-content-end">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="registration_max_checkins" class="form-control w-100px"
                                            min="1" max="9999" value="{{ $event->registration_max_checkins ?? 1 }}"
                                            required>
                                    <button class="btn btn-sm btn-light-primary" type="submit">Actualizar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-6">
                                    No hay eventos de tipo registro.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $registrationEvents->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
