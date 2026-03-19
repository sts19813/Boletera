@extends('layouts.app')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold text-gray-800">Tickets</h1>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTicket">
                <i class="ki-duotone ki-plus fs-2"></i> Nuevo Ticket
            </button>

            <button id="btnDownloadTemplate" class="btn btn-success">
                <i class="ki-duotone ki-download fs-2"></i> Descargar Plantilla
            </button>

            <button id="btnExportTickets" class="btn btn-warning">
                Exportar Tickets
            </button>
            <input type="file" id="inputImportFile" accept=".xlsx" hidden>

            <button id="btnImport" class="btn btn-info">
                Importar Tickets
            </button>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-6">
            <select id="filterEvent" class="form-select">
                <option value="">Todos los eventos</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="ticketsTable" class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th>ID</th>
                        <th>Evento</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Precio Total</th>
                        <th>Stock</th>
                        <th>Vendidos</th>
                        <th>Disponible Desde</th>
                        <th>Disponible Hasta</th>
                        <th>Cortesia</th>
                        <th>Status</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalEditTicket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-600px">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Ticket</h5>
                    <button class="btn btn-sm btn-icon" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="formEditTicket">
                        @csrf

                        <input type="hidden" id="editTicketId" name="id">

                        <div class="mb-3">
                            <label class="form-label">Evento</label>
                            <select id="editTicketEvent" name="event_id" class="form-select" required>
                                <option value="">Selecciona un evento...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input id="editTicketName" name="name" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <input id="editTicketType" name="type" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Precio Total</label>
                            <input id="editTicketTotalPrice" name="total_price" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input id="editTicketStock" name="stock" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vendidos</label>
                            <input id="editTicketSold" name="sold" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Disponible Desde</label>
                            <input type="datetime-local" id="editAvailableFrom" name="available_from" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Disponible Hasta</label>
                            <input type="datetime-local" id="editAvailableUntil" name="available_until"
                                class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea id="editTicketDescription" name="description" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cortesia</label>
                            <select id="editIsCourtesy" name="is_courtesy" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Si</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select id="editTicketStatus" name="status" class="form-select">
                                <option value="available">Disponible</option>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="sold">Vendido</option>
                                <option value="sold_out">Agotado</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <button class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTicket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-600px">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Ticket</h5>
                    <button class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="formTicket">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Evento</label>
                            <select id="ticketEvent" name="event_id" class="form-select" required>
                                <option value="">Selecciona un evento...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <input type="text" name="type" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Precio Total</label>
                            <input type="number" step="0.01" name="total_price" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vendidos</label>
                            <input type="number" name="sold" class="form-control" value="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Disponible Desde</label>
                            <input type="datetime-local" name="available_from" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Disponible Hasta</label>
                            <input type="datetime-local" name="available_until" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cortesia</label>
                            <select name="is_courtesy" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Si</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="available">Disponible</option>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="sold">Vendido</option>
                                <option value="sold_out">Agotado</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Guardar Ticket</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPreviewImport" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Vista previa de importacion</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <table class="table table-bordered" id="previewTable">
                        <thead>
                            <tr>
                                <th>Accion</th>
                                <th>ID</th>
                                <th>Evento</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button id="btnConfirmImport" class="btn btn-primary">
                        Confirmar Importacion
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.ticketEvents = @json($events);
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="{{ asset('assets/js/catalogoTickets.js') }}"></script>
@endpush
