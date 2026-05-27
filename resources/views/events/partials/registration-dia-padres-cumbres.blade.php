<div class="card shadow-sm mb-8">
    <div class="card-header bg-light">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 w-100">
            <h4 class="card-title fw-bold mb-0">Registro directo día padres cumbres</h4>
            <span class="badge badge-light-primary fs-7" style="color:white !important">
                Disponibles: <span id="dia-padres-total-people">0/0</span>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-5">
            <div class="col-md-4">
                <label class="form-label required">Nombre del equipo</label>
                <input type="text" name="team_name" class="form-control form-control-solid" required>
            </div>
            <div class="col-md-4">
                <label class="form-label required">Nombre completo del padre</label>
                <input type="text" name="father_full_name" class="form-control form-control-solid" required>
            </div>
            <div class="col-md-4">
                <label class="form-label required">Correo del padre</label>
                <input type="email" name="father_email" class="form-control form-control-solid"
                    placeholder="correo@ejemplo.com" required>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Hijos</h5>
    <div class="d-flex align-items-center gap-3">
        <span class="badge badge-light-primary fs-7" style="color:white !important">Total personas: <span id="dia-padres-selected-people">2</span></span>
        <button type="button" id="dia-padres-add-child" class="btn btn-primary w-100 fw-semibold">Agregar hijo</button>
    </div>
</div>

<div id="dia-padres-children-wrapper">
    <div class="card shadow-sm mb-5 js-child-card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="card-title fw-bold mb-0 js-child-title">Hijo 1</h6>
            <button type="button" class="btn btn-sm btn-light-danger js-remove-child">Quitar</button>
        </div>
        <div class="card-body">
            <div class="row g-5">
                <div class="col-md-5">
                    <label class="form-label required">Nombre completo</label>
                    <input type="text" name="children[0][full_name]" class="form-control form-control-solid" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label required">Nivel</label>
                    <select name="children[0][school_level]" class="form-select form-select-solid" required>
                        <option value="">Selecciona</option>
                        <option value="primaria">Primaria</option>
                        <option value="secundaria">Secundaria</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label required">Grado</label>
                    <input type="text" name="children[0][grade]" class="form-control form-control-solid"
                        placeholder="Ej. 5to" required>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const diaPadresAvailability = {
        remaining: null,
        total: null,
    };

    window.resolveRegistrationQty = function () {
        const wrapper = document.getElementById('dia-padres-children-wrapper');
        const childrenCount = wrapper ? wrapper.querySelectorAll('.js-child-card').length : 0;
        return Math.max(1, 1 + childrenCount);
    };

    window.syncRegistrationQty = function () {
        const qty = window.resolveRegistrationQty();
        const item = window.cartState?.items?.find((cartItem) => cartItem.id === 'registration');

        if (item) {
            item.qty = qty;
            item.stock = 1;
            if (typeof window.updateCartUI === 'function') {
                window.updateCartUI();
            }
        }

        const selectedPeople = document.getElementById('dia-padres-selected-people');
        if (selectedPeople) {
            selectedPeople.textContent = String(qty);
        }

        const availableCounter = document.getElementById('dia-padres-total-people');
        if (availableCounter) {
            const hasAvailability = Number.isFinite(diaPadresAvailability.remaining) && Number.isFinite(diaPadresAvailability.total);
            if (hasAvailability) {
                const remaining = Number(diaPadresAvailability.remaining);
                const total = Number(diaPadresAvailability.total);
                const projectedRemaining = Math.max(0, remaining - qty);
                availableCounter.textContent = `${projectedRemaining}/${total}`;
            }

            const addChildButton = document.getElementById('dia-padres-add-child');
            if (addChildButton) {
                if (hasAvailability) {
                    const remaining = Number(diaPadresAvailability.remaining);
                    const canAddMore = qty < remaining;
                    addChildButton.disabled = !canAddMore;
                    addChildButton.classList.toggle('disabled', !canAddMore);
                } else {
                    addChildButton.disabled = false;
                    addChildButton.classList.remove('disabled');
                }
            }
        }

        return qty;
    };

    document.addEventListener('DOMContentLoaded', function () {
        const wrapper = document.getElementById('dia-padres-children-wrapper');
        const addButton = document.getElementById('dia-padres-add-child');
        const availabilityUrl = @json(route('registration.direct.availability', $lot->id));

        if (!wrapper || !addButton) {
            return;
        }

        const loadAvailability = async () => {
            try {
                const response = await fetch(availabilityUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                diaPadresAvailability.remaining = Number(payload?.remaining ?? 0);
                diaPadresAvailability.total = Number(payload?.total ?? 0);
                window.syncRegistrationQty();
            } catch (error) {
                // Si falla el endpoint, el formulario sigue funcionando sin bloquear.
            }
        };

        const refreshChildrenIndexes = () => {
            const cards = wrapper.querySelectorAll('.js-child-card');
            cards.forEach((card, index) => {
                const title = card.querySelector('.js-child-title');
                if (title) {
                    title.textContent = `Hijo ${index + 1}`;
                }

                card.querySelectorAll('input, select').forEach((field) => {
                    const name = field.getAttribute('name');
                    if (!name) {
                        return;
                    }

                    field.setAttribute('name', name.replace(/children\[\d+\]/, `children[${index}]`));
                });
            });
        };

        const buildChildCard = (index) => {
            return `
                <div class="card shadow-sm mb-5 js-child-card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="card-title fw-bold mb-0 js-child-title">Hijo ${index + 1}</h6>
                        <button type="button" class="btn btn-sm btn-light-danger js-remove-child">Quitar</button>
                    </div>
                    <div class="card-body">
                        <div class="row g-5">
                            <div class="col-md-5">
                                <label class="form-label required">Nombre completo</label>
                                <input type="text" name="children[${index}][full_name]" class="form-control form-control-solid" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Nivel</label>
                                <select name="children[${index}][school_level]" class="form-select form-select-solid" required>
                                    <option value="">Selecciona</option>
                                    <option value="primaria">Primaria</option>
                                    <option value="secundaria">Secundaria</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Grado</label>
                                <input type="text" name="children[${index}][grade]" class="form-control form-control-solid" placeholder="Ej. 5to" required>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        };

        addButton.addEventListener('click', function () {
            const nextIndex = wrapper.querySelectorAll('.js-child-card').length;
            wrapper.insertAdjacentHTML('beforeend', buildChildCard(nextIndex));
            refreshChildrenIndexes();
            window.syncRegistrationQty();
        });

        wrapper.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.js-remove-child');
            if (!removeButton) {
                return;
            }

            const cards = wrapper.querySelectorAll('.js-child-card');
            if (cards.length <= 1) {
                removeButton.closest('.js-child-card')?.remove();
            } else {
                removeButton.closest('.js-child-card')?.remove();
            }

            refreshChildrenIndexes();
            window.syncRegistrationQty();
        });

        refreshChildrenIndexes();
        window.syncRegistrationQty();
        loadAvailability();
    });
</script>
