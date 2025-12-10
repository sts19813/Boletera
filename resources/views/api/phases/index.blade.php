@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-5">
    <h1 class="fw-bold text-gray-800">Fases</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPhase">
        <i class="ki-duotone ki-plus fs-2"></i> Nueva Fase
    </button>
</div>

<div class="row mb-5">
    <div class="col-md-4">
        <select id="filterProject" class="form-select">
            <option value="">Todos los proyectos</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <table id="phasesTable" class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Proyecto</th>
                    <th>Fecha Inicio</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>


<!-- Modal Crear Fase -->
<div class="modal fade" id="modalPhase" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Fase</h5>
                <button class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="formPhase">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Proyecto</label>
                        <select name="project_id" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Guardar Fase</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const table = $('#phasesTable').DataTable({
        ajax: {
            url: '/api/phases',
            dataSrc: ''
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'project.name', defaultContent: '-' },
            { data: 'start_date', defaultContent: '-' },
            {
                data: 'created_at',
                render: function (data) {
                    return new Date(data).toLocaleDateString();
                }
            }
        ]
    });

    //  Filtrar por proyecto
    $('#filterProject').on('change', function () {
        const projectId = $(this).val();
        let url = '/api/phases';
        if (projectId) {
            url += `?project_id=${projectId}`;
        }
        table.ajax.url(url).load();
    });

    //  Guardar fase
    $('#formPhase').on('submit', function(e) {
        e.preventDefault();
        $.post('/api/phases', $(this).serialize())
            .done((response) => {
                $('#modalPhase').modal('hide');
                table.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    title: 'Fase creada',
                    timer: 2000,
                    showConfirmButton: false
                });
                $(this)[0].reset();
            })
            .fail((xhr) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.message
                });
            });
    });
});
</script>
@endpush
