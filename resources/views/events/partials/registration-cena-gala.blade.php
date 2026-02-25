<div id="participants-wrapper">

    <div class="participant-card card shadow-sm mb-8" data-index="0">
        <div class="card-header bg-light">
            <h4 class="card-title fw-bold mb-0">
                Asistente 1
            </h4>
        </div>

        <div class="card-body">

            {{-- Nombre --}}
            <div class="mb-5">
                <label class="form-label required">Nombre completo</label>
                <input type="text" name="participants[0][nombre]" class="form-control form-control-solid" required>
            </div>

            {{-- Email --}}
            <div class="mb-5">
                <label class="form-label required">Correo electrónico</label>
                <input type="email" name="participants[0][email]" class="form-control form-control-solid" required>
            </div>

            {{-- Celular --}}
            <div class="mb-5">
                <label class="form-label required">Celular</label>
                <input type="tel" name="participants[0][celular]" class="form-control form-control-solid phone-input"
                    maxlength="10" required>
            </div>

            {{-- Tipo --}}
            <div class="mb-5 cumbres-group">
                <label class="form-label required">Eres:</label>

                <div class="d-flex gap-6">
                    <label class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input tipo-checkbox" type="checkbox" name="participants[0][tipo][]"
                            value="egresado">
                        <span class="form-check-label">Egresado</span>
                    </label>

                    <label class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input tipo-checkbox" type="checkbox" name="participants[0][tipo][]"
                            value="papa">
                        <span class="form-check-label">Papá del Colegio</span>
                    </label>

                    <label class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input tipo-checkbox" type="checkbox" name="participants[0][tipo][]"
                            value="invitado">
                        <span class="form-check-label">Invitado</span>
                    </label>
                </div>

                <div class="text-danger small mt-2 d-none tipo-error">
                    Debes seleccionar al menos una opción.
                </div>
            </div>

            {{-- Generación (solo egresado) --}}
            <div class="mb-0 generacion-wrapper d-none">
                <label class="form-label required">Generación</label>
                <input type="text" name="participants[0][generacion]"
                    class="form-control form-control-solid generacion-input">
            </div>

        </div>
    </div>

</div>

<div class="text-center">
    <button type="button" id="addParticipant" class="btn btn-light-primary">
        + Agregar Asistente
    </button>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {


        /* ===============================
           CREAR PARTICIPANTE
        =============================== */
        function createParticipantCard() {

            const wrapper = document.getElementById('participants-wrapper');
            const firstCard = document.querySelector('.participant-card');
            if (!wrapper || !firstCard) return;

            const currentCount = document.querySelectorAll('.participant-card').length;

            const newCard = firstCard.cloneNode(true);

            newCard.setAttribute('data-index', currentCount);
            newCard.querySelector('.card-title').innerText = `Asistente ${currentCount + 1}`;

            newCard.querySelectorAll('input, select').forEach(input => {

                let name = input.getAttribute('name');
                name = name.replace(/\[\d+\]/, `[${currentCount}]`);
                input.setAttribute('name', name);

                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });

            const generacionWrapper = newCard.querySelector('.generacion-wrapper');
            const generacionInput = newCard.querySelector('.generacion-input');

            if (generacionWrapper) generacionWrapper.classList.add('d-none');
            if (generacionInput) generacionInput.required = false;
            wrapper.appendChild(newCard);
        }

        /* ===============================
           ELIMINAR ÚLTIMO
        =============================== */
        function removeLastParticipant() {

            const cards = document.querySelectorAll('.participant-card');
            if (cards.length <= 1) return;

            cards[cards.length - 1].remove();
        }

        /* ===============================
           SINCRONIZAR SEGÚN QTY REAL
        =============================== */
        function syncWithCart() {

            const item = window.cartState?.items?.find(t => t.id === 'registration');

            // 🔒 Si el carrito eliminó el item completamente,
            // lo restauramos a 1 automáticamente
            if (!item) {
                window.cartState.items.push({
                    id: 'registration',
                    event_id: window.EVENT_ID,
                    name: window.registrationTicket.name,
                    total_price: Number(window.registrationTicket.total_price),
                    stock: window.registrationConfig?.maxCapacity ?? 9999,
                    qty: 1,
                    svg_selector: null
                });

                updateCartUI();
                return;
            }

            const qty = item.qty;
            const currentForms = document.querySelectorAll('.participant-card').length;

            if (qty > currentForms) {
                for (let i = 0; i < qty - currentForms; i++) {
                    createParticipantCard();
                }
            }

            if (qty < currentForms) {
                for (let i = 0; i < currentForms - qty; i++) {
                    removeLastParticipant();
                }
            }
        }

        document.getElementById('addParticipant')?.addEventListener('click', function () {

            const plusBtn = document.querySelector('.btn-plus[data-id="registration"]');

            if (plusBtn) {
                plusBtn.click(); // simula exactamente el click del +
            }
        });

        /* ===============================
        MOSTRAR GENERACIÓN SI ES EGRESADO
        =============================== */
        document.addEventListener('change', function (e) {

            if (!e.target.classList.contains('tipo-checkbox')) return;

            const card = e.target.closest('.participant-card');
            if (!card) return;

            const generacionWrapper = card.querySelector('.generacion-wrapper');
            const generacionInput = card.querySelector('.generacion-input');

            const egresadoChecked = card.querySelector(
                'input[value="egresado"]'
            )?.checked;

            if (egresadoChecked) {
                generacionWrapper.classList.remove('d-none');
                generacionInput.required = true;
            } else {
                generacionWrapper.classList.add('d-none');
                generacionInput.required = false;
                generacionInput.value = '';
            }
        });

        /* ===============================
           OBSERVAR CAMBIOS DEL CARRITO
        =============================== */
        const cartList = document.getElementById('cartItems');

        const observer = new MutationObserver(function () {
            syncWithCart();
        });

        if (cartList) {
            observer.observe(cartList, { childList: true, subtree: true });
        }

    });
</script>