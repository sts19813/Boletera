document.addEventListener('DOMContentLoaded', () => {

    // ======================
    // SEAT MAP
    // ======================
    const seatMap = document.getElementById('seatMap');
    if (seatMap && window.Panzoom) {

        const panzoom = Panzoom(seatMap, {
            maxScale: 4,
            minScale: 1,
            step: 0.3,
            contain: 'outside',
            cursor: 'grab'
        });

        seatMap.parentElement?.addEventListener('wheel', panzoom.zoomWithWheel);

        seatMap.addEventListener('pointerdown', (e) => {
            if (e.target.closest('path, rect, polygon, circle')) {
                e.stopPropagation();
            }
        });

        document.getElementById('zoomIn')
            ?.addEventListener('click', () => panzoom.zoomIn());

        document.getElementById('zoomOut')
            ?.addEventListener('click', () => panzoom.zoomOut());
    }

    // ======================
    // BOTONES DE PAGO
    // ======================
    document.querySelectorAll('.btn-metodo').forEach(btn => {
        btn.addEventListener('click', () => {

            const metodoPago = btn.dataset.metodo;
            const nombreInput = document.getElementById('ventaNombre')?.value.trim();
            const esCortesia = metodoPago === 'cortesia';

            let email = 'taquilla@local';
            if (nombreInput) email = nombreInput;
            if (esCortesia) email = 'CORTESIA';

            let registrationData = null;

            if (window.isRegistration) {
                if (!validateRegistrationForm()) return;

                const form = document.getElementById('registrationForm');
                registrationData = formDataToObject(form);
            }

            fetch('/taquilla/sell', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.Laravel.csrfToken
                },
                body: JSON.stringify({
                    cart: window.cartState.items.map(t => ({
                        id: t.id,
                        event_id: t.event_id,
                        name: t.name,
                        price: t.total_price,
                        qty: t.qty,
                        type: t.id === 'registration' ? 'registration' : 'ticket'
                    })),
                    email: email,
                    event_id: window.EVENT_ID,
                    payment_method: metodoPago,
                    registration: registrationData,
                })
            })
                .then(res => res.text())
                .then(html => {
                    document.open();
                    document.write(html);
                    document.close();
                })
                .catch(() => alert('Error en venta de taquilla'));
        });
    });
});
