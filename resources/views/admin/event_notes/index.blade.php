@extends('layouts.app')

@section('title', 'Notas administrativas de eventos')

@section('content')
    <div class="card card-flush mb-6">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold">Nueva nota administrativa</h3>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-2">Revisa los datos capturados:</div>
                    <ul class="mb-0 ps-4">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.event-notes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-5">
                    <div class="col-md-4">
                        <label class="form-label required">Evento</label>
                        <select name="event_id" class="form-select" required>
                            <option value="">Selecciona evento</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" @selected(old('event_id') === $event->id)>{{ $event->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required">Clasificacion</label>
                        <select name="category" class="form-select" required>
                            <option value="">Selecciona clasificacion</option>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Monto (opcional)</label>
                        <input type="number" step="0.01" min="0" max="999999999.99" name="amount" class="form-control"
                            value="{{ old('amount') }}" placeholder="Ejemplo: 50000">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">Titulo corto</label>
                        <input type="text" name="title" class="form-control" maxlength="150" value="{{ old('title') }}"
                            placeholder="Ejemplo: Entrega de efectivo a proveedor" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Persona o referencia (opcional)</label>
                        <input type="text" name="counterparty" class="form-control" maxlength="120"
                            value="{{ old('counterparty') }}" placeholder="Ejemplo: tete / proveedor / cliente">
                    </div>

                    <div class="col-12">
                        <label class="form-label required">Detalle de la nota</label>
                        <textarea name="note" class="form-control" rows="4" maxlength="5000" required
                            placeholder="Ejemplo: Se entregaron 50 mil pesos por concepto de pago del evento Cena Gala Cumbres.">{{ old('note') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Adjuntos (imagenes o documentos)</label>
                        <input type="file" name="attachments[]" class="form-control" multiple
                            accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
                        <div class="form-text">Maximo 8 archivos. Hasta 10MB por archivo.</div>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="btn btn-primary">Guardar nota</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header align-items-center gap-3 flex-wrap">
            <div class="card-title">
                <h3 class="fw-bold">Historial de notas administrativas</h3>
            </div>

            <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
                <input type="text" id="noteSearch" class="form-control form-control-sm w-250px"
                    placeholder="Buscar en notas, titulo, evento, persona...">
                <select id="eventFilter" class="form-select form-select-sm w-200px">
                    <option value="">Todos los eventos</option>
                    @foreach($events as $event)
                        <option value="{{ $event->name }}">{{ $event->name }}</option>
                    @endforeach
                </select>
                <select id="categoryFilter" class="form-select form-select-sm w-180px">
                    <option value="">Todas las clasificaciones</option>
                    @foreach($categories as $label)
                        <option value="{{ $label }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_admin_event_notes">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                            <th>Fecha</th>
                            <th>Evento</th>
                            <th>Clasificacion</th>
                            <th>Titulo / Nota</th>
                            <th>Monto</th>
                            <th>Usuario</th>
                            <th>Adjuntos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notes as $note)
                            @php
                                $categoryLabel = $categories[$note->category] ?? ucfirst($note->category);
                                $badgeClass = match ($note->category) {
                                    'courtesy' => 'badge-light-primary',
                                    'cancellation' => 'badge-light-warning',
                                    'dispute' => 'badge-light-danger',
                                    'payment' => 'badge-light-success',
                                    default => 'badge-light-secondary',
                                };
                            @endphp
                            <tr>
                                <td data-order="{{ optional($note->created_at)->timestamp ?? 0 }}">{{ optional($note->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $note->event->name ?? '-' }}</td>
                                <td><span class="badge {{ $badgeClass }}">{{ $categoryLabel }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $note->title }}</div>
                                    <div class="text-muted fs-7">{{ \Illuminate\Support\Str::limit($note->note, 180) }}</div>
                                    <span class="d-none">{{ $note->note }}</span>
                                    @if($note->counterparty)
                                        <div class="text-muted fs-8 mt-1">Referencia: {{ $note->counterparty }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if(!is_null($note->amount))
                                        ${{ number_format((float) $note->amount, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $note->creator->name ?? '-' }}</td>
                                <td>
                                    @if($note->attachments->isEmpty())
                                        <span class="text-muted">Sin adjuntos</span>
                                    @else
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($note->attachments as $attachment)
                                                <a class="btn btn-sm btn-light-primary"
                                                    href="{{ route('admin.event-notes.attachments.download', ['eventNote' => $note->id, 'attachment' => $attachment->id]) }}"
                                                    target="_blank">
                                                    {{ \Illuminate\Support\Str::limit($attachment->original_name, 24) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableElement = document.getElementById('kt_admin_event_notes');
            if (!tableElement) {
                return;
            }

            const table = $('#kt_admin_event_notes').DataTable({
                pageLength: 25,
                ordering: true,
                order: [[0, 'desc']],
                searching: true,
                dom: 'frtip',
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
                }
            });

            $('#noteSearch').on('keyup', function () {
                table.search(this.value).draw();
            });

            $('#eventFilter').on('change', function () {
                const value = $.fn.dataTable.util.escapeRegex(this.value || '');
                table.column(1).search(value ? '^' + value + '$' : '', true, false).draw();
            });

            $('#categoryFilter').on('change', function () {
                const value = $.fn.dataTable.util.escapeRegex(this.value || '');
                table.column(2).search(value ? '^' + value + '$' : '', true, false).draw();
            });
        });
    </script>
@endpush
