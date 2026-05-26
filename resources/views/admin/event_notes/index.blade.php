@extends('layouts.app')

@section('title', 'Notas administrativas de eventos')

@section('content')
    @php
        $eventsById = $events->keyBy('id');
        $fieldLabels = [
            'event_id' => 'Evento',
            'category' => 'Clasificacion',
            'title' => 'Titulo',
            'note' => 'Nota',
            'counterparty' => 'Persona/Referencia',
            'amount' => 'Monto',
        ];
    @endphp

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

    <div class="card card-flush">
        <div class="card-header align-items-center gap-3 flex-wrap">
            <div class="card-title">
                <h3 class="fw-bold">Historial de notas administrativas</h3>
            </div>

            <div class="ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNoteModal">
                    Crear nota
                </button>
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
                            <th class="text-end">Acciones</th>
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
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal"
                                        data-bs-target="#editNoteModal{{ $note->id }}">
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.event-notes.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Crear nota</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
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
                                    value="{{ old('counterparty') }}" placeholder="Ejemplo: proveedor / cliente">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar nota</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($notes as $note)
        <div class="modal fade" id="editNoteModal{{ $note->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar nota #{{ $note->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#editTab{{ $note->id }}">Editar nota</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#historyTab{{ $note->id }}">Historial de cambios</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="editTab{{ $note->id }}" role="tabpanel">
                                <form action="{{ route('admin.event-notes.update', $note) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="_edit_note_id" value="{{ $note->id }}">

                                    <div class="row g-5">
                                        <div class="col-md-4">
                                            <label class="form-label required">Evento</label>
                                            <select name="event_id" class="form-select" required>
                                                <option value="">Selecciona evento</option>
                                                @foreach($events as $event)
                                                    <option value="{{ $event->id }}" @selected(old('_edit_note_id') == $note->id ? old('event_id') === $event->id : $note->event_id === $event->id)>
                                                        {{ $event->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label required">Clasificacion</label>
                                            <select name="category" class="form-select" required>
                                                <option value="">Selecciona clasificacion</option>
                                                @foreach($categories as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('_edit_note_id') == $note->id ? old('category') === $value : $note->category === $value)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Monto (opcional)</label>
                                            <input type="number" step="0.01" min="0" max="999999999.99" name="amount"
                                                class="form-control"
                                                value="{{ old('_edit_note_id') == $note->id ? old('amount') : $note->amount }}"
                                                placeholder="Ejemplo: 50000">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label required">Titulo corto</label>
                                            <input type="text" name="title" class="form-control" maxlength="150"
                                                value="{{ old('_edit_note_id') == $note->id ? old('title') : $note->title }}" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Persona o referencia (opcional)</label>
                                            <input type="text" name="counterparty" class="form-control" maxlength="120"
                                                value="{{ old('_edit_note_id') == $note->id ? old('counterparty') : $note->counterparty }}">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label required">Detalle de la nota</label>
                                            <textarea name="note" class="form-control" rows="4" maxlength="5000" required>{{ old('_edit_note_id') == $note->id ? old('note') : $note->note }}</textarea>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Agregar nuevos adjuntos</label>
                                            <input type="file" name="attachments[]" class="form-control" multiple
                                                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
                                            <div class="form-text">Puedes anexar mas comprobantes sin perder los anteriores.</div>
                                        </div>
                                    </div>

                                    <div class="mt-5 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="historyTab{{ $note->id }}" role="tabpanel">
                                @if($note->histories->isEmpty())
                                    <div class="alert alert-light-info mb-0">Aun no hay cambios registrados para esta nota.</div>
                                @else
                                    <div class="d-flex flex-column gap-4">
                                        @foreach($note->histories as $history)
                                            <div class="border rounded p-4">
                                                <div class="d-flex flex-wrap gap-4 mb-3 text-muted fs-7">
                                                    <span><strong>Fecha:</strong> {{ optional($history->created_at)->format('d/m/Y H:i:s') }}</span>
                                                    <span><strong>Usuario:</strong> {{ $history->changedByUser->name ?? '-' }}</span>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-sm table-row-dashed mb-0">
                                                        <thead>
                                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                                <th>Campo</th>
                                                                <th>Valor anterior</th>
                                                                <th>Valor nuevo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach(($history->new_values ?? []) as $field => $newValue)
                                                                @php
                                                                    $oldValue = $history->old_values[$field] ?? null;
                                                                    $label = $fieldLabels[$field] ?? $field;

                                                                    if ($field === 'event_id') {
                                                                        $oldValue = $eventsById[$oldValue]->name ?? $oldValue;
                                                                        $newValue = $eventsById[$newValue]->name ?? $newValue;
                                                                    }

                                                                    if ($field === 'category') {
                                                                        $oldValue = $categories[$oldValue] ?? $oldValue;
                                                                        $newValue = $categories[$newValue] ?? $newValue;
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td>{{ $label }}</td>
                                                                    <td>{{ is_null($oldValue) || $oldValue === '' ? '-' : $oldValue }}</td>
                                                                    <td>{{ is_null($newValue) || $newValue === '' ? '-' : $newValue }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableElement = document.getElementById('kt_admin_event_notes');
            if (tableElement) {
                $('#kt_admin_event_notes').DataTable({
                    pageLength: 25,
                    ordering: true,
                    order: [[0, 'desc']],
                    searching: true,
                    dom: 'frtip',
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
                    }
                });
            }

            const oldEditNoteId = @json(old('_edit_note_id'));
            if (oldEditNoteId) {
                const editModalElement = document.getElementById('editNoteModal' + oldEditNoteId);
                if (editModalElement) {
                    const editModal = new bootstrap.Modal(editModalElement);
                    editModal.show();
                }
            } else if (@json($errors->any())) {
                const createModalElement = document.getElementById('createNoteModal');
                if (createModalElement) {
                    const createModal = new bootstrap.Modal(createModalElement);
                    createModal.show();
                }
            }
        });
    </script>
@endpush
