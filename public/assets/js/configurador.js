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

});
