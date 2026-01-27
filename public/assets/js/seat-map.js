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

            fetch('/taquilla/sell', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.Laravel.csrfToken
                },
                body: JSON.stringify({
                    cart: window.cartState.items,
                    cortesia: esCortesia,
                    email,
                    payment_method: metodoPago
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
