document.addEventListener('DOMContentLoaded', function () {
    /**
     * =========================
     * ESTADO DEL CARRITO
     * =========================
     */
    window.cartState = {
        items: []
    };


    if (window.isRegistration && window.registrationTicket) {

        let stock = 1;

        if (window.registrationConfig) {

            // 🎯 Si permite múltiples
            if (window.registrationConfig.allowsMultiple) {

                // Si hay capacidad definida → usarla
                if (window.registrationConfig.maxCapacity) {
                    stock = window.registrationConfig.maxCapacity;
                } else {
                    stock = 9999; // fallback seguro
                }

            } else {
                stock = 1;
            }

            // 🎯 Golf team siempre es 1
            if (window.registrationConfig.templateForm === 'golf_team') {
                stock = 1;
            }
        }

        window.cartState.items.push({
            id: window.registrationTicket.id,
            event_id: window.EVENT_ID,
            name: window.registrationTicket.name,
            total_price: Number(window.registrationTicket.total_price),
            stock: stock,
            qty: 1,
            svg_selector: window.registrationTicket.svg_selector ?? null
        });

        updateCartUI();
    }


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
            event_id: window.EVENT_ID,
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
        if (window.stopOnlineSales && !window.canBypassOnlineStop) {
            alert('La venta en línea está detenida para este evento.');
            return;
        }

        if (!window.cartState.items.length) {
            alert('Carrito vacío');
            return;
        }

        // 📝 Validar inscripción
        if (window.isRegistration) {
            if (!validateRegistrationForm()) {
                return; // ⛔ NO PASA
            }
        }

        let registrationData = null;

        if (window.isRegistration) {
            const form = document.getElementById('registrationForm');
            if (!form) {
                alert('Formulario de inscripción no encontrado');
                return;
            }

            registrationData = formDataToObject(form);

        }

        fetch('/cart/add', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.Laravel.csrfToken
            },
            body: JSON.stringify({
                event_id: window.EVENT_ID,
                cart: window.cartState.items.map(t => ({
                    id: t.id,
                    event_id: t.event_id,
                    name: t.name,
                    price: t.total_price,
                    qty: t.qty,
                    selectorSVG: t.svg_selector,
                    type: window.isRegistration ? 'registration' : 'ticket'
                })),
                registration: registrationData
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
    const alertaVentaOnline = document.getElementById('alertaVentaOnline');

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

    if (btn) {
        const isStoppedForUser = window.stopOnlineSales && !window.canBypassOnlineStop;
        btn.disabled = window.cartState.items.length === 0 || isStoppedForUser;

        if (isStoppedForUser) {
            btn.classList.add('disabled');
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.6';
            btn.innerText = 'Venta en línea detenida';
        } else {
            btn.classList.remove('disabled');
            btn.style.pointerEvents = '';
            btn.style.opacity = '';
            btn.innerText = 'Continuar pago';
        }
    }

    if (alertaVentaOnline) {
        if (window.stopOnlineSales && !window.canBypassOnlineStop) {
            alertaVentaOnline.innerHTML = `
                <div class="alert alert-warning fw-semibold">
                    ⚠️ La venta en línea está detenida para este evento.
                </div>
            `;
        } else {
            alertaVentaOnline.innerHTML = '';
        }
    }
}

function validateRegistrationForm() {

    const form = document.getElementById('registrationForm');
    if (!form) return true;

    // 🔴 Validación HTML5 nativa
    if (!form.checkValidity()) {
        form.reportValidity(); // muestra mensajes del navegador
        return false;
    }

    // 📱 Validación extra de celulares
    const phones = form.querySelectorAll('input[type="tel"]');
    for (const phone of phones) {
        if (phone.value.length < 10) {
            alert('El número de celular debe tener 10 dígitos');
            phone.focus();
            return false;
        }
    }

    // 🟣 Validación Relación Cumbres (AL MENOS UNO POR JUGADOR)
    const groups = form.querySelectorAll('.cumbres-group');

    for (const group of groups) {
        const checkboxes = group.querySelectorAll('input[type="checkbox"]');
        const hasChecked = Array.from(checkboxes).some(cb => cb.checked);

        if (!hasChecked) {
            toastr.error('Debes seleccionar al menos una opción en cada sección requerida.');
            group.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
    }


    return true; // ✅ OK
}
function formDataToObject(form) {
    const data = {};
    const formData = new FormData(form);

    for (let [key, value] of formData.entries()) {

        const keys = key
            .replace(/\]/g, '')
            .split('[');

        let current = data;

        keys.forEach((k, i) => {

            const isLast = i === keys.length - 1;
            const isArrayPush = k === ''; // ← detecta []

            if (isLast) {
                if (isArrayPush) {
                    // push directo al array
                    if (!Array.isArray(current)) {
                        current = [];
                    }
                    current.push(value);
                } else {
                    if (current[k] !== undefined) {
                        if (!Array.isArray(current[k])) {
                            current[k] = [current[k]];
                        }
                        current[k].push(value);
                    } else {
                        current[k] = value;
                    }
                }
            } else {
                if (k === '') return;

                if (!current[k]) {
                    current[k] = isNaN(keys[i + 1]) && keys[i + 1] !== ''
                        ? {}
                        : [];
                }

                current = current[k];
            }
        });
    }

    return data;
}
