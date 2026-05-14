@extends('layouts.app')

@section('title', 'Ventas y Registros')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex flex-wrap align-items-center gap-3">
            <div class="card-title m-0">
                <h3 class="fw-bold mb-0">Ventas y registros</h3>
            </div>

            <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
                <form method="GET" action="{{ route('admin.registrations.index') }}" class="d-flex align-items-center gap-2">
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
                                @foreach($columns as $column)
                                    <th>{{ $column['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    @foreach($columns as $column)
                                        <td>{{ $row[$column['key']] ?? '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
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
        });
    </script>
@endpush
