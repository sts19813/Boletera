document.addEventListener('DOMContentLoaded', function () {
    window.cartState = {
        items: []
    };

    window.currentCouponCode = '';
    window.appliedCoupon = null;

    window.getRegistrationPromotionConfig = function (eventId) {
        if (!window.REGISTRATION_PROMOTIONS) {
            return null;
        }

        return window.REGISTRATION_PROMOTIONS[String(eventId)] ?? null;
    };

    window.applyRegistrationPricingToItem = function (item) {
        if (!item) {
            return item;
        }

        if (item.id !== 'registration') {
            const base = Number(item.base_price ?? item.unit_price ?? item.total_price ?? 0);
            item.base_price = Number.isFinite(base) ? base : 0;
            item.unit_price = item.base_price;
            if (!window.currentCouponCode) {
                item.total_price = item.unit_price;
            }
            delete item.promotion;
            return item;
        }

        const config = window.getRegistrationPromotionConfig(item.event_id);
        const fallbackBase = Number(window.registrationTicket?.total_price ?? 0);
        const rawBasePrice = Number(
            item.base_price ?? item.unit_price ?? item.total_price ?? fallbackBase
        );

        item.base_price = Number.isFinite(rawBasePrice) ? rawBasePrice : 0;

        if (!config) {
            item.unit_price = item.base_price;
            if (!window.currentCouponCode) {
                item.total_price = item.unit_price;
            }
            delete item.promotion;
            return item;
        }

        const qty = Math.max(1, Number(item.qty) || 1);
        const minQty = Math.max(1, Number(config.minQty) || 1);
        const promoPrice = Number(config.promoPrice);

        if (qty >= minQty && Number.isFinite(promoPrice)) {
            item.unit_price = promoPrice;
            item.promotion = {
                applied: true,
                type: 'registration_qty_discount',
                label: config.label ?? 'Promocion aplicada',
                original_price: item.base_price,
                discounted_price: promoPrice,
                min_qty: minQty
            };
        } else {
            item.unit_price = item.base_price;
            delete item.promotion;
        }

        if (!window.currentCouponCode) {
            item.total_price = item.unit_price;
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

    window.isZeroPriceRegistration = function () {
        return Boolean(
            window.isRegistration
            && Number(window.registrationTicket?.total_price ?? 0) <= 0
        );
    };

    window.useDirectRegistrationFlow = function () {
        return Boolean(
            window.isWhatsappDirectRegistration()
            || window.isZeroPriceRegistration()
        );
    };

    function getCouponInput() {
        return document.getElementById('couponCodeInput');
    }

    function setCouponFeedback(message, type = 'muted') {
        const feedback = document.getElementById('couponFeedback');

        if (!feedback) {
            return;
        }

        const classMap = {
            success: 'text-success',
            error: 'text-danger',
            muted: 'text-muted'
        };

        feedback.className = `fs-8 mt-2 ${classMap[type] ?? classMap.muted}`;
        feedback.textContent = message || '';
    }

    function resetCouponDataFromItems() {
        window.cartState.items.forEach(item => {
            window.applyRegistrationPricingToItem(item);
            item.total_price = Number(item.unit_price ?? item.base_price ?? item.total_price ?? 0);
            item.discount_percent = null;
            item.discount_amount = 0;
            item.coupon_code = null;
            item.coupon_id = null;
        });

        window.appliedCoupon = null;
    }

    async function recalculateCouponPricing(showErrors = false) {
        if (!window.couponConfig?.enabled) {
            return;
        }

        const code = window.currentCouponCode;

        if (!code) {
            resetCouponDataFromItems();
            setCouponFeedback('');
            updateCartUI();
            return;
        }

        if (!window.cartState.items.length) {
            setCouponFeedback('');
            return;
        }

        try {
            const payload = {
                code,
                cart: window.cartState.items.map(item => ({
                    id: item.id,
                    event_id: item.event_id,
                    type: item.id === 'registration' ? 'registration' : 'ticket',
                    qty: item.qty,
                    base_price: Number(item.unit_price ?? item.base_price ?? item.total_price ?? 0),
                    price: Number(item.unit_price ?? item.base_price ?? item.total_price ?? 0)
                }))
            };

            const response = await fetch(window.couponConfig.validateUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.Laravel.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
                window.appliedCoupon = null;
                resetCouponDataFromItems();
                updateCartUI();
                const message = data.message ?? 'No se pudo aplicar el cupón.';
                setCouponFeedback(message, 'error');

                if (showErrors) {
                    toastr.error(message);
                }

                return;
            }

            window.appliedCoupon = data.coupon ?? null;

            window.cartState.items = window.cartState.items.map((localItem, index) => {
                const remoteItem = data.cart?.[index] ?? null;

                if (!remoteItem) {
                    return localItem;
                }

                localItem.base_price = Number(remoteItem.base_price ?? localItem.base_price ?? 0);
                localItem.unit_price = Number(remoteItem.base_price ?? localItem.unit_price ?? localItem.total_price ?? 0);
                localItem.total_price = Number(remoteItem.price ?? localItem.total_price ?? 0);
                localItem.discount_percent = remoteItem.discount_percent;
                localItem.discount_amount = Number(remoteItem.discount_amount ?? 0);
                localItem.coupon_code = remoteItem.coupon_code ?? null;
                localItem.coupon_id = remoteItem.coupon_id ?? null;

                return localItem;
            });

            setCouponFeedback(`Cupón aplicado: ${window.appliedCoupon?.code ?? code}`, 'success');
            updateCartUI();
        } catch (error) {
            if (showErrors) {
                toastr.error('No se pudo validar el cupón.');
            }
        }
    }

    if (window.isRegistration && window.registrationTicket) {
        let stock = 1;

        if (window.registrationConfig) {
            if (window.registrationConfig.allowsMultiple) {
                if (window.registrationConfig.maxCapacity) {
                    stock = window.registrationConfig.maxCapacity;
                } else {
                    stock = 9999;
                }
            } else {
                stock = 1;
            }

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
            unit_price: Number(window.registrationTicket.total_price),
            base_price: Number(window.registrationTicket.total_price),
            stock: stock,
            qty: 1,
            svg_selector: window.registrationTicket.svg_selector ?? null
        });

        window.applyRegistrationPricingToItem(window.cartState.items[0]);
        window.cartState.items[0].total_price = Number(window.cartState.items[0].unit_price ?? window.registrationTicket.total_price);
        updateCartUI();
    }

    function getCartItem(ticketId) {
        return window.cartState.items.find(t => t.id == ticketId);
    }

    window.addToCart = function (ticket) {
        let item = getCartItem(ticket.id);

        if (item) {
            if (ticket.stock > 1 && item.qty < ticket.stock) {
                item.qty++;
            }

            window.applyRegistrationPricingToItem(item);
            if (!window.currentCouponCode) {
                item.total_price = Number(item.unit_price ?? item.total_price);
            }
            updateCartUI();

            if (window.currentCouponCode) {
                recalculateCouponPricing();
            }

            return;
        }

        const basePrice = Number(ticket.total_price ?? 0);

        const newItem = {
            id: ticket.id,
            event_id: window.EVENT_ID,
            name: ticket.name,
            total_price: basePrice,
            unit_price: basePrice,
            base_price: basePrice,
            stock: ticket.stock ?? 1,
            qty: 1,
            svg_selector: ticket.svg_selector ?? null,
            discount_percent: null,
            discount_amount: 0,
            coupon_code: null,
            coupon_id: null
        };

        window.cartState.items.push(newItem);
        window.applyRegistrationPricingToItem(newItem);
        if (!window.currentCouponCode) {
            newItem.total_price = Number(newItem.unit_price ?? newItem.total_price);
        }

        updateCartUI();

        if (window.currentCouponCode) {
            recalculateCouponPricing();
        }
    };

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
        if (!window.currentCouponCode) {
            item.total_price = Number(item.unit_price ?? item.total_price);
        }

        updateCartUI();

        if (window.currentCouponCode) {
            recalculateCouponPricing();
        }
    };

    window.removeFromCart = function (ticketId) {
        window.cartState.items =
            window.cartState.items.filter(t => t.id != ticketId);
        updateCartUI();

        if (window.currentCouponCode) {
            recalculateCouponPricing();
        }
    };

    document.querySelectorAll(selector).forEach(group => {
        if (group.dataset.status !== 'available') return;

        group.addEventListener('click', () => {
            const ticket = window.getTicketFromGroup(group);
            if (!ticket) return;

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

    document.getElementById('applyCouponBtn')?.addEventListener('click', function () {
        const input = getCouponInput();
        const code = (input?.value ?? '').trim().toUpperCase();

        if (!code) {
            window.currentCouponCode = '';
            resetCouponDataFromItems();
            setCouponFeedback('');
            updateCartUI();
            return;
        }

        window.currentCouponCode = code;
        if (input) {
            input.value = code;
        }

        recalculateCouponPricing(true);
    });

    document.getElementById('clearCouponBtn')?.addEventListener('click', function () {
        const input = getCouponInput();

        if (input) {
            input.value = '';
        }

        window.currentCouponCode = '';
        resetCouponDataFromItems();
        setCouponFeedback('');
        updateCartUI();
    });

    document.getElementById('btnCheckout')?.addEventListener('click', () => {
        if (window.stopOnlineSales && !window.canBypassOnlineStop) {
            toastr.error('La venta en línea está detenida para este evento.');

            return;
        }

        if (!window.cartState.items.length) {
            toastr.error('El carrito está vacío');
            return;
        }

        if (window.isRegistration) {
            if (!validateRegistrationForm()) {
                return;
            }
        }

        if (window.useDirectRegistrationFlow()) {
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
                coupon_code: window.currentCouponCode || null,
                cart: window.cartState.items.map(t => ({
                    id: t.id,
                    event_id: t.event_id,
                    name: t.name,
                    price: Number(t.total_price),
                    base_price: Number(t.unit_price ?? t.base_price ?? t.total_price),
                    discount_percent: t.discount_percent,
                    discount_amount: Number(t.discount_amount ?? 0),
                    coupon_code: t.coupon_code,
                    coupon_id: t.coupon_id,
                    qty: t.qty,
                    selectorSVG: t.svg_selector,
                    type: window.isRegistration ? 'registration' : 'ticket'
                })),
                registration: registrationData
            })
        })
            .then(res => res.json())
            .then(() => {
                window.location.href = '/pago';
            })
            .catch(err => {
                console.error(err);
                toastr.error('Error al preparar el pago');
            });
    });
});

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

        const couponHtml = ticket.coupon_code && Number(ticket.discount_amount ?? 0) > 0
            ? `<div class="text-success fs-8 fw-semibold">Cupón ${ticket.coupon_code} aplicado</div>`
            : '';

        li.innerHTML = `
            <div style="flex:1;">
                <div>${ticket.name}</div>
                <div class="cart-item-price">
                    $${Number(ticket.total_price).toLocaleString('es-MX')} c/u
                </div>
                ${promotionHtml}
                ${couponHtml}
            </div>

            ${controls}
            <span class="cart-remove">✕</span>
        `;

        li.querySelector('span:last-child').onclick = () => {
            removeFromCart(ticket.id);

            if (ticket.stock <= 1) {
                const mapping = window.dbLotes.find(m => m.ticket_id == ticket.id);
                if (mapping) {
                    const el = document.getElementById(mapping.svg_selector);
                    if (el) paintGroup(el, ticketStatusColors.available);
                }
            }
        };

        list.appendChild(li);

        li.querySelector('.btn-minus')?.addEventListener('click', e => {
            e.stopPropagation();
            updateQty(ticket.id, -1);
        });

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
            btn.innerText = window.useDirectRegistrationFlow?.()
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
    const directQty = Number(window.cartState?.items?.find(t => t.id === 'registration')?.qty ?? 1);
    body.append('qty', String(Math.max(1, directQty)));

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
                message += `<br><br><span class="fw-bold">¿Deseas unirte al grupo de WhatsApp?</span>`;
            }

            if (window.Swal?.fire) {
                Swal.fire({
                    icon: 'success',
                    title: title,
                    html: message,
                    confirmButtonText: whatsappLink ? 'Abrir grupo' : 'Aceptar',
                    showCancelButton: !!whatsappLink,
                    reverseButtons: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    window.location.href = 'https://stomtickets.com';
                });
            } else {
                alert(`${title}\n\n${description}`);

                if (whatsappLink) {
                    window.open(whatsappLink, '_blank');
                }

                window.location.href = 'https://stomtickets.com';
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

    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    const phones = form.querySelectorAll('input[type="tel"]');
    for (const phone of phones) {
        if (phone.value.length < 10) {
            toastr.error('El número de celular debe tener 10 dígitos');
            phone.focus();
            return false;
        }
    }

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

    if (window.registrationFormMode === 'builder' && !window.validateRegistrationBuilderForm?.(form)) {
        toastr.error('Debes completar los campos obligatorios.');
        return false;
    }

    return true;
}

function formDataToObject(form) {
    const data = {};
    const formData = new FormData(form);

    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            value = value.name || '';
        }

        const keys = key
            .replace(/\]/g, '')
            .split('[');

        let current = data;

        keys.forEach((k, i) => {
            const isLast = i === keys.length - 1;
            const isArrayPush = k === '';

            if (isLast) {
                if (isArrayPush) {
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
