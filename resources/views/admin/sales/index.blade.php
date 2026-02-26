@extends('layouts.app')

@section('title', 'Ventas')

@section('content')


    <style>
        :root {
            --bs-primary: #883FFF;
            --bs-primary-active: #6f2ed6;
            --bs-primary-light: #a66bff;

            --bs-primary-rgb: 136, 63, 255;
        }

        /* Badge Primary Global */
        .badge-light-primary {
            background-color: #883FFF !important;
            color: #ffffff !important;
        }

        .btn-light-primary {
            background-color: #883FFF !important;
            border-color: #883FFF !important;
            color: #ffffff !important;
        }

        .btn-light-primary:hover {
            background-color: #6f2ed6 !important;
            border-color: #6f2ed6 !important;
            color: #ffffff !important;
        }
    </style>

    <div class="d-flex flex-column flex-column-fluid">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-gray-800">
                    <i class="ki-outline ki-chart-line fs-2 me-2 text-primary"></i>
                    Módulo de Ventas
                </h1>
                <span class="text-muted fs-7">Listado unificado de tickets e inscripciones</span>
            </div>
        </div>

        <!-- Card -->
        <div class="card card-flush shadow-sm">
            <div class="card-body">

                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_sales">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                            <th>Tipo</th>
                            <th>Evento</th>
                            <th>Comprador</th>
                            <th>Referencia</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($sales as $sale)
                            <tr>

                                <td>
                                    @if($sale['type'] === 'ticket')
                                        <span class="badge badge-light-success">Ticket</span>
                                    @else
                                        <span class="badge badge-light-primary">Registro de Formulario</span>
                                    @endif
                                </td>

                                <td>{{ $sale['event'] ?? '—' }}</td>
                                <td>{{ $sale['email'] }}</td>
                                <td>
                                    <span class="badge badge-light-info">
                                        {{ $sale['reference'] }}
                                    </span>
                                </td>

                                <td>${{ number_format($sale['total'], 2) }}</td>

                                <td>{{ optional($sale['date'])->format('d/m/Y H:i') }}</td>

                                <td>
                                    @if($sale['type'] === 'ticket')
                                        @if($sale['instance']->used_at)
                                            <span class="badge badge-light-danger">Usado</span>
                                        @else
                                            <span class="badge badge-light-success">Válido</span>
                                        @endif
                                    @else
                                        <span class="badge badge-light-primary">Registrado</span>
                                    @endif
                                </td>

                                <td class="text-end" style="width: 250px;">

                                    <button class="btn btn-sm btn-light-info btn-view-sale me-2" data-sale='@json($sale)'>
                                        Ver
                                    </button>

                                    @if($sale['type'] === 'ticket')
                                        <a target="_blank" href="{{ route('admin.ticket_instances.reprint', $sale['instance']) }}"
                                            class="btn btn-sm btn-light-primary">
                                            Reimprimir
                                        </a>
                                    @else
                                        <a target="_blank" href="{{ route('admin.registrations.reprint', $sale['instance']) }}"
                                            class="btn btn-sm btn-light-primary">
                                            Reimprimir
                                        </a>
                                    @endif

                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>


    <!-- ================= MODAL VER ================= -->

    <div class="modal fade" id="saleModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Detalle de venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-5">
                        <strong>Evento:</strong> <span id="modalEvent"></span><br>
                        <strong>Email:</strong> <span id="modalEmail"></span><br>
                        <strong>Referencia:</strong> <span id="modalReference"></span><br>
                        <strong>Total:</strong> <span id="modalTotal"></span>
                    </div>

                    <div id="modalContentDynamic"></div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

@endsection


@push('scripts')
    <script>
        $(document).ready(function () {

            $('#kt_sales').DataTable({
                pageLength: 25,
                ordering: false,
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json"
                }
            });

            const modal = new bootstrap.Modal(document.getElementById('saleModal'));

            $('.btn-view-sale').on('click', function () {

                const sale = $(this).data('sale');

                $('#modalEvent').text(sale.event ?? '—');
                $('#modalEmail').text(sale.email ?? '—');
                $('#modalReference').text(sale.reference ?? '—');
                $('#modalTotal').text('$' + parseFloat(sale.total).toFixed(2));

                let html = '';

                // ==============================
                // TICKET
                // ==============================
                if (sale.type === 'ticket') {

                    html += `
                            <div class="card card-bordered">
                                <div class="card-body">
                                    <h5 class="fw-bold mb-4">Información del Ticket</h5>
                                    <p><strong>Nombre:</strong> ${sale.instance.nombre ?? '—'}</p>
                                    <p><strong>Celular:</strong> ${sale.instance.celular ?? '—'}</p>
                                    <p><strong>Canal de venta:</strong> ${sale.instance.sale_channel ?? '—'}</p>
                                    <p><strong>Método de pago:</strong> ${sale.instance.payment_method ?? '—'}</p>
                                    <p><strong>Fecha compra:</strong> ${sale.instance.purchased_at ?? '—'}</p>
                                </div>
                            </div>
                        `;
                }

                // ==============================
                // INSCRIPCIÓN
                // ==============================
                if (sale.type === 'registration') {

                    const registration = sale.instance.registration;

                    if (registration && registration.form_data) {

                        html += `
                                <div class="card card-bordered">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-4">Datos del Formulario</h5>
                                        <table class="table table-bordered">
                                            <tbody>
                            `;

                        Object.entries(registration.form_data).forEach(([key, value]) => {

                            if (typeof value === 'object') {
                                value = JSON.stringify(value);
                            }

                            html += `
                                    <tr>
                                        <th>${key}</th>
                                        <td>${value ?? '—'}</td>
                                    </tr>
                                `;
                        });

                        html += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;
                    }
                }

                $('#modalContentDynamic').html(html);
                modal.show();
            });

        });
    </script>
@endpush