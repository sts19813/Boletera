@extends('layouts.app')

@section('title', 'Dashboard | STOM Tickets')

@section('content')

    <div class="row g-5 mb-5">

        <div class="col-xl-3">
            <div class="card card-stats">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Boletos vendidos</span>
                    <div id="card_total" class="fs-2 fw-bold">0</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card card-stats">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Ingresos totales</span>
                    <div id="card_ingresos" class="fs-2 fw-bold text-success">$0.00</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card card-stats">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Ventas hoy</span>
                    <div id="card_hoy" class="fs-2 fw-bold text-primary">0</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card card-stats">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Última venta</span>
                    <div id="card_ultima" class="fs-6 fw-bold">-</div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-5 mb-5">

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Ventas por día</h3>
                </div>
                <div class="card-body">
                    <div id="chartVentas" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Últimas ventas</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Precio</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody id="tablaVentas">
                            <tr>
                                <td colspan="3" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-5 mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title fw-bold">Listado completo de boletos</h3>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Boleto</th>
                                        <th>Email</th>
                                        <th>Método</th>
                                        <th>Referencia</th>
                                        <th>Fecha compra</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaBoletos">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            Cargando boletos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            fetch("{{ route('admin.dashboard.data') }}")
                .then(res => res.json())
                .then(data => {

                    // Cards
                    document.getElementById('card_total').innerText = data.cards.total_boletos;
                    document.getElementById('card_ingresos').innerText = '$' + data.cards.ingresos;
                    document.getElementById('card_hoy').innerText = data.cards.ventas_hoy;
                    document.getElementById('card_ultima').innerText = data.cards.ultima_venta;

                    // Tabla últimas ventas
                    let rows = '';
                    data.ultimas_ventas.forEach(v => {
                        rows += `
                            <tr>
                                <td>${v.email}</td>
                                <td>$${v.precio}</td>
                                <td>${v.fecha}</td>
                            </tr>
                        `;
                    });
                    document.getElementById('tablaVentas').innerHTML = rows;

                    // Chart (amCharts 5)
                    am5.ready(function () {

                        var root = am5.Root.new("chartVentas");
                        root.setThemes([am5themes_Animated.new(root)]);

                        var chart = root.container.children.push(
                            am5xy.XYChart.new(root, {
                                panX: false,
                                panY: false,
                                layout: root.verticalLayout
                            })
                        );

                        var xAxis = chart.xAxes.push(
                            am5xy.CategoryAxis.new(root, {
                                categoryField: "date",
                                renderer: am5xy.AxisRendererX.new(root, {})
                            })
                        );

                        var yAxis = chart.yAxes.push(
                            am5xy.ValueAxis.new(root, {
                                renderer: am5xy.AxisRendererY.new(root, {})
                            })
                        );

                        var series = chart.series.push(
                            am5xy.ColumnSeries.new(root, {
                                name: "Ventas",
                                xAxis: xAxis,
                                yAxis: yAxis,
                                valueYField: "value",
                                categoryXField: "date"
                            })
                        );

                        xAxis.data.setAll(data.chart);
                        series.data.setAll(data.chart);
                    });
                });

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ===============================
            // LISTADO COMPLETO DE BOLETOS
            // ===============================
            fetch("{{ route('admin.dashboard.boletos') }}")
                .then(res => res.json())
                .then(data => {
                    let rows = '';

                    data.forEach(b => {
                        rows += `
                        <tr>
                            <td>${b.id}</td>
                            <td>${b.boleto}</td>
                            <td>${b.email}</td>
                            <td class="text-capitalize">${b.metodo}</td>
                            <td>${b.referencia}</td>
                            <td>${b.fecha}</td>
                        </tr>
                    `;
                    });

                    document.getElementById('tablaBoletos').innerHTML = rows;
                });

        });
    </script>

@endpush