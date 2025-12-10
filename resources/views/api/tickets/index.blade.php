@extends('layouts.app')

@section('content')

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold text-gray-800">Tickets</h1>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTicket">
                <i class="ki-duotone ki-plus fs-2"></i> Nuevo Ticket
            </button>

            <button id="btnDownloadTemplate" class="btn btn-success">
                <i class="ki-duotone ki-download fs-2"></i> Descargar Plantilla
            </button>

            <input type="file" id="inputImport" accept=".xlsx" hidden>
            <button id="btnImport" class="btn btn-info">
                <i class="ki-duotone ki-upload fs-2"></i> Importar Tickets
            </button>
        </div>
    </div>

    <!-- Filtros superiores -->
    <div class="row mb-5">
        <div class="col-md-4">
            <select id="filterProject" class="form-select">
                <option value="">Todos los proyectos</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <select id="filterPhase" class="form-select" disabled>
                <option value="">Selecciona un proyecto...</option>
            </select>
        </div>
        <div class="col-md-4">
            <select id="filterStage" class="form-select" disabled>
                <option value="">Selecciona una fase...</option>
            </select>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="card-body">
            <table id="ticketsTable" class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Proyecto</th>
                        <th>Fase</th>
                        <th>Etapa</th>
                        <th>Precio Total</th>
                        <th>Stock</th>
                        <th>Vendidos</th>
                        <th>Disponible Desde</th>
                        <th>Disponible Hasta</th>
                        <th>Cortesía</th>
                        <th>Status</th>
                        <th>Creado</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal Editar Ticket -->
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
                            <input type="datetime-local" id="editAvailableUntil" name="available_until" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea id="editTicketDescription" name="description" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cortesía</label>
                            <select id="editIsCourtesy" name="is_courtesy" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select id="editTicketStatus" name="status" class="form-select">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
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

    <!-- Modal Crear Ticket -->
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
                            <label class="form-label">Etapa</label>
                            <select id="ticketStage" name="stage_id" class="form-select" required>
                                <option value="">Selecciona una etapa...</option>
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
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cortesía</label>
                            <select name="is_courtesy" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
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
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="{{ asset('assets/js/catalogoTickets.js') }}"></script>
@endpush
