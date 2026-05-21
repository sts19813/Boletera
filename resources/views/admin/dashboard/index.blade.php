@extends('layouts.app')

@section('title', 'Dashboard | STOM Tickets')

@section('content')
    <div class="card mb-6">
        <div class="card-body">
            <div class="row g-4 align-items-end">
                <div class="col-xl-5 col-lg-6">
                    <label class="form-label">Eventos</label>
                    <select id="event_ids" class="form-select" multiple size="6">
                        <option value="__all__" @selected(empty($selectedEventIds ?? []))>Todos</option>
                        @foreach(($events ?? collect()) as $event)
                            <option value="{{ $event->id }}" @selected(in_array($event->id, $selectedEventIds ?? [], true))>
                                {{ $event->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">Desde</label>
                    <input type="date" id="from" class="form-control" value="{{ request('from') }}">
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">Hasta</label>
                    <input type="date" id="to" class="form-control" value="{{ request('to') }}">
                </div>

                <div class="col-xl-3 col-md-4 d-flex gap-2">
                    <button id="filtrar" class="btn btn-primary w-100">Aplicar filtros</button>
                    <button id="limpiar" class="btn btn-light w-100">Limpiar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 mb-6">
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Ventas totales</span>
                    <div id="card_total_items" class="fs-2 fw-bold">0</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Ingresos totales</span>
                    <div id="card_ingresos_totales" class="fs-2 fw-bold text-success">$0.00</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Ticket promedio</span>
                    <div id="card_ticket_promedio" class="fs-2 fw-bold text-primary">$0.00</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Ventas hoy</span>
                    <div id="card_ventas_hoy" class="fs-2 fw-bold">0</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Tickets</span>
                    <div id="card_tickets" class="fs-2 fw-bold">0</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Inscripciones</span>
                    <div id="card_registros" class="fs-2 fw-bold">0</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Pagadas</span>
                    <div id="card_pagadas" class="fs-2 fw-bold text-success">0</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Cortesías</span>
                    <div id="card_cortesias" class="fs-2 fw-bold text-warning">0</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">% Cortesía</span>
                    <div id="card_pct_cortesia" class="fs-2 fw-bold text-warning">0%</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4">
            <div class="card card-stats h-100">
                <div class="card-body">
                    <span class="text-gray-500 fs-7">Última venta</span>
                    <div id="card_ultima_venta" class="fs-6 fw-bold">-</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 mb-6">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Volumen por día (pagados vs cortesía)</h3>
                </div>
                <div class="card-body">
                    <div id="chartVolume" style="height: 360px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Últimas ventas</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Evento</th>
                                    <th>Tipo</th>
                                    <th>Email</th>
                                    <th>Precio</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody id="tablaVentas">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 mb-6">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Ingresos por día</h3>
                </div>
                <div class="card-body">
                    <div id="chartRevenue" style="height: 340px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Top eventos por ingresos</h3>
                </div>
                <div class="card-body">
                    <div id="chartTopEvents" style="height: 340px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 mb-6">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Mix por método de pago</h3>
                </div>
                <div class="card-body">
                    <div id="chartPayments" style="height: 320px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Mix por canal de venta</h3>
                </div>
                <div class="card-body">
                    <div id="chartChannels" style="height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Ingresos por evento</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Evento</th>
                                    <th class="text-end">Ingresos</th>
                                    <th class="text-end">Pagadas</th>
                                    <th class="text-end">Cortesías</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody id="tablaEventosIngresos">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Listado completo de ventas</h3>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Evento</th>
                                    <th>Tipo</th>
                                    <th>Concepto</th>
                                    <th>Email</th>
                                    <th>Nombre</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                    <th>Vendido por</th>
                                    <th>Precio</th>
                                    <th>Fecha compra</th>
                                </tr>
                            </thead>
                            <tbody id="tablaBoletos">
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        Cargando ventas...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const chartRoots = {};

        const currencyFormatter = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
        });

        const numberFormatter = new Intl.NumberFormat('es-MX');

        function formatCurrency(value) {
            return currencyFormatter.format(Number(value || 0));
        }

        function formatNumber(value) {
            return numberFormatter.format(Number(value || 0));
        }

        function getSelectedEventIds() {
            const selected = Array.from(document.getElementById('event_ids').selectedOptions).map(option => option.value);
            return selected.includes('__all__') ? [] : selected;
        }

        function getFilters() {
            return {
                from: document.getElementById('from').value,
                to: document.getElementById('to').value,
                eventIds: getSelectedEventIds(),
            };
        }

        function buildUrl(baseUrl, filters) {
            const url = new URL(baseUrl, window.location.origin);

            if (filters.from) {
                url.searchParams.set('from', filters.from);
            }

            if (filters.to) {
                url.searchParams.set('to', filters.to);
            }

            filters.eventIds.forEach((id) => {
                url.searchParams.append('event_ids[]', id);
            });

            return url.toString();
        }

        function disposeChart(containerId) {
            if (chartRoots[containerId]) {
                chartRoots[containerId].dispose();
                delete chartRoots[containerId];
            }

            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = '';
            }
        }

        function setEmptyState(containerId, message) {
            disposeChart(containerId);
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = `<div class="d-flex justify-content-center align-items-center text-muted h-100">${message}</div>`;
            }
        }

        function renderVolumeChart(data) {
            const containerId = 'chartVolume';
            if (!Array.isArray(data) || data.length === 0) {
                setEmptyState(containerId, 'Sin datos en el rango seleccionado.');
                return;
            }

            disposeChart(containerId);

            am5.ready(function () {
                const root = am5.Root.new(containerId);
                chartRoots[containerId] = root;
                root.setThemes([am5themes_Animated.new(root)]);

                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    layout: root.verticalLayout,
                }));

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: 'date',
                    renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 40 }),
                }));

                xAxis.get('renderer').labels.template.setAll({
                    rotation: -45,
                    centerY: am5.p50,
                    centerX: am5.p100,
                    paddingTop: 15,
                    fontSize: 11,
                });

                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    min: 0,
                    renderer: am5xy.AxisRendererY.new(root, {}),
                }));

                xAxis.data.setAll(data);

                const paidSeries = chart.series.push(am5xy.ColumnSeries.new(root, {
                    name: 'Pagados',
                    xAxis,
                    yAxis,
                    valueYField: 'pagados',
                    categoryXField: 'date',
                    stacked: true,
                    tooltip: am5.Tooltip.new(root, {
                        labelText: '[bold]{valueY}[/] pagadas',
                    }),
                }));

                const courtesySeries = chart.series.push(am5xy.ColumnSeries.new(root, {
                    name: 'Cortesía',
                    xAxis,
                    yAxis,
                    valueYField: 'cortesia',
                    categoryXField: 'date',
                    stacked: true,
                    tooltip: am5.Tooltip.new(root, {
                        labelText: '[bold]{valueY}[/] cortesía',
                    }),
                }));

                paidSeries.columns.template.setAll({ strokeOpacity: 0 });
                courtesySeries.columns.template.setAll({ strokeOpacity: 0 });

                paidSeries.data.setAll(data);
                courtesySeries.data.setAll(data);

                chart.set('cursor', am5xy.XYCursor.new(root, {}));
                chart.children.push(am5.Legend.new(root, { centerX: am5.p50, x: am5.p50 }));
                chart.appear(800, 100);
            });
        }

        function renderRevenueChart(data) {
            const containerId = 'chartRevenue';
            if (!Array.isArray(data) || data.length === 0) {
                setEmptyState(containerId, 'Sin datos de ingresos para mostrar.');
                return;
            }

            disposeChart(containerId);

            am5.ready(function () {
                const root = am5.Root.new(containerId);
                chartRoots[containerId] = root;
                root.setThemes([am5themes_Animated.new(root)]);

                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                }));

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: 'date',
                    renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 40 }),
                }));

                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {}),
                }));

                xAxis.data.setAll(data);

                const series = chart.series.push(am5xy.LineSeries.new(root, {
                    name: 'Ingresos',
                    xAxis,
                    yAxis,
                    valueYField: 'ingresos',
                    categoryXField: 'date',
                    tooltip: am5.Tooltip.new(root, {
                        labelText: '{categoryX}: [bold]{valueY.formatNumber("#,###.00")}[/] MXN',
                    }),
                }));

                series.strokes.template.setAll({
                    strokeWidth: 3,
                });

                series.fills.template.setAll({
                    fillOpacity: 0.18,
                    visible: true,
                });

                series.data.setAll(data);
                chart.set('cursor', am5xy.XYCursor.new(root, {}));
                chart.appear(800, 100);
            });
        }

        function renderTopEventsChart(data) {
            const containerId = 'chartTopEvents';
            if (!Array.isArray(data) || data.length === 0) {
                setEmptyState(containerId, 'Sin eventos para mostrar.');
                return;
            }

            disposeChart(containerId);

            am5.ready(function () {
                const root = am5.Root.new(containerId);
                chartRoots[containerId] = root;
                root.setThemes([am5themes_Animated.new(root)]);

                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    layout: root.verticalLayout,
                }));

                const yAxis = chart.yAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: 'evento',
                    renderer: am5xy.AxisRendererY.new(root, { minGridDistance: 20 }),
                }));

                const xAxis = chart.xAxes.push(am5xy.ValueAxis.new(root, {
                    min: 0,
                    renderer: am5xy.AxisRendererX.new(root, {}),
                }));

                const series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    xAxis,
                    yAxis,
                    valueXField: 'ingresos',
                    categoryYField: 'evento',
                    tooltip: am5.Tooltip.new(root, {
                        labelText: '{categoryY}: [bold]{valueX.formatNumber("#,###.00")}[/] MXN',
                    }),
                }));

                series.columns.template.setAll({
                    cornerRadiusTR: 6,
                    cornerRadiusBR: 6,
                    strokeOpacity: 0,
                });

                yAxis.data.setAll(data);
                series.data.setAll(data);

                chart.appear(800, 100);
            });
        }

        function renderPieChart(containerId, data, keyField) {
            if (!Array.isArray(data) || data.length === 0) {
                setEmptyState(containerId, 'Sin datos para mostrar.');
                return;
            }

            disposeChart(containerId);

            am5.ready(function () {
                const root = am5.Root.new(containerId);
                chartRoots[containerId] = root;
                root.setThemes([am5themes_Animated.new(root)]);

                const chart = root.container.children.push(am5percent.PieChart.new(root, {
                    layout: root.verticalLayout,
                }));

                const series = chart.series.push(am5percent.PieSeries.new(root, {
                    valueField: keyField,
                    categoryField: 'label',
                }));

                series.labels.template.setAll({
                    oversizedBehavior: 'truncate',
                    maxWidth: 160,
                });

                series.slices.template.setAll({
                    tooltipText: '{category}: [bold]{valuePercentTotal.formatNumber("0.00")}%[/]\n{value.formatNumber("#,###.00")} MXN',
                });

                series.data.setAll(data);
                chart.children.push(am5.Legend.new(root, { centerX: am5.p50, x: am5.p50 }));
                chart.appear(800, 100);
            });
        }

        function renderEventsRevenueTable(rows) {
            const tbody = document.getElementById('tablaEventosIngresos');
            if (!Array.isArray(rows) || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin datos para mostrar.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map((row) => `
                <tr>
                    <td>${row.evento}</td>
                    <td class="text-end fw-bold text-success">${formatCurrency(row.ingresos)}</td>
                    <td class="text-end">${formatNumber(row.pagados)}</td>
                    <td class="text-end text-warning">${formatNumber(row.cortesia)}</td>
                    <td class="text-end">${formatNumber(row.total)}</td>
                </tr>
            `).join('');
        }

        function renderLastSales(rows) {
            const tbody = document.getElementById('tablaVentas');
            if (!Array.isArray(rows) || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin ventas recientes.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map((row) => `
                <tr>
                    <td>${row.evento}</td>
                    <td class="text-capitalize">${row.tipo}</td>
                    <td>${row.email}</td>
                    <td>${formatCurrency(row.precio)}</td>
                    <td>${row.fecha}</td>
                </tr>
            `).join('');
        }

        function renderSalesTable(rows) {
            const tbody = document.getElementById('tablaBoletos');
            if (!Array.isArray(rows) || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">Sin ventas para los filtros seleccionados.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map((row) => {
                const courtesyBadge = row.es_cortesia
                    ? '<span class="badge badge-light-warning ms-1">Cortesía</span>'
                    : '';

                return `
                    <tr>
                        <td>${row.evento}</td>
                        <td class="text-capitalize">${row.tipo}</td>
                        <td>${row.boleto}</td>
                        <td>${row.email}</td>
                        <td>${row.nombre ?? '-'}</td>
                        <td class="text-capitalize">${row.metodo ?? 'N/A'}</td>
                        <td>${row.referencia ?? '-'}</td>
                        <td>${row.user_name ?? 'StomTickets'}</td>
                        <td>${formatCurrency(row.precio)} ${courtesyBadge}</td>
                        <td>${row.fecha}</td>
                    </tr>
                `;
            }).join('');
        }

        function updateCards(cards) {
            document.getElementById('card_total_items').innerText = formatNumber(cards.total_items);
            document.getElementById('card_ingresos_totales').innerText = formatCurrency(cards.ingresos_totales);
            document.getElementById('card_ticket_promedio').innerText = formatCurrency(cards.ticket_promedio);
            document.getElementById('card_ventas_hoy').innerText = formatNumber(cards.ventas_hoy);
            document.getElementById('card_tickets').innerText = formatNumber(cards.tickets);
            document.getElementById('card_registros').innerText = formatNumber(cards.registros);
            document.getElementById('card_pagadas').innerText = formatNumber(cards.pagadas);
            document.getElementById('card_cortesias').innerText = formatNumber(cards.cortesias);
            document.getElementById('card_pct_cortesia').innerText = `${Number(cards.porcentaje_cortesia || 0).toFixed(2)}%`;
            document.getElementById('card_ultima_venta').innerText = cards.ultima_venta || '-';
        }

        async function cargarDashboard() {
            const filters = getFilters();
            const dashboardUrl = buildUrl("{{ route('admin.dashboard.data') }}", filters);
            const boletosUrl = buildUrl("{{ route('admin.dashboard.boletos') }}", filters);

            const [dashboardRes, boletosRes] = await Promise.all([
                fetch(dashboardUrl),
                fetch(boletosUrl),
            ]);

            const dashboardData = await dashboardRes.json();
            const boletosData = await boletosRes.json();

            updateCards(dashboardData.cards || {});
            renderLastSales(dashboardData.ultimas_ventas || []);
            renderEventsRevenueTable(dashboardData.charts?.events_revenue || []);
            renderSalesTable(boletosData || []);

            renderVolumeChart(dashboardData.charts?.volume_by_day || []);
            renderRevenueChart(dashboardData.charts?.revenue_by_day || []);
            renderTopEventsChart(dashboardData.charts?.top_events || []);
            renderPieChart('chartPayments', dashboardData.charts?.payment_methods || [], 'ingresos');
            renderPieChart('chartChannels', dashboardData.charts?.sale_channels || [], 'ingresos');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const $eventIds = $('#event_ids');
            if ($eventIds.length) {
                $eventIds.select2({
                    placeholder: 'Selecciona eventos',
                    width: '100%',
                });

                $eventIds.on('change', function () {
                    const values = $eventIds.val() || [];
                    const hasAll = values.includes('__all__');

                    if (!hasAll || values.length === 1) {
                        return;
                    }

                    $eventIds.val(['__all__']).trigger('change.select2');
                });
            }

            cargarDashboard().catch(() => {
                setEmptyState('chartVolume', 'Error al cargar datos.');
                setEmptyState('chartRevenue', 'Error al cargar datos.');
                setEmptyState('chartTopEvents', 'Error al cargar datos.');
                setEmptyState('chartPayments', 'Error al cargar datos.');
                setEmptyState('chartChannels', 'Error al cargar datos.');
            });

            document.getElementById('filtrar').addEventListener('click', () => {
                cargarDashboard();
            });

            document.getElementById('limpiar').addEventListener('click', () => {
                document.getElementById('from').value = '';
                document.getElementById('to').value = '';
                const eventSelect = document.getElementById('event_ids');
                Array.from(eventSelect.options).forEach((option) => {
                    option.selected = false;
                });
                cargarDashboard();
            });
        });
    </script>
@endpush
