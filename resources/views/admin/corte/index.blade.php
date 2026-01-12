@extends('layouts.app')

@section('title', 'Corte de Ventas')

@section('content')
<div class="card card-flush">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <h3 class="fw-bold">Corte de Ventas</h3>
        </div>

       <div class="card-toolbar">
            <a href="{{ route('admin.corte.export.general') }}"
            class="btn btn-primary">
                Exportar Corte
            </a>
        </div>

    </div>

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
                    <th>Card taquilla</th>
                    <th>Total Card</th>
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
