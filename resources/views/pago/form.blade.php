@extends('layouts.app')

@section('title', 'Pago seguro')

@section('content')
<div class="container py-10">
    <div class="row g-8">

        {{-- FORMULARIO DE PAGO --}}
        <div class="col-lg-8">
            <div class="card card-flush shadow-sm">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Método de pago</h3>
                </div>

                <div class="card-body">
                    <form id="payment-form">

                        <div class="mb-6">
                            <label class="form-label required">Nombre en la tarjeta</label>
                            <input type="text"
                                   class="form-control form-control-solid"
                                   placeholder="Nombre completo"
                                   required>
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Correo electrónico</label>
                            <input type="email"
                                   id="buyerEmail"
                                   class="form-control form-control-solid"
                                   placeholder="correo@ejemplo.com"
                                   required>
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Datos de la tarjeta</label>
                            <div id="card-element"
                                 class="form-control form-control-solid py-4"></div>
                        </div>

                        <div id="card-errors" class="text-danger mb-4"></div>

                        <button type="submit"
                                class="btn btn-primary w-100 fw-bold">
                            Completar compra
                        </button>

                    </form>
                </div>
            </div>
        </div>

        {{-- RESUMEN --}}
        <div class="col-lg-4">
            <div class="card card-flush shadow-sm">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Resumen de compra</h3>
                </div>

                <div class="card-body">

                    <div class="mb-6">
                        @foreach ($carrito as $item)
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-gray-700">{{ $item['name'] }}</span>
                                <span class="fw-semibold">
                                    ${{ number_format($item['price'], 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="separator separator-dashed my-4"></div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-600">Subtotal</span>
                        <span>${{ number_format($subtotal, 2) }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-600">Cargo por servicio</span>
                        <span>${{ number_format($comision, 2) }}</span>
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
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const stripe = Stripe('{{ config('services.stripe.key') }}');
    const elements = stripe.elements();

    const card = elements.create('card', {
        style: {
            base: {
                color: '#181C32',
                fontFamily: 'Inter, sans-serif',
                fontSize: '16px',
                '::placeholder': { color: '#A1A5B7' }
            },
            invalid: { color: '#F1416C' }
        }
    });

    card.mount('#card-element');

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

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const email = document.getElementById('buyerEmail').value;

            stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: card,
                    billing_details: { email: email }
                }
            }).then(result => {

                if (result.error) {
                    document.getElementById('card-errors').textContent =
                        result.error.message;
                    return;
                }

                if (result.paymentIntent.status === 'succeeded') {
                    window.location.href =
                        "{{ route('pago.success') }}?email=" +
                        encodeURIComponent(email);
                }
            });
        });
    });
});
</script>
@endpush
