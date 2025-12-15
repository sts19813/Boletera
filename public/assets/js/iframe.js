document.addEventListener('DOMContentLoaded', function () {


    /**
     * =========================
     * ESTADO DEL CARRITO
     * =========================
     */
    window.cartState = {
        items: []
    };

    function isInCart(ticketId) {
        return window.cartState.items.some(t => t.id == ticketId);
    }

    function addToCart(ticket) {
        if (isInCart(ticket.id)) return;
        window.cartState.items.push(ticket);
        updateCartUI();
    }

    function removeFromCart(ticketId) {
        window.cartState.items =
            window.cartState.items.filter(t => t.id != ticketId);
        updateCartUI();
    }

    /**
     * =========================
     * CLICK EN ASIENTOS
     * =========================
     */
    document.querySelectorAll('svg g').forEach(group => {

        if (group.dataset.status !== 'available') return;

        group.addEventListener('click', () => {

            const ticket = window.getTicketFromGroup(group);
            if (!ticket) return;

            if (isInCart(ticket.id)) {
                removeFromCart(ticket.id);
                paintGroup(group, ticketStatusColors.available);
            } else {
                addToCart(ticket);
                paintGroup(group, 'rgba(0,120,255,.6)');
            }
        });
    });

    /**
     * =========================
     * CHECKOUT STRIPE
     * =========================
     */
    document.getElementById('btnCheckout')?.addEventListener('click', () => {

        if (!window.cartState.items.length) {
            alert('Carrito vacío');
            return;
        }

        fetch('/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.Laravel.csrfToken
            },
            body: JSON.stringify({
                cart: window.cartState.items.map(t => ({
                    id: t.id,
                    name: t.name,
                    price: Number(t.total_price),
                    qty: 1,
                    selectorSVG: t.svg_selector ?? null
                }))
            })
        })
            .then(res => res.json())
            .then(() => {
                // 👉 aquí ya NO es Stripe
                window.location.href = '/pago';
            })
            .catch(err => {
                console.error(err);
                alert('Error al preparar el pago');
            });
    });

});

/**
 * =========================
 * UI DEL CARRITO
 * =========================
 */
function updateCartUI() {

    const panel = document.getElementById('cartPanel');
    const list = document.getElementById('cartItems');
    const totalEl = document.getElementById('cartTotal');
    const btn = document.getElementById('btnCheckout');

    list.innerHTML = '';
    let total = 0;

    window.cartState.items.forEach(ticket => {

        const li = document.createElement('li');
        li.style.display = 'flex';
        li.style.justifyContent = 'space-between';

        li.innerHTML = `
            <span>${ticket.name}</span>
            <span style="cursor:pointer">✕</span>
        `;

        li.querySelector('span:last-child').onclick = () => {
            removeFromCart(ticket.id);

            const mapping = window.dbLotes.find(m => m.ticket_id == ticket.id);
            if (mapping) {
                const el = document.getElementById(mapping.svg_selector);
                if (el) paintGroup(el, ticketStatusColors.available);
            }
        };

        list.appendChild(li);
        total += Number(ticket.total_price || 0);
    });

    totalEl.textContent = `$${total.toLocaleString('es-MX')}`;
    panel.style.display = window.cartState.items.length ? 'block' : 'none';
    btn.disabled = window.cartState.items.length === 0;
}
