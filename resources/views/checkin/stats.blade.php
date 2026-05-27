@extends('layouts.checkin')

@section('title', 'Estadísticas de Check-in')

@section('content')
<div class="container py-5" style="max-width: 1100px;">

    <div class="card shadow-sm mb-5">
        <div class="card-body p-6 p-lg-8">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4 mb-5">
                <div>
                    <h2 class="fw-bold mb-2">Estadísticas de Check-in</h2>
                    <p class="text-muted mb-0">Métricas filtradas por tus eventos permitidos.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ url('/checkin') . (!empty($selectedEventId) ? '?event_id=' . urlencode($selectedEventId) : '') }}"
                        class="btn btn-light-primary">
                        Volver al escáner
                    </a>
                    @role('admin')
                        <a href="{{ route('admin.checkin_management.index') }}" class="btn btn-primary">
                            Modulo admin checkin
                        </a>
                    @endrole
                </div>
            </div>

            <form method="GET" action="{{ route('checkin.stats') }}" class="row g-4 align-items-end">
                <div class="col-lg-6">
                    <label class="form-label fw-semibold">Evento</label>
                    <select class="form-select" name="event_id" onchange="this.form.submit()">
                        <option value="">Todos los eventos permitidos</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" @selected((string) $selectedEventId === (string) $event->id)>
                                {{ $event->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-6 text-lg-end">
                    @if($selectedEventId)
                        @php $selectedEvent = $events->firstWhere('id', $selectedEventId); @endphp
                        <span class="badge badge-light-primary fs-7">
                            Evento activo: {{ $selectedEvent?->name ?? 'N/D' }}
                        </span>
                    @else
                        <span class="badge badge-light-info fs-7">Vista agrupada: eventos permitidos</span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="card card-flush h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div class="fs-2hx fw-bold text-gray-900">{{ $total }}</div>
                    <div class="text-muted fw-semibold mt-1">Boletos generados</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card card-flush h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div class="fs-2hx fw-bold text-success">{{ $scanned }}</div>
                    <div class="text-muted fw-semibold mt-1">Boletos escaneados</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card card-flush h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div class="fs-2hx fw-bold text-warning">{{ $courtesyScanned }}</div>
                    <div class="text-muted fw-semibold mt-1">Cortesías escaneadas</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card card-flush h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div class="fs-2hx fw-bold text-danger">{{ $pending }}</div>
                    <div class="text-muted fw-semibold mt-1">Faltan por escanear</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
