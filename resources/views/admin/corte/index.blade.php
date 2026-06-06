@extends('layouts.app')

@section('title', 'Corte de Ventas')

@section('content')


    <div class="d-flex align-items-center justify-content-between mb-6 flex-wrap gap-3">
        <h3 class="fw-bold mb-0">Corte de Ventas</h3>

        @if(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('finance') || auth()->user()?->can('exportar reportes'))
            <a href="{{ route('admin.corte.export.general', request()->query()) }}" class="btn btn-primary">
                Exportar Corte
            </a>
        @endif
    </div>

    <div class="card card-flush mb-6">
        <div class="card-body">
            <form method="GET" class="row g-4 align-items-end">

                <div class="col-xl-4 col-md-6">
                    <label class="form-label">Eventos</label>
                    <select id="event_ids" name="event_ids[]" class="form-select" multiple>
                        <option value="__all__" @selected(empty($selectedEventIds ?? []))>Todos</option>
                        @foreach(($events ?? collect()) as $event)
                            <option value="{{ $event->id }}" @selected(in_array($event->id, $selectedEventIds ?? [], true))>
                                {{ $event->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="form-label">Desde</label>
                    <input type="datetime-local" name="from" class="form-control" value="{{ request('from') }}">
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="form-label">Hasta</label>
                    <input type="datetime-local" name="to" class="form-control" value="{{ request('to') }}">
                </div>

                <div class="col-xl-2 col-md-6 d-flex gap-2">
                    <button class="btn btn-primary">
                        Filtrar
                    </button>

                    <a href="{{ route('admin.corte.index') }}" class="btn btn-light">
                        Limpiar
                    </a>
                </div>

            </form>
        </div>
    </div>

    <div class="card card-flush">

        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                        <th>Tipo</th>
                        <th>Precio</th>
                        <th>Boletos entregados</th>
                        <th>Cortesías</th>
                        <th>Pagados</th>
                        <th>Web stripe</th>
                        <th>Total Web</th>
                        <th>Cash taquilla</th>
                        <th>Total Cash</th>
                        <th>Clip taquilla</th>
                        <th>Total Clip</th>
                        <th>Total(global)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($corte as $row)
                        <tr>
                            <td>{{ $row['tipo'] }}</td>
                            <td>${{ number_format($row['precio_unitario'], 2) }}</td>
                            <td>{{ $row['vendidos'] }}</td>
                            <td class="text-warning fw-bold">{{ $row['cortesias'] }}</td>
                            <td>{{ $row['pagados'] }}</td>

                            <td>{{ $row['web_qty'] }}</td>
                            <td class="text-info fw-bold">
                                ${{ number_format($row['web_total'], 2) }}
                            </td>


                            <td>{{ $row['cash_qty'] }}</td>
                            <td class="text-success">
                                ${{ number_format($row['cash_total'], 2) }}
                            </td>

                            <td>{{ $row['card_qty'] }}</td>
                            <td class="text-primary">
                                ${{ number_format($row['card_total'], 2) }}
                            </td>

                            <td class="fw-bold">
                                ${{ number_format($row['total_generado'], 2) }}
                            </td>
                        </tr>
                    @endforeach

                    <tr class="fw-bold bg-light">
                        <td>TOTAL</td>
                        <td></td>
                        <td>{{ $totales['vendidos'] }}</td>
                        <td>{{ $totales['cortesias'] }}</td>
                        <td>{{ $totales['pagados'] }}</td>

                        <td>{{ $totales['web_qty'] }}</td>
                        <td>${{ number_format($totales['web_total'], 2) }}</td>

                        <td>{{ $totales['cash_qty'] }}</td>
                        <td>${{ number_format($totales['cash_total'], 2) }}</td>

                        <td>{{ $totales['card_qty'] }}</td>
                        <td>${{ number_format($totales['card_total'], 2) }}</td>

                        <td>${{ number_format($totales['gran_total'], 2) }}</td>
                    </tr>

                </tbody>

            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const $eventIds = $('#event_ids');
            if ($eventIds.length === 0) {
                return;
            }

            $eventIds.select2({
                placeholder: 'Selecciona eventos',
                width: '100%',
            });

            $eventIds.on('change', function () {
                const values = $eventIds.val() || [];
                const hasAll = values.includes('__all__');

                if (!hasAll) {
                    return;
                }

                if (values.length === 1) {
                    return;
                }

                $eventIds.val(['__all__']).trigger('change.select2');
            });
        });
    </script>
@endpush
