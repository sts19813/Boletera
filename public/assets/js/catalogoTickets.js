$(document).ready(function () {

    // ===============================================================
    //  Inicialización de DataTable: Listado de Tickets
    // ===============================================================
    let ticketsTable = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/api/tickets',
            dataSrc: ''
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'type' },
            { data: 'stage.phase.project.name' },
            { data: 'stage.phase.name' },
            { data: 'stage.name' },
            {
                data: 'total_price',
                render: (data) => `$${parseFloat(data).toLocaleString()}`
            },
            { data: 'stock' },
            { data: 'sold' },
            { data: 'available_from' },
            { data: 'available_until' },
            {
                data: 'is_courtesy',
                render: (data) =>
                    data
                        ? '<span class="badge bg-warning">Cortesía</span>'
                        : '<span class="badge bg-success">Normal</span>'
            },
            {
                data: 'status',
                render: (data) =>
                    data === 'active'
                        ? '<span class="badge bg-primary">Activo</span>'
                        : '<span class="badge bg-secondary">Inactivo</span>'
            },
            { data: 'created_at' }
        ],
        language: {
            url: "/assets/datatables/spanish.json"
        }
    });

    // ===============================================================
    //  Abrir modal para crear Ticket
    // ===============================================================
    $('#btnNewTicket').on('click', function () {
        $('#ticketForm')[0].reset();
        $('#ticketId').val('');
        $('#ticketModalLabel').text('Nuevo Ticket');
        $('#modalTicket').modal('show');
    });


    //cargar stages en el modal
    $('#modalTicket').on('shown.bs.modal', function () {

        const selectStage = $('#ticketStage');
        selectStage.html('<option value="">Cargando...</option>');

        $.get('/api/stages', function (stages) {
            selectStage.html('<option value="">Seleccionar...</option>');
            stages.forEach(s => {
                selectStage.append(`<option value="${s.id}">${s.name}</option>`);
            });
        });
    });

    // ===============================================================
    //  Crear Ticket
    // ===============================================================
    $('#formTicket').on('submit', function (e) {
        e.preventDefault();

        let formData = new FormData(this);

        $.ajax({
            url: '/api/tickets',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                $('#modalTicket').modal('hide');
                ticketsTable.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: 'El ticket fue creado correctamente.'
                });
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Ocurrió un error al crear el ticket.'
                });
            }
        });
    });


    // ===============================================================
    //  Abrir Modal de Edición
    // ===============================================================
    $(document).on('click', '.btnEditTicket', function () {
        let id = $(this).data('id');

        $.ajax({
            url: `/api/tickets/${id}`,
            method: 'GET',
            success: function (ticket) {

                $('#editTicketId').val(ticket.id);
                $('#editTicketName').val(ticket.name);
                $('#editTicketType').val(ticket.type);
                $('#editTicketTotalPrice').val(ticket.total_price);
                $('#editTicketStock').val(ticket.stock);
                $('#editTicketSold').val(ticket.sold);
                $('#editTicketStatus').val(ticket.status);
                $('#editIsCourtesy').val(ticket.is_courtesy);
                $('#editAvailableFrom').val(ticket.available_from);
                $('#editAvailableUntil').val(ticket.available_until);
                $('#editTicketDescription').val(ticket.description);

                // Si necesitas cargar stages también:
                $('#editTicketStage').val(ticket.stage_id);

                $('#modalEditTicket').modal('show');
            }
        });
    });




    // ===============================================================
    //  Eliminar Ticket
    // ===============================================================
    $(document).on('click', '.btnDeleteTicket', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: '¿Eliminar Ticket?',
            text: 'Esta acción no se puede revertir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((res) => {
            if (res.isConfirmed) {
                $.ajax({
                    url: `/api/tickets/${id}`,
                    method: 'DELETE',
                    success: function () {
                        ticketsTable.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: 'El ticket fue eliminado correctamente.'
                        });
                    }
                });
            }
        });
    });


    // ===============================================================
    // Descargar plantilla Excel
    // ===============================================================
    $('#btnDownloadTemplate').on('click', function () {

        const headers = [[
            'id',
            'stage_id',
            'name',
            'type',
            'total_price',
            'stock',
            'sold',
            'available_from',
            'available_until',
            'description',
            'is_courtesy',
            'status'
        ]];

        const ws = XLSX.utils.aoa_to_sheet(headers);
        const wb = XLSX.utils.book_new();

        XLSX.utils.book_append_sheet(wb, ws, 'Tickets');
        XLSX.writeFile(wb, 'tickets_importacion.xlsx');
    });



    // ===============================================================
    // Importar Tickets desde Excel
    // ===============================================================
    let importedFile = null;
    let importedRows = [];
    $('#btnImport').on('click', function () {
        $('#inputImportFile').click();
    });


    $('#inputImportFile').on('change', function (e) {

        importedFile = e.target.files[0];
        if (!importedFile) return;

        const reader = new FileReader();

        reader.onload = function (evt) {
            const workbook = XLSX.read(evt.target.result, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(sheet, { defval: '' });

            importedRows = rows;

            const tbody = $('#previewTable tbody');
            tbody.empty();

            rows.forEach(r => {

                const action = (!r.id || !r.stage_id)
                    ? '<span class="badge bg-success">Nuevo</span>'
                    : '<span class="badge bg-warning">Actualizar</span>';

                tbody.append(`
                <tr>
                    <td>${action}</td>
                    <td>${r.id || '-'}</td>
                    <td>${r.stage_id || '-'}</td>
                    <td>${r.name}</td>
                    <td>$${r.total_price || 0}</td>
                    <td>${r.stock || 0}</td>
                    <td>${r.status}</td>
                </tr>
            `);
            });
            $('#modalPreviewImport').modal('show');
        };

        reader.readAsArrayBuffer(importedFile);
    });



    $('#btnConfirmImport').on('click', function () {

        const formData = new FormData();
        formData.append('file', importedFile);

        Swal.fire({
            title: 'Procesando importación...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: '/api/tickets/import',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                Swal.fire('Éxito', res.message, 'success');
                $('#modalPreviewImport').modal('hide');
                ticketsTable.ajax.reload(null, false);
                $('#inputImportFile').val('');
                importedFile = null;
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.message, 'error');
            }
        });
    });

    $('#btnExportTickets').on('click', function () {

        const data = ticketsTable.rows({ search: 'applied' }).data().toArray();

        if (!data.length) {
            Swal.fire('Sin datos', 'No hay tickets para exportar', 'info');
            return;
        }

        const rows = data.map(t => ({
            id: t.id,
            stage_id: t.stage_id,
            name: t.name,
            type: t.type,
            total_price: t.total_price,
            stock: t.stock,
            sold: t.sold,
            available_from: t.available_from,
            available_until: t.available_until,
            description: t.description,
            is_courtesy: t.is_courtesy ? 1 : 0,
            status: t.status
        }));

        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();

        XLSX.utils.book_append_sheet(wb, ws, 'Tickets');
        XLSX.writeFile(wb, 'tickets_exportados.xlsx');
    });


});
