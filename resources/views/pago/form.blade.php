@extends('layouts.iframe')

@section('title', 'Pago seguro')

@section('content')


<div class="bodyform">

    <div class="container py-10 position-relative" style="margin-top:-130px;">
    <div class="row g-8">

        {{-- IZQUIERDA --}}
        <div class="col-lg-8">

            <form id="payment-form">

                {{-- CARD CONTACTO --}}
                <div class="card card-flush shadow-sm mb-8">
                    <div class="card-header">
                        <h3 class="card-title fw-bold">Datos del comprador</h3>
                    </div>

                    <div class="card-body">

                        <div class="mb-6">
                            <label class="form-label required">Nombre</label>
                            <input type="text" id="buyerName"
                                   class="form-control form-control-solid"
                                   placeholder="Nombre completo" required>
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Celular</label>
                            <input type="tel" id="buyerPhone"
                                   class="form-control form-control-solid"
                                   placeholder="Ej. 9991234567" required>
                        </div>

                        <div class="text-gray-600 fw-semibold mb-4">
                            Escribe el correo donde se enviarán tus boletos
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Correo</label>
                            <input type="email" id="buyerEmail"
                                   class="form-control form-control-solid"
                                   placeholder="correo@ejemplo.com" required>
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Confirmar correo</label>
                            <input type="email" id="buyerEmailConfirm"
                                   class="form-control form-control-solid"
                                   placeholder="Repite tu correo" required>
                        </div>

                    </div>
                </div>

                {{-- CARD PAGO --}}
                <div class="card card-flush shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title fw-bold">Pago</h3>
                    </div>

                    <div class="card-body">

                        <div class="mb-6">
                            <label class="form-label required">Nombre del titular</label>
                            <input type="text" id="cardHolder"
                                   class="form-control form-control-solid"
                                   placeholder="Tal como aparece en la tarjeta" required>
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Número de tarjeta</label>
                            <div id="card-number" class="form-control form-control-solid py-4"></div>
                        </div>

                        <div class="row g-4 mb-6">
                            <div class="col-md-6">
                                <label class="form-label required">Fecha de vencimiento</label>
                                <div id="card-expiry" class="form-control form-control-solid py-4"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Código de seguridad (CVC)</label>
                                <div id="card-cvc" class="form-control form-control-solid py-4"></div>
                            </div>
                        </div>

                        <div id="card-errors" class="text-danger mb-4"></div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold" style="background: #7723FF !important;">
                            Completar compra
                        </button>

                    </div>
                </div>

            </form>

        </div>

        {{-- DERECHA - RESUMEN --}}
        <div class="col-lg-4">
            <div class="card card-flush shadow-sm">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Resumen de compra</h3>
                </div>

                <div class="card-body">

                    <div class="mb-6">
                        @foreach ($carrito as $item)
                            <div class="d-flex justify-content-between align-items-start mb-3">

                                <div>
                                    <div class="fw-semibold text-gray-800">
                                        {{ $item['name'] }}
                                    </div>

                                    @if(($item['qty'] ?? 1) > 1)
                                        <div class="text-gray-500 fs-8">
                                            x{{ $item['qty'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="fw-semibold">
                                    ${{ number_format($item['price'] * ($item['qty'] ?? 1), 2) }}
                                </div>

                            </div>
                        @endforeach

                    </div>

                    <div class="separator separator-dashed my-4"></div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-600">Subtotal</span>
                        <span>${{ number_format($subtotal, 2) }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 d-none">
                        <span class="text-gray-600">
                            Cargo por servicio
                            <span class="badge badge-light-success ms-2">SIN COSTO</span>
                        </span>

                        <span class="text-gray-400 text-decoration-line-through">
                            ${{ number_format($comision, 2) }}
                        </span>
                    </div>

                    <div class="separator separator-dashed my-4"></div>

                    <div class="d-flex justify-content-between fs-4 fw-bold">
                        <span>Total</span>
                        <span>${{ number_format($total, 2) }}</span>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

</div>

@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    // ===============================
    // Detectar tema actual (Metronic)
    // ===============================
    function getCurrentTheme() {
        let mode = document.documentElement.getAttribute('data-theme-mode');

        if (mode === 'system') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light';
        }

        return mode || 'light';
    }

    document.addEventListener('DOMContentLoaded', function () {

        // ===============================
        // Estilos Stripe
        // ===============================
        const stripeStyleLight = {
            base: {
                color: '#4d578d',
                fontFamily: 'Inter, sans-serif',
                fontSize: '16px',
                '::placeholder': { color: '#A1A5B7' }
            },
            invalid: { color: '#F1416C' }
        };

        const stripeStyleDark = {
            base: {
                color: '#EDEDED',
                fontFamily: 'Inter, sans-serif',
                fontSize: '16px',
                '::placeholder': { color: '#7E8299' }
            },
            invalid: { color: '#F1416C' }
        };

        function getStripeStyle() {
            return getCurrentTheme() === 'dark'
                ? stripeStyleDark
                : stripeStyleLight;
        }

        // ===============================
        // Inicializar Stripe
        // ===============================
        const stripe = Stripe('{{ config('services.stripe.key') }}');
        const elements = stripe.elements();

        let cardNumber = elements.create('cardNumber', { style: getStripeStyle() });
        let cardExpiry = elements.create('cardExpiry', { style: getStripeStyle() });
        let cardCvc = elements.create('cardCvc', { style: getStripeStyle() });

        cardNumber.mount('#card-number');
        cardExpiry.mount('#card-expiry');
        cardCvc.mount('#card-cvc');

        // ===============================
        // Actualizar Stripe al cambiar tema
        // ===============================
        document.addEventListener('click', function (e) {
            const target = e.target.closest('[data-kt-element="mode"]');
            if (!target) return;

            setTimeout(() => {
                const newStyle = getStripeStyle();
                cardNumber.update({ style: newStyle });
                cardExpiry.update({ style: newStyle });
                cardCvc.update({ style: newStyle });
            }, 100);
        });

        // ===============================
        // Crear Payment Intent
        // ===============================
        fetch('{{ route('pago.intent') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {

            const clientSecret = data.clientSecret;
            const form = document.getElementById('payment-form');

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const email = document.getElementById('buyerEmail').value;
                const emailConfirm = document.getElementById('buyerEmailConfirm').value;
                const name = document.getElementById('buyerName').value;
                const phone = document.getElementById('buyerPhone').value;

                if (email !== emailConfirm) {
                    document.getElementById('card-errors').textContent =
                        'Los correos no coinciden';
                    return;
                }

                stripe.confirmCardPayment(clientSecret, {
                    payment_method: {
                        card: cardNumber,
                        billing_details: {
                            name: name,
                            email: email,
                            phone: phone
                        }
                    }
                }).then(result => {

                    if (result.error) {
                        document.getElementById('card-errors').textContent =
                            result.error.message;
                        return;
                    }

                    if (result.paymentIntent.status === 'succeeded') {
                        window.location.href =
                            "{{ route('pago.success') }}?pi=" +
                            result.paymentIntent.id +
                            "&email=" + encodeURIComponent(email);
                    }
                });
            });
        });
    });
</script>
@endpush
