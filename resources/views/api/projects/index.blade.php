@extends('layouts.app')

@section('content')

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-5">
    <h1 class="fw-bold text-gray-800">Proyectos</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProject">
        <i class="ki-duotone ki-plus fs-2"></i> Nuevo Proyecto
    </button>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-body">
        <table id="projectsTable" class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Usuario</th>
                    <th>Creado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>


<!-- Modal Crear Proyecto -->
<div class="modal fade" id="modalProject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Proyecto</h5>
                <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="formProject">
                    @csrf
                    <div class="mb-3">
                       <select name="user_id" id="userSelect" class="form-select" required>
                            <option value="">Selecciona un usuario</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre del Desarrollo/Proyecto </label>
                        <input type="text" name="name" class="form-control" placeholder="Nombre del proyecto" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="phone" class="form-control" placeholder="9991234567">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Logo (URL)</label>
                        <input type="text" name="logo" class="form-control" placeholder="https://example.com/logo.png">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">¿Cotización habilitada?</label>
                        <select name="quotation" class="form-select">
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </select>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            Guardar
                        </button>
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

    $('#userSelect').select2({
        placeholder: 'Selecciona un usuario',
        width: '100%'
    });
    const table = $('#projectsTable').DataTable({
        ajax: {
            url: '/api/projects',
            dataSrc: ''
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'email' },
            { data: 'phone' },
            { data: 'user_id' },
            { data: 'created_at', render: function (data) {
                return new Date(data).toLocaleDateString();
            }},
            {
                data: null,
                className: 'text-end',
                render: function (data) {
                    return `
                        <button class="btn btn-sm btn-light-primary view-btn" data-id="${data.id}">
                            <i class="ki-duotone ki-eye fs-5"></i> Ver
                        </button>
                    `;
                }
            }
        ]
    });

    // Guardar nuevo proyecto
    $('#formProject').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '/api/projects',
            method: 'POST',
            data: $(this).serialize(),
            success: function () {
                $('#modalProject').modal('hide');
                table.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    title: 'Proyecto creado',
                    text: 'El proyecto se ha guardado correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#formProject')[0].reset();
            },
            error: function (xhr) {
                console.error(xhr.responseJSON);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.message || 'No se pudo crear el proyecto.'
                });
            }
        });
    });
});
</script>
@endpush
