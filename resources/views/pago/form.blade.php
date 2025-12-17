@extends('layouts.iframe')


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
                                <label class="form-label required">Nombre completo</label>
                                <input type="text" id="buyerName" class="form-control form-control-solid"
                                    placeholder="Nombre del titular" required>
                            </div>

                            <div class="mb-6">
                                <label class="form-label required">Correo electrónico</label>
                                <input type="email" id="buyerEmail" class="form-control form-control-solid"
                                    placeholder="correo@ejemplo.com" required>
                            </div>

                            <div class="mb-6">
                                <label class="form-label required">Teléfono</label>
                                <input type="tel" id="buyerPhone" class="form-control form-control-solid"
                                    placeholder="Ej. 9991234567" required>
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
                                    <label class="form-label required">CVC</label>
                                    <div id="card-cvc" class="form-control form-control-solid py-4"></div>
                                </div>
                            </div>


                            <div id="card-errors" class="text-danger mb-4"></div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold">
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

            const style = {
                base: {
                    color: '#181C32',
                    fontFamily: 'Inter, sans-serif',
                    fontSize: '16px',
                    '::placeholder': { color: '#A1A5B7' }
                },
                invalid: { color: '#F1416C' }
            };

            const cardNumber = elements.create('cardNumber', { style });
            const cardExpiry = elements.create('cardExpiry', { style });
            const cardCvc = elements.create('cardCvc', { style });

            cardNumber.mount('#card-number');
            cardExpiry.mount('#card-expiry');
            cardCvc.mount('#card-cvc');


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
                        const name = document.getElementById('buyerName').value;
                        const phone = document.getElementById('buyerPhone').value;

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