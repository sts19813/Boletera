document.addEventListener('DOMContentLoaded', function () {


    /**
     * =========================
     * ESTADO DEL CARRITO
     * =========================
     */
    window.cartState = {
        items: []
    };

    function getCartItem(ticketId) {
        return window.cartState.items.find(t => t.id == ticketId);
    }

    window.addToCart = function (ticket) {

        let item = getCartItem(ticket.id);

        if (item) {
            // Solo sumar si es general
            if (ticket.stock > 1 && item.qty < ticket.stock) {
                item.qty++;
            }
            updateCartUI();
            return;
        }

        window.cartState.items.push({
            id: ticket.id,
            name: ticket.name,
            total_price: Number(ticket.total_price),
            stock: ticket.stock ?? 1,
            qty: 1,
            svg_selector: ticket.svg_selector ?? null
        });

        updateCartUI();
    }


    window.updateQty = function (ticketId, delta) {
        const item = getCartItem(ticketId);
        if (!item) return;

        item.qty += delta;

        if (item.qty <= 0) {
            removeFromCart(ticketId);
            return;
        }

        if (item.qty > item.stock) {
            item.qty = item.stock;
        }

        updateCartUI();
    };

    window.removeFromCart = function (ticketId) {
        window.cartState.items =
            window.cartState.items.filter(t => t.id != ticketId);
        updateCartUI();
    };


    /**
     * =========================
     * CLICK EN ASIENTOS
     * =========================
     */
    document.querySelectorAll(selector).forEach(group => {

        if (group.dataset.status !== 'available') return;

        group.addEventListener('click', () => {

            const ticket = window.getTicketFromGroup(group);
            if (!ticket) return;

            // 🎟 Numerado
            if (ticket.stock <= 1) {

                if (getCartItem(ticket.id)) {
                    removeFromCart(ticket.id);
                    paintGroup(group, ticketStatusColors.available);
                } else {
                    addToCart(ticket);
                    paintGroup(group, 'rgba(255, 138, 0,.6)');
                }

                return;
            }

            addToCart(ticket);
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
                    price: t.total_price,
                    qty: t.qty,
                    selectorSVG: t.svg_selector
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
        li.style.alignItems = 'center';
        li.style.marginBottom = '6px';

        let controls = '';

        // 🎫 SOLO si stock > 1
        if (ticket.stock > 1) {
            controls = `
                <div class="cart-qty">
                    <button class="btn-minus" data-id="${ticket.id}">−</button>
                    <strong>${ticket.qty}</strong>
                    <button class="btn-plus" data-id="${ticket.id}">+</button>
                </div>
            `;


        } else {
            controls = `<strong>1</strong>`;
        }

        li.innerHTML = `
            <div style="flex:1;">
                <div>${ticket.name}</div>
                <div class="cart-item-price">
                    $${ticket.total_price.toLocaleString('es-MX')} c/u
                </div>
            </div>

            ${controls}
            <span class="cart-remove">✕</span>
        `;

        // ❌ quitar
        li.querySelector('span:last-child').onclick = () => {
            removeFromCart(ticket.id);

            // solo devolver color a numerados
            if (ticket.stock <= 1) {
                const mapping = window.dbLotes.find(m => m.ticket_id == ticket.id);
                if (mapping) {
                    const el = document.getElementById(mapping.svg_selector);
                    if (el) paintGroup(el, ticketStatusColors.available);
                }
            }
        };

        list.appendChild(li);

        // ➖
        li.querySelector('.btn-minus')?.addEventListener('click', e => {
            e.stopPropagation();
            updateQty(ticket.id, -1);
        });

        // ➕
        li.querySelector('.btn-plus')?.addEventListener('click', e => {
            e.stopPropagation();
            updateQty(ticket.id, 1);
        });


        total += ticket.total_price * ticket.qty;
    });

    totalEl.textContent = `$${total.toLocaleString('es-MX')}`;
    if (panel) {
        panel.style.display = window.cartState.items.length ? 'block' : 'none';
    }
    btn.disabled = window.cartState.items.length === 0;
}

