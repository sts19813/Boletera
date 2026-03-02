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
                                        <th>Evento</th>
                                        <th>Boleto</th>
                                        <th>Email</th>
                                        <th>Nombre</th>
                                        <th>Método</th>
                                        <th>Referencia</th>
                                        <th>vendido por</th>
                                        <th>Precio</th>
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
                        cursor.lineY.set("visible", true);

                        // ===============================
                        // EJE X
                        // ===============================
                        var xRenderer = am5xy.AxisRendererX.new(chartRoot, {
                            minGridDistance: 60
                        });

                        var xAxis = chart.xAxes.push(
                            am5xy.CategoryAxis.new(chartRoot, {
                                categoryField: "date",
                                renderer: xRenderer
                            })
                        );

                        xRenderer.labels.template.setAll({
                            rotation: -45,
                            centerY: am5.p50,
                            centerX: am5.p100,
                            paddingTop: 15,
                            oversizedBehavior: "truncate",
                            fill: am5.color(0x374151),
                            fontSize: 12
                        });

                        // ===============================
                        // EJE Y
                        // ===============================
                        var yAxis = chart.yAxes.push(
                            am5xy.ValueAxis.new(chartRoot, {
                                min: 0,
                                renderer: am5xy.AxisRendererY.new(chartRoot, {})
                            })
                        );

                        yAxis.get("renderer").labels.template.setAll({
                            fill: am5.color(0x374151),
                            fontSize: 12
                        });

                        xAxis.get("renderer").grid.template.setAll({
                            stroke: am5.color(0xe5e7eb),
                            strokeOpacity: 0.9
                        });

                        yAxis.get("renderer").grid.template.setAll({
                            stroke: am5.color(0xe5e7eb),
                            strokeOpacity: 0.9
                        });

                        xAxis.data.setAll(data.chart);

                        // ===============================
                        // SERIE PAGADOS
                        // ===============================
                        var pagados = chart.series.push(
                            am5xy.ColumnSeries.new(chartRoot, {
                                name: "Pagados",
                                xAxis: xAxis,
                                yAxis: yAxis,
                                valueYField: "pagados",
                                categoryXField: "date",
                                stacked: true,
                                tooltip: am5.Tooltip.new(chartRoot, {
                                    labelText: "[bold]{valueY}[/] pagados"
                                })
                            })
                        );

                        pagados.columns.template.setAll({
                            strokeOpacity: 0
                        });

                        // Redondeado dinámico pagados
                        pagados.columns.template.adapters.add("cornerRadiusTL", function (radius, target) {
                            return target.dataItem.dataContext.cortesia > 0 ? 0 : 8;
                        });

                        pagados.columns.template.adapters.add("cornerRadiusTR", function (radius, target) {
                            return target.dataItem.dataContext.cortesia > 0 ? 0 : 8;
                        });

                        // ===============================
                        // SERIE CORTESÍA
                        // ===============================
                        var cortesia = chart.series.push(
                            am5xy.ColumnSeries.new(chartRoot, {
                                name: "Cortesía",
                                xAxis: xAxis,
                                yAxis: yAxis,
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

                        // Redondeado dinámico cortesía
                        cortesia.columns.template.adapters.add("cornerRadiusTL", function (radius, target) {
                            return target.dataItem.dataContext.cortesia > 0 ? 8 : 0;
                        });

                        cortesia.columns.template.adapters.add("cornerRadiusTR", function (radius, target) {
                            return target.dataItem.dataContext.cortesia > 0 ? 8 : 0;
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
                                xAxis: xAxis,
                                yAxis: yAxis,
                                valueYField: "total",
                                categoryXField: "date"
                            })
                        );

                        totalLabel.strokes.template.set("visible", false);

                        totalLabel.bullets.push(function () {
                            return am5.Bullet.new(chartRoot, {
                                sprite: am5.Label.new(chartRoot, {
                                    text: "{total}",
                                    populateText: true,
                                    fill: am5.color(0xffffff),
                                    fontWeight: "700",
                                    centerY: am5.p100,
                                    centerX: am5.p50,
                                    dy: -12
                                })
                            });
                        });

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

            fetch("{{ route('admin.dashboard.boletos') }}")
                .then(res => res.json())
                .then(data => {
                    let rows = '';
                    data.forEach(b => {
                        rows += `
                        <tr>
                            <td>${b.evento}</td>
                            <td>${b.boleto}</td>
                            <td>${b.email}</td>
                            <td>${b.nombre}</td>
                            <td class="text-capitalize">${b.metodo}</td>
                            <td>${b.referencia}</td>
                            <td>${b.user_name}</td>
                            <td>${b.precio}</td>
                            <td>${b.fecha}</td>
                        </tr>
                    `;
                    });
                    document.getElementById('tablaBoletos').innerHTML = rows;
                });
        });
    </script>
@endpush