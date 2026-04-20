document.addEventListener('DOMContentLoaded', function () {
    /**
     * =========================
     * ESTADO DEL CARRITO
     * =========================
     */
    window.cartState = {
        items: []
    };

    window.getRegistrationPromotionConfig = function (eventId) {
        if (!window.REGISTRATION_PROMOTIONS) {
            return null;
        }

        return window.REGISTRATION_PROMOTIONS[String(eventId)] ?? null;
    };

    window.applyRegistrationPricingToItem = function (item) {
        if (!item || item.id !== 'registration') {
            if (item) {
                delete item.promotion;
            }
            return item;
        }

        const config = window.getRegistrationPromotionConfig(item.event_id);
        const fallbackBase = Number(window.registrationTicket?.total_price ?? 0);
        const basePrice = Number(
            item.base_price ?? item.total_price ?? fallbackBase
        );

        item.base_price = Number.isFinite(basePrice) ? basePrice : 0;

        if (!config) {
            item.total_price = item.base_price;
            delete item.promotion;
            return item;
        }

        const qty = Math.max(1, Number(item.qty) || 1);
        const minQty = Math.max(1, Number(config.minQty) || 1);
        const promoPrice = Number(config.promoPrice);

        if (qty >= minQty && Number.isFinite(promoPrice)) {
            item.total_price = promoPrice;
            item.promotion = {
                applied: true,
                type: 'registration_qty_discount',
                label: config.label ?? 'Promocion aplicada',
                original_price: item.base_price,
                discounted_price: promoPrice,
                min_qty: minQty
            };
        } else {
            item.total_price = item.base_price;
            delete item.promotion;
        }

        return item;
    };

    window.isWhatsappDirectRegistration = function () {
        return Boolean(
            window.isRegistration
            && window.registrationConfig
            && window.registrationConfig.templateForm === 'whatsapp_direct'
        );
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
            if (
                window.registrationConfig.templateForm === 'golf_team'
                || window.registrationConfig.templateForm === 'whatsapp_direct'
            ) {
                stock = 1;
            }
        }

        window.cartState.items.push({
            id: window.registrationTicket.id,
            event_id: window.EVENT_ID,
            name: window.registrationTicket.name,
            total_price: Number(window.registrationTicket.total_price),
            base_price: Number(window.registrationTicket.total_price),
            stock: stock,
            qty: 1,
            svg_selector: window.registrationTicket.svg_selector ?? null
        });

        window.applyRegistrationPricingToItem(window.cartState.items[0]);
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
            window.applyRegistrationPricingToItem(item);
            updateCartUI();
            return;
        }

        const newItem = {
            id: ticket.id,
            event_id: window.EVENT_ID,
            name: ticket.name,
            total_price: Number(ticket.total_price),
            base_price: Number(ticket.total_price),
            stock: ticket.stock ?? 1,
            qty: 1,
            svg_selector: ticket.svg_selector ?? null
        };

        window.cartState.items.push(newItem);
        window.applyRegistrationPricingToItem(newItem);

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

        window.applyRegistrationPricingToItem(item);
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
            toastr.error('La venta en línea está detenida para este evento.');

            return;
        }

        if (!window.cartState.items.length) {
            toastr.error('El carrito está vacío');
            return;
        }

        // 📝 Validar inscripción
        if (window.isRegistration) {
            if (!validateRegistrationForm()) {
                return; // ⛔ NO PASA
            }
        }

        if (window.isWhatsappDirectRegistration()) {
            submitDirectRegistration();
            return;
        }

        let registrationData = null;

        if (window.isRegistration) {
            const form = document.getElementById('registrationForm');
            if (!form) {
                toastr.error('Formulario de inscripcion no encontrado');
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
                    price: Number(t.total_price),
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
                toastr.error('Error al preparar el pago');
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
        window.applyRegistrationPricingToItem?.(ticket);

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

        const promotionHtml = ticket.promotion?.applied
            ? `<div class="text-success fs-8 fw-semibold">${ticket.promotion.label}</div>`
            : '';

        li.innerHTML = `
            <div style="flex:1;">
                <div>${ticket.name}</div>
                <div class="cart-item-price">
                    $${ticket.total_price.toLocaleString('es-MX')} c/u
                </div>
                ${promotionHtml}
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


        total += Number(ticket.total_price) * Number(ticket.qty);
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
            btn.innerText = window.isWhatsappDirectRegistration?.()
                ? 'Enviar registro'
                : 'Continuar pago';
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

function extractErrorMessage(payload) {
    if (!payload) {
        return 'No se pudo completar el registro.';
    }

    if (typeof payload.message === 'string' && payload.message.trim() !== '') {
        return payload.message;
    }

    if (payload.errors && typeof payload.errors === 'object') {
        const firstKey = Object.keys(payload.errors)[0];
        const firstError = payload.errors[firstKey];

        if (Array.isArray(firstError) && firstError.length > 0) {
            return firstError[0];
        }
    }

    return 'No se pudo completar el registro.';
}

function submitDirectRegistration() {
    const form = document.getElementById('registrationForm');
    const btn = document.getElementById('btnCheckout');
    const endpoint = window.Laravel?.routes?.directRegistration;

    if (!endpoint) {
        toastr.error('No se encontro la ruta de registro directo.');
        return;
    }

    if (!form) {
        toastr.error('Formulario de registro no encontrado.');
        return;
    }

    if (!validateRegistrationForm()) {
        return;
    }

    const body = new FormData(form);

    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Enviando...';
    }

    fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': window.Laravel.csrfToken,
            'Accept': 'application/json'
        },
        body
    })
        .then(async (res) => {
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                throw data;
            }

            return data;
        })
        .then((data) => {
            const title = data?.title ?? 'Gracias por tu registro';
            const description = data?.description ?? 'Tu registro fue guardado correctamente.<br> unete a nuestro grupo de WhatsApp para más información y actualizaciones.';
            const whatsappLink = data?.whatsapp_link ?? '';

            let message = description;
            if (whatsappLink) {
                message += `<br><br><a href="${whatsappLink}" target="_blank" class="fw-bold">Unirse al grupo de WhatsApp</a>`;
            }

            if (window.Swal?.fire) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: title,
                    html: message,
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                }).then(() => {
                    if (whatsappLink) {
                        window.location.href = whatsappLink;
                        return;
                    }
                    window.location.reload();
                });
            } else {
                // fallback simple
                alert(`${title}\n\n${description}`);
                if (whatsappLink) {
                    window.location.href = whatsappLink;
                    return;
                }
                window.location.reload();
            }
        })
        .catch((payload) => {
            toastr.error(extractErrorMessage(payload));
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                updateCartUI();
            }
        });
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
            toastr.error('El número de celular debe tener 10 dígitos');
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

