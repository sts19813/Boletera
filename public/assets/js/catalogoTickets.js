$(document).ready(function () {

    const events = Array.isArray(window.ticketEvents) ? window.ticketEvents : [];

    function fillEventSelect($select, selected = '') {
        $select.html('<option value="">Selecciona un evento...</option>');

        events.forEach(event => {
            const selectedAttr = selected && selected === event.id ? 'selected' : '';
            $select.append(`<option value="${event.id}" ${selectedAttr}>${event.name}</option>`);
        });
    }

    fillEventSelect($('#ticketEvent'));
    fillEventSelect($('#editTicketEvent'));

    let ticketsTable = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/api/tickets',
            data: function (d) {
                const eventId = $('#filterEvent').val();
                if (eventId) d.event_id = eventId;
            },
            dataSrc: ''
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'id' },
            {
                data: 'event.name',
                defaultContent: '—'
            },
            { data: 'name' },
            { data: 'type' },
            {
                data: 'total_price',
                render: (data) => `$${parseFloat(data || 0).toLocaleString()}`
            },
            { data: 'stock' },
            { data: 'sold' },
            { data: 'available_from' },
            { data: 'available_until' },
            {
                data: 'is_courtesy',
                render: (data) =>
                    data
                        ? '<span class="badge bg-warning">Cortesia</span>'
                        : '<span class="badge bg-success">Normal</span>'
            },
            {
                data: 'status',
                render: (data) => {
                    const status = (data || '').toLowerCase();

                    if (status === 'available' || status === 'active') {
                        return '<span class="badge bg-primary">Activo</span>';
                    }

                    if (status === 'sold' || status === 'sold_out') {
                        return '<span class="badge bg-danger">Agotado</span>';
                    }

                    return '<span class="badge bg-secondary">Inactivo</span>';
                }
            },
            { data: 'created_at' },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (row) => `
                    <button class="btn btn-sm btn-light-primary btnEditTicket" data-id="${row.id}">Editar</button>
                    <button class="btn btn-sm btn-light-danger btnDeleteTicket" data-id="${row.id}">Eliminar</button>
                `
            }
        ],
        language: {
            url: '/assets/datatables/spanish.json'
        }
    });

    $('#filterEvent').on('change', function () {
        ticketsTable.ajax.reload();
    });

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
                $('#formTicket')[0].reset();

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
                    text: xhr.responseJSON?.message || 'Ocurrio un error al crear el ticket.'
                });
            }
        });
    });

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
                $('#editIsCourtesy').val(ticket.is_courtesy ? 1 : 0);
                $('#editAvailableFrom').val(ticket.available_from);
                $('#editAvailableUntil').val(ticket.available_until);
                $('#editTicketDescription').val(ticket.description);

                fillEventSelect($('#editTicketEvent'), ticket.event_id);
                $('#modalEditTicket').modal('show');
            }
        });
    });

    $('#formEditTicket').on('submit', function (e) {
        e.preventDefault();

        const id = $('#editTicketId').val();
        const payload = $(this).serialize();

        $.ajax({
            url: `/api/tickets/${id}`,
            method: 'PUT',
            data: payload,
            success: function () {
                $('#modalEditTicket').modal('hide');
                ticketsTable.ajax.reload(null, false);
                Swal.fire('Actualizado', 'El ticket fue actualizado correctamente.', 'success');
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'No se pudo actualizar el ticket.', 'error');
            }
        });
    });

    $(document).on('click', '.btnDeleteTicket', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: '¿Eliminar Ticket?',
            text: 'Esta accion no se puede revertir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((res) => {
            if (!res.isConfirmed) return;

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
        });
    });

    $('#btnDownloadTemplate').on('click', function () {
        const headers = [[
            'id',
            'event_id',
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

    let importedFile = null;

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

            const tbody = $('#previewTable tbody');
            tbody.empty();

            rows.forEach(r => {
                const action = (!r.id)
                    ? '<span class="badge bg-success">Nuevo</span>'
                    : '<span class="badge bg-warning">Actualizar</span>';

                tbody.append(`
                <tr>
                    <td>${action}</td>
                    <td>${r.id || '-'}</td>
                    <td>${r.event_id || '-'}</td>
                    <td>${r.name || '-'}</td>
                    <td>$${r.total_price || 0}</td>
                    <td>${r.stock || 0}</td>
                    <td>${r.status || '-'}</td>
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
            title: 'Procesando importacion...',
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
                Swal.fire('Exito', res.message, 'success');
                $('#modalPreviewImport').modal('hide');
                ticketsTable.ajax.reload(null, false);
                $('#inputImportFile').val('');
                importedFile = null;
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'No se pudo importar.', 'error');
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
            event_id: t.event_id,
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
