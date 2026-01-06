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
                    <small class="text-gray-500">Las cortesias no están consideradas</small>
                </div>
            </div>
        </div>

        <div class="col-xl-2">
            <div class="card card-stats">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Ventas hoy</span>
                    <div id="card_hoy" class="fs-2 fw-bold text-primary">0</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2">
            <div class="card card-stats">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Última venta</span>
                    <div id="card_ultima" class="fs-6 fw-bold">-</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2">
            <div class="card card-stats">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Boletos de cortesía</span>
                    <div id="card_cortesia" class="fs-2 fw-bold text-warning">0</div>
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
  
                    <div class="d-flex gap-2 mb-3">
                        <input type="date" id="from" class="form-control">
                        <input type="date" id="to" class="form-control">
                        <button id="filtrar" class="btn btn-primary">Filtrar</button>
                    </div>

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
    let chartRoot = null;

    // ===============================
    // CARGAR DASHBOARD (INICIAL / FILTRO)
    // ===============================
    function cargarDashboard(from = null, to = null) {

        let url = "{{ route('admin.dashboard.data') }}";
        if (from && to) {
            url += `?from=${from}&to=${to}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {

                // ===============================
                // CARDS
                // ===============================
                document.getElementById('card_total').innerText = data.cards.total_boletos;
                document.getElementById('card_ingresos').innerText = '$' + data.cards.ingresos;
                document.getElementById('card_hoy').innerText = data.cards.ventas_hoy;
                document.getElementById('card_ultima').innerText = data.cards.ultima_venta;
                document.getElementById('card_cortesia').innerText = data.cards.cortesia;

                // ===============================
                // TABLA ÚLTIMAS VENTAS
                // ===============================
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

                // ===============================
                // DESTRUIR CHART PREVIO
                // ===============================
                if (chartRoot) {
                    chartRoot.dispose();
                }

                // ===============================
                // CHART (amCharts 5)
                // ===============================
                am5.ready(function () {

                    chartRoot = am5.Root.new("chartVentas");
                    chartRoot.setThemes([am5themes_Animated.new(chartRoot)]);

                    var chart = chartRoot.container.children.push(
                        am5xy.XYChart.new(chartRoot, {
                            panX: false,
                            panY: false,
                            layout: chartRoot.verticalLayout
                        })
                    );

                    // Cursor
                    var cursor = chart.set("cursor", am5xy.XYCursor.new(chartRoot, {}));
                    cursor.lineY.set("visible", false);

                    // Axis
                    var xAxis = chart.xAxes.push(
                        am5xy.CategoryAxis.new(chartRoot, {
                            categoryField: "date",
                            renderer: am5xy.AxisRendererX.new(chartRoot, {
                                minGridDistance: 30
                            })
                        })
                    );

                    var yAxis = chart.yAxes.push(
                        am5xy.ValueAxis.new(chartRoot, {
                            min: 0,
                            renderer: am5xy.AxisRendererY.new(chartRoot, {})
                        })
                    );

                    xAxis.data.setAll(data.chart);

                    // ===============================
                    // PAGADOS
                    // ===============================
                    var pagados = chart.series.push(
                        am5xy.ColumnSeries.new(chartRoot, {
                            name: "Pagados",
                            xAxis,
                            yAxis,
                            valueYField: "pagados",
                            categoryXField: "date",
                            stacked: true,
                            tooltip: am5.Tooltip.new(chartRoot, {
                                labelText: "[bold]{valueY}[/] pagados"
                            })
                        })
                    );

                    pagados.columns.template.setAll({
                        cornerRadiusTL: 6,
                        cornerRadiusTR: 6,
                        strokeOpacity: 0
                    });

                    // ===============================
                    // CORTESÍA
                    // ===============================
                    var cortesia = chart.series.push(
                        am5xy.ColumnSeries.new(chartRoot, {
                            name: "Cortesía",
                            xAxis,
                            yAxis,
                            valueYField: "cortesia",
                            categoryXField: "date",
                            stacked: true,
                            tooltip: am5.Tooltip.new(chartRoot, {
                                labelText: "[bold]{valueY}[/] cortesía"
                            })
                        })
                    );

                    cortesia.columns.template.setAll({
                        strokeOpacity: 0
                    });

                    // ===============================
                    // COLORES HOY
                    // ===============================
                    pagados.columns.template.adapters.add("fill", (fill, target) => {
                        return target.dataItem.dataContext.isToday
                            ? am5.color(0x2563eb)
                            : fill;
                    });

                    cortesia.columns.template.adapters.add("fill", (fill, target) => {
                        return target.dataItem.dataContext.isToday
                            ? am5.color(0xf59e0b)
                            : fill;
                    });

                    // ===============================
                    // TOTAL ARRIBA
                    // ===============================
                    var totalLabel = chart.series.push(
                        am5xy.LineSeries.new(chartRoot, {
                            xAxis,
                            yAxis,
                            valueYField: "total",
                            categoryXField: "date"
                        })
                    );

                    totalLabel.strokes.template.set("visible", false);

                    totalLabel.bullets.push(() =>
                        am5.Bullet.new(chartRoot, {
                            sprite: am5.Label.new(chartRoot, {
                                text: "{total}",
                                populateText: true, // 👈 CLAVE

                                centerY: am5.p100,
                                centerX: am5.p50,
                                dy: -10,
                                fontWeight: "600"
                            })
                        })
                    );

                    pagados.data.setAll(data.chart);
                    cortesia.data.setAll(data.chart);
                    totalLabel.data.setAll(data.chart);

                    chart.appear(800, 100);
                });
            });
    }

    // ===============================
    // BOTÓN FILTRAR
    // ===============================
    document.getElementById('filtrar').addEventListener('click', () => {
        const from = document.getElementById('from').value;
        const to = document.getElementById('to').value;

        if (!from || !to) {
            alert('Selecciona un rango de fechas');
            return;
        }

        cargarDashboard(from, to);
    });

    // ===============================
    // CARGA INICIAL
    // ===============================
    document.addEventListener('DOMContentLoaded', function () {
        cargarDashboard();

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