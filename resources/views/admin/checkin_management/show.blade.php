@extends('layouts.app')

@section('title', 'Detalle de Escaneos')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-bold text-gray-800 mb-1">Detalle de Escaneos por Item</h1>
            <div class="text-muted">Historial completo del item seleccionado.</div>
        </div>
        <a href="{{ route('admin.checkin_management.index', request()->query()) }}" class="btn btn-light-primary">
            Volver al modulo
        </a>
    </div>

    <div class="card mb-5">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="fw-semibold text-muted">Item</div>
                    <div class="fw-bold">{{ $instance->ticket?->name ?? 'Registro' }}</div>
                    <div class="text-muted fs-8">{{ $instance->id }}</div>
                </div>

                <div class="col-md-4">
                    <div class="fw-semibold text-muted">Evento</div>
                    <div class="fw-bold">{{ $instance->evento?->name ?? 'Sin evento' }}</div>
                    <div class="text-muted fs-8">Tipo: {{ $instance->ticket_id ? 'Boleto' : 'Registro' }}</div>
                </div>

                <div class="col-md-4">
                    <div class="fw-semibold text-muted">Comprador</div>
                    <div class="fw-bold">{{ $instance->nombre ?: 'Sin nombre' }}</div>
                    <div class="text-muted fs-8">{{ $instance->email ?: 'Sin email' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $summaryTotal }}</div>
                    <div class="text-muted">Intentos totales</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold text-success">{{ $summarySuccess }}</div>
                    <div class="text-muted">Escaneos exitosos</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold text-warning">{{ $summaryUsed }}</div>
                    <div class="text-muted">Intentos usados</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold text-danger">{{ $summaryInvalid }}</div>
                    <div class="text-muted">Intentos invalidos</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="card-title fw-bold">Historial detallado</h3>
                <span class="text-muted fs-7">Progreso exitoso: {{ $summarySuccess }}/{{ $maxCheckins }}</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-gray-500 fw-semibold text-uppercase fs-7">
                            <th>Fecha y hora</th>
                            <th>Resultado</th>
                            <th>Mensaje</th>
                            <th>IP escaner</th>
                            <th>Hash</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $scan)
                            <tr>
                                <td>{{ $scan->scanned_at?->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    @php
                                        $badgeClass = match ($scan->result) {
                                            'success' => 'success',
                                            'used' => 'warning',
                                            default => 'danger',
                                        };
                                    @endphp
                                    <span class="badge badge-light-{{ $badgeClass }} text-uppercase">{{ $scan->result }}</span>
                                </td>
                                <td>{{ $scan->message ?: 'Sin mensaje' }}</td>
                                <td>{{ $scan->scanner_ip ?: 'Sin IP' }}</td>
                                <td class="text-truncate" style="max-width: 220px;">{{ $scan->hash ?: 'Sin hash' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-6">Este item no tiene escaneos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $history->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
