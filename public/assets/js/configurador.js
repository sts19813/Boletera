document.addEventListener('DOMContentLoaded', function () {

    let selectedPolygonId = null;

    const redirectCheckbox = document.getElementById('redirect');
    const redirectUrlInput = document.getElementById('redirect_url');
    const colorInput = document.getElementById('color');
    const colorActiveInput = document.getElementById('color_active');
    const polygonForm = document.getElementById('polygonForm');
    const lotSelect = document.getElementById('modal_lot_id');

    /**
     * =========================
     * TOGGLE REDIRECCIÓN
     * =========================
     */
    redirectCheckbox.addEventListener('change', function () {
        const enabled = this.checked;
        redirectUrlInput.disabled = !enabled;
        colorInput.disabled = !enabled;
        colorActiveInput.disabled = !enabled;

        if (!enabled) {
            redirectUrlInput.value = '';
            colorInput.value = '#34c759ff';
            colorActiveInput.value = '#2c7be5ff';
        }
    });

    /**
     * =========================
     * MODAL
     * =========================
     */
    const modalEl = document.getElementById('polygonModal');
    const polygonModal = new bootstrap.Modal(modalEl);

    /**
     * =========================
     * CLICK EN SVG (MAPEO)
     * =========================
     */
    // Detectar click sobre cualquier elemento dentro de SVG
    const svgElements = document.querySelectorAll(selector);
    svgElements.forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();

            // Obtener ID del elemento clickeado o del <g> padre
            let elementId = this.id?.trim() || this.closest('g')?.id?.trim() || null;
            if (!elementId) return;

            document.getElementById('selectedElementId').innerText = elementId;
            document.getElementById('polygonId').value = elementId;
            selectedPolygonId= elementId;

            // Limpiar select
            lotSelect.innerHTML = `<option value="">Cargando lotes...</option>`;

        
            lotSelect.innerHTML = `<option value="">Seleccione un lote...</option>`;
            window.preloadedLots.forEach(lot => {

                const opt = document.createElement('option');
                opt.value = lot.id;
                opt.textContent = lot.name;
                lotSelect.appendChild(opt);
            });

            polygonModal.show();
        });
    });

    /**
     * =========================
     * SUBMIT MAPEO
     * =========================
     */
    polygonForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!selectedPolygonId) {
            alert('Selecciona un polígono');
            return;
        }

        if (!lotSelect.value) {
            alert('Selecciona un ticket');
            return;
        }

        const formData = new FormData(this);

        formData.set('polygonId', selectedPolygonId);
        formData.set('desarrollo_id', window.idDesarrollo);
        formData.set('project_id', window.currentLot.project_id ?? '');
        formData.set('phase_id', window.currentLot.phase_id ?? '');
        formData.set('stage_id', window.currentLot.stage_id ?? '');
        formData.set('lot_id', lotSelect.value);

        formData.set('redirect', redirectCheckbox.checked ? 1 : 0);

        if (!redirectCheckbox.checked) {
            formData.set('redirect_url', '');
            formData.set('color', '');
            formData.set('color_active', '');
        }

        fetch(window.Laravel.routes.eventsStore, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': window.Laravel.csrfToken
            }
        })
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    alert(res.message || 'Error al guardar');
                    return;
                }

                polygonModal.hide();
                location.reload();
            })
            .catch(err => {
                console.error(err);
                alert('Error al guardar mapeo');
            });
    });


    // ============================================================
    // MODAL ADMINISTRAR EVENTOS MAPEADOS
    // ============================================================

    $('#btnManageEventMappings').on('click', function () {

        const tbody = $('#mappedEventsTable');
        tbody.empty();

        debugger

        const eventsById = Object.fromEntries(
            window.preloadedLots.map(e => [e.id, e.name])
        );

        if (!window.dbLotes || window.dbLotes.length === 0) {
            tbody.append(`
            <tr>
                <td colspan="3" class="text-center text-muted">
                    No hay eventos mapeados
                </td>
            </tr>
        `);
        } else {
            window.dbLotes.forEach(item => {

                const eventName = eventsById[item.ticket_id] ?? '—';

                tbody.append(`
                <tr>
                    <td><code>${item.svg_selector}</code></td>
                    <td>${eventName}</td>
                    <td>
                        <button 
                            class="btn btn-sm btn-danger btn-delete-event-mapping"
                            data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
            });
        }

        $('#mappedEventsModal').modal('show');
    });


    // Eliminar mapeo
    $('#mappedEventsTable').on('click', '.btn-delete-event-mapping', function () {

        const id = $(this).data('id');

        Swal.fire({
            title: '¿Eliminar mapeo?',
            text: 'El polígono quedará libre nuevamente',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then(result => {

            if (!result.isConfirmed) return;

            $.ajax({
                url: window.Laravel.routes.eventDelete,
                type: 'DELETE',
                data: {
                    id,
                    _token: window.Laravel.csrfToken
                },
                success: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Mapeo eliminado',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => location.reload());
                }
            });
        });
    });
});
