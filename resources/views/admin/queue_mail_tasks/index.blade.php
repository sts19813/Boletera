@extends('layouts.app')

@section('title', 'Cola de correos')

@section('content')
    <div class="card card-flush mb-5">
        <div class="card-header align-items-center">
            <div>
                <h3 class="card-title mb-1">Cola de correos / PDFs</h3>
                <div class="text-muted fs-7">Monitoreo de envios de boletos e inscripciones.</div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <form method="POST" action="{{ route('admin.queue-mails.mode') }}" class="d-flex align-items-center gap-2">
                    @csrf
                    <label class="text-muted fs-7 mb-0">Modo de envio</label>
                    <select name="delivery_mode" class="form-select form-select-sm w-auto">
                        <option value="queue" @selected($preferredMode === 'queue')>Queue (recomendado)</option>
                        <option value="sync" @selected($preferredMode === 'sync')>Sync inmediato</option>
                    </select>
                    <button class="btn btn-sm btn-light-primary">Guardar</button>
                </form>
                <form method="POST" action="{{ route('admin.queue-mails.run') }}" class="d-flex align-items-center gap-2">
                    @csrf
                    <label class="text-muted fs-7 mb-0">Procesar ahora</label>
                    <select name="max_jobs" class="form-select form-select-sm w-auto">
                        <option value="1">1 tarea</option>
                        <option value="3" selected>3 tareas</option>
                        <option value="5">5 tareas</option>
                        <option value="10">10 tareas</option>
                    </select>
                    <button class="btn btn-sm btn-primary">Ejecutar</button>
                </form>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="alert alert-light-info d-flex flex-column gap-1 mb-4">
                <div class="fw-semibold">
                    Modo preferido: <span class="text-uppercase">{{ $preferredMode }}</span>
                    | Modo efectivo: <span class="text-uppercase">{{ $effectiveMode }}</span>
                </div>
                <div class="fs-7">
                    QUEUE_CONNECTION actual: <code>{{ $queueConnection }}</code>
                    | QUEUE_TICKET_DELIVERY actual: <code>{{ $queueName }}</code>
                </div>
                <div class="fs-7">
                    QUEUE_CONNECTION en env: <code>{{ $queueConnectionEnv ?? '(sin definir)' }}</code>
                    | QUEUE_TICKET_DELIVERY en env: <code>{{ $queueNameEnv ?? '(sin definir)' }}</code>
                </div>
                @if(!$queueConfigValid)
                    <div class="fs-7 text-warning">
                        La configuracion requerida para queue es: QUEUE_CONNECTION=database y QUEUE_TICKET_DELIVERY=ticket-delivery.
                        Mientras no se cumpla, el sistema enviara en modo sync automaticamente.
                    </div>
                @endif
            </div>
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="p-3 border rounded">
                        <div class="text-muted fs-7">Pendientes</div>
                        <div class="fw-bold fs-4">{{ $stats['pending'] }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="p-3 border rounded">
                        <div class="text-muted fs-7">Procesando</div>
                        <div class="fw-bold fs-4">{{ $stats['processing'] }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="p-3 border rounded">
                        <div class="text-muted fs-7">Enviadas</div>
                        <div class="fw-bold fs-4">{{ $stats['sent'] }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="p-3 border rounded">
                        <div class="text-muted fs-7">Fallidas</div>
                        <div class="fw-bold fs-4">{{ $stats['failed'] }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded">
                        <div class="text-muted fs-7">Pendientes en tabla `jobs` ({{ $queueName }})</div>
                        <div class="fw-bold fs-4">{{ $stats['jobs_table_pending'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header align-items-center">
            <h3 class="card-title">Tareas</h3>
            <form method="GET" action="{{ route('admin.queue-mails.index') }}" class="d-flex align-items-center gap-2">
                <select name="status" class="form-select form-select-sm w-auto">
                    <option value="">Todos</option>
                    @foreach(['pending' => 'Pendiente', 'processing' => 'Procesando', 'sent' => 'Enviada', 'failed' => 'Fallida'] as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="search" class="form-control form-control-sm w-250px" value="{{ $search }}"
                    placeholder="Correo / referencia / id">
                <button class="btn btn-sm btn-light-primary">Filtrar</button>
            </form>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Destino</th>
                            <th>Referencia</th>
                            <th>Estatus</th>
                            <th>Intentos</th>
                            <th>Cola</th>
                            <th>Fechas</th>
                            <th>Error</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $task)
                            <tr>
                                <td class="text-muted fs-8">{{ $task->id }}</td>
                                <td>{{ $task->type }}</td>
                                <td>{{ $task->recipient }}</td>
                                <td>{{ $task->reference ?? '-' }}</td>
                                <td>
                                    @php
                                        $badgeClass = match ($task->status) {
                                            'sent' => 'badge-light-success',
                                            'failed' => 'badge-light-danger',
                                            'processing' => 'badge-light-warning',
                                            default => 'badge-light-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ strtoupper($task->status) }}</span>
                                </td>
                                <td>{{ $task->attempts }}</td>
                                <td>{{ $task->queue_name }}</td>
                                <td class="fs-8 text-muted">
                                    <div>Encolada: {{ optional($task->queued_at)->format('d/m/Y H:i:s') ?? '-' }}</div>
                                    <div>Enviada: {{ optional($task->sent_at)->format('d/m/Y H:i:s') ?? '-' }}</div>
                                    <div>Fallida: {{ optional($task->failed_at)->format('d/m/Y H:i:s') ?? '-' }}</div>
                                </td>
                                <td class="fs-8 text-danger" style="max-width: 280px; white-space: normal;">
                                    {{ $task->error_message ? \Illuminate\Support\Str::limit($task->error_message, 180) : '-' }}
                                </td>
                                <td>
                                    @if($task->status === 'failed')
                                        <form method="POST" action="{{ route('admin.queue-mails.retry', $task) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-light-danger">Reintentar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Sin tareas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $tasks->links() }}
            </div>
        </div>
    </div>
@endsection
