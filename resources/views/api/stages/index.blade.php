@extends('layouts.app')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold text-gray-800">Etapas</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalStage">
            <i class="ki-duotone ki-plus fs-2"></i> Nueva Etapa
        </button>
    </div>

    <!-- Filtros -->
    <div class="row mb-5">
        <div class="col-md-4">
            <select id="filterProject" class="form-select">
                <option value="">Todos los proyectos</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <select id="filterPhase" class="form-select" disabled>
                <option value="">Selecciona un proyecto...</option>
            </select>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <table id="stagesTable" class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Fase</th>
                        <th>Empresa</th>
                        <th>Creado</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>


    <!-- Modal Crear Etapa -->
    <div class="modal fade" id="modalStage" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-600px">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Etapa</h5>
                    <button class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="formStage">
                        @csrf

                        <!-- Dentro del formulario -->
                        <div class="mb-3">
                            <label class="form-label">Proyecto</label>
                            <select id="projectSelect" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fase</label>
                            <select name="phase_id" id="phaseSelect" class="form-select" required disabled>
                                <option value="">Selecciona primero un Proyecto...</option>
                            </select>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <!--

                                <div class="mb-3">
                                    <label class="form-label">Empresa</label>
                                    <select name="enterprise_id" class="form-select">
                                        <option value="">Sin empresa...</option>
                                        @foreach ($enterprises as $enterprise)
                                            <option value="{{ $enterprise->id }}">
                                                {{ $enterprise->business_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                            -->
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Guardar Etapa</button>
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
            const table = $('#stagesTable').DataTable({
                ajax: { url: '/api/stages', dataSrc: '' },
                columns: [
                    { data: 'id' },
                    { data: 'name' },
                    { data: 'phase.name', defaultContent: 'Sin fase' },
                    { data: 'enterprise.business_name', defaultContent: 'Sin empresa' },
                    { data: 'created_at', render: d => new Date(d).toLocaleDateString() }
                ]
            });

            //  Filtrar fases por proyecto
            $('#filterProject').on('change', function () {
                const projectId = $(this).val();
                const phaseSelect = $('#filterPhase');

                phaseSelect.prop('disabled', true).html('<option>Selecciona un proyecto...</option>');

                if (!projectId) {
                    table.ajax.url('/api/stages').load();
                    return;
                }

                //  Obtener fases del proyecto seleccionado
                $.get(`/api/phases?project_id=${projectId}`, function (phases) {
                    phaseSelect.prop('disabled', false).html('<option value="">Seleccionar...</option>');
                    phases.forEach(p => phaseSelect.append(`<option value="${p.id}">${p.name}</option>`));
                });

                table.ajax.url(`/api/stages?project_id=${projectId}`).load();
            });

            //  Filtrar etapas por fase
            $('#filterPhase').on('change', function () {
                const phaseId = $(this).val();
                const projectId = $('#filterProject').val();
                const params = {};

                if (projectId) params.project_id = projectId;
                if (phaseId) params.phase_id = phaseId;

                const query = new URLSearchParams(params).toString();
                table.ajax.url(`/api/stages?${query}`).load();
            });

            //  Mantener lógica de modal para crear etapa
            $('#projectSelect').on('change', function () {
                let projectId = $(this).val();
                let phaseSelect = $('#phaseSelect');

                if (!projectId) {
                    phaseSelect.prop('disabled', true).html('<option>Selecciona primero un Proyecto...</option>');
                    return;
                }

                $.get(`/api/phases?project_id=${projectId}`, function (phases) {
                    phaseSelect.prop('disabled', false).html('<option value="">Seleccionar...</option>');
                    phases.forEach(p => phaseSelect.append(`<option value="${p.id}">${p.name}</option>`));
                });
            });

            $('#formStage').submit(function (e) {
                e.preventDefault(); // evita que el form haga submit normal

                // Habilitar el select por si estaba disabled
                $('#phaseSelect').prop('disabled', false);

                const data = {
                    phase_id: $('#phaseSelect').val(),
                    name: $(this).find('input[name="name"]').val(),
                    enterprise_id: $(this).find('select[name="enterprise_id"]').val()
                };

                $.ajax({
                    url: '/api/stages', // endpoint de tu API
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
                    data: data,
                    success: function (res) {
                        $('#modalStage').modal('hide');       // cerrar modal
                        $('#stagesTable').DataTable().ajax.reload(); // recargar tabla
                        $('#formStage')[0].reset();           // limpiar formulario
                        $('#phaseSelect').prop('disabled', true).html('<option>Selecciona primero un Proyecto...</option>');
                    },
                    error: function (err) {
                        console.log(err.responseJSON);
                        alert('Ocurrió un error al guardar la etapa.');
                    }
                });
            });

        });

    </script>
@endpush