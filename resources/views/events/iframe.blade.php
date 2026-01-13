@extends('layouts.iframe')

@section('title', 'Stom Tickets')
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('content')

<style>
    .btn-metodo.active {
    outline: 3px solid rgba(0,0,0,.2);
}

</style>

    <link rel="stylesheet" href="/assets/css/configurador.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <div class="container py-10 position-relative" style="margin-top:-130px;">
        <div class="row g-8">

            {{-- ===================== --}}
            {{-- IZQUIERDA: MAPA + SVG --}}
            {{-- ===================== --}}
            <div class="col-lg-8">

                {{-- INSTRUCCIONES --}}
                <div class="seat-instructions mb-6">

                    <div class="seat-instructions-inner">

                        <div class="seat-arrow">
                            <i class="ki-outline ki-arrow-down fs-2"></i>
                        </div>

                        <div class="seat-text">
                            <div class="fw-bold fs-5">
                                Selecciona tus asientos preferidos
                            </div>
                            <div class="text-muted fs-7">
                                Una vez seleccionados, continúa con el pago para asegurar tu lugar
                            </div>
                        </div>

                        <div class="seat-arrow">
                            <i class="ki-outline ki-arrow-down fs-2"></i>
                        </div>

                    </div>

                    {{-- LEYENDA --}}
                    <div class="seat-legend mt-4">
                        <div class="legend-item">
                            <span class="legend-dot available"></span>
                            <span>Disponibles</span>
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot occupied"></span>
                            <span>Ocupados</span>
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot selected"></span>
                            <span>Seleccionado</span>
                        </div>
                    </div>
                </div>

                {{-- MAPA --}}
                <div class="text-center">
                    <div style="position: relative; display: inline-block; width:100%;">
                        <div class="seat-map-wrapper">
                            <div id="seatMap" class="seat-map">
                                {{-- PNG --}}
                                @if ($lot->png_image)
                                    <img src="{{ asset('/' . $lot->png_image) }}" alt="Mapa" />
                                @endif

                                {{-- SVG --}}
                                @if ($lot->svg_image)
                                    <div class="seat-svg">
                                        {!! file_get_contents(public_path($lot->svg_image)) !!}
                                    </div>
                                @endif
                            </div>

                            {{-- CONTROLES MOBILE --}}
                            <div class="seat-zoom-controls">
                                <button id="zoomIn">+</button>
                                <button id="zoomOut">−</button>
                            </div>

                            {{-- HINT --}}
                            <div class="seat-zoom-hint d-lg-none">
                                Usa dos dedos para acercar o alejar
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== --}}
            {{-- DERECHA: CARRITO --}}
            {{-- ===================== --}}
            <div class="col-lg-4">

                <div class="card card-flush shadow-sm cart-sticky">

                    <div class="card-header">
                        <h3 class="card-title fw-bold d-flex align-items-center gap-2">
                            <i class="ki-duotone ki-basket fs-3 text-primary"></i>
                            Boletos seleccionados
                        </h3>
                    </div>

                    <div class="card-body">

                        <ul id="cartItems" class="kt-cart-items mb-6"></ul>

                        <div class="separator separator-dashed my-4"></div>

                        <div class="d-flex justify-content-between mb-4 fw-semibold">
                            <span>Total</span>
                            <span id="cartTotal" class="text-primary">$0</span>
                        </div>

                        @if(!auth()->check() || !auth()->user()->is_admin)
                            <button id="btnCheckout" class="btn btn-primary w-100 fw-semibold" disabled>
                                Continuar pago
                            </button>
                        @endif

                        @if(auth()->check() && auth()->user()->is_admin)

                            <button id="btnCheckout" class="btn btn-primary w-100 fw-semibold d-none" disabled>
                                Continuar pago
                            </button>

                            <div class="mt-3">
                                <label class="form-label fw-bold mb-2">Tipo de venta</label>

                                <div class="d-grid gap-2">
                                    <button class="btn btn-success btn-lg fw-bold btn-metodo"
                                        data-metodo="cash">
                                        💵 Efectivo
                                    </button>

                                    <button class="btn btn-primary btn-lg fw-bold btn-metodo"
                                        data-metodo="card">
                                        💳 Tarjeta
                                    </button>

                                    <button class="btn btn-secondary btn-lg fw-bold btn-metodo d-none"
                                        data-metodo="cortesia">
                                        🎟️ Cortesía
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label fw-bold">
                                    Nombre o correo <span class="text-muted">(opcional)</span>
                                </label>
                                <input type="text" id="ventaNombre" class="form-control"
                                    placeholder="Ej. Juan Pérez o invitado@gmail.com">
                            </div>

                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.isAdmin = @json(auth()->check() && auth()->user()->isAdmin());
    </script>

    <script>
        let selector = @json($lot->modal_selector ?? 'svg g *');

        window.Laravel = {
            csrfToken: "{{ csrf_token() }}",
            routes: {
                lotsFetch: "{{ route('events.fetch') }}",
                lotesStore: "{{ route('events.store') }}"
            }
        };

        window.preloadedLots = @json($lots);
        window.currentLot = @json($lot);
        window.projects = @json($projects);
        window.dbLotes = @json($dbLotes);

        window.idDesarrollo = {{ $lot->id }};
        let redireccion = true;
    </script>
    <script src="https://unpkg.com/@panzoom/panzoom/dist/panzoom.min.js"></script>

    <script src="/assets/js/shared-svg.js"></script>
    <script src="/assets/js/iframe.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const seatMap = document.getElementById('seatMap');

            const panzoom = Panzoom(seatMap, {
                maxScale: 4,
                minScale: 1,
                step: 0.3,
                contain: 'outside',
                cursor: 'grab'
            });

            // Zoom con rueda (desktop)
            seatMap.parentElement.addEventListener('wheel', panzoom.zoomWithWheel);

            // Evitar que los clicks en asientos bloqueen el drag
            seatMap.addEventListener('pointerdown', (e) => {
                if (e.target.closest('path, rect, polygon, circle')) {
                    e.stopPropagation();
                }
            });

            // Botones mobile
            const zoomIn = document.getElementById('zoomIn');
            const zoomOut = document.getElementById('zoomOut');

            if (zoomIn && zoomOut) {
                zoomIn.addEventListener('click', () => panzoom.zoomIn());
                zoomOut.addEventListener('click', () => panzoom.zoomOut());
            }
        });

       

        document.querySelectorAll('.btn-metodo').forEach(btn => {
            btn.addEventListener('click', () => {

                const metodoPago = btn.dataset.metodo; // cash | card | cortesia
                const nombreInput = document.getElementById('ventaNombre').value.trim();

                const esCortesia = metodoPago === 'cortesia';

                let email;

                if (nombreInput) {
                    email = nombreInput;
                } else if (esCortesia) {
                    email = 'CORTESIA';
                } else {
                    email = 'taquilla@local';
                }

                fetch('/taquilla/sell', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.Laravel.csrfToken
                    },
                    body: JSON.stringify({
                        cart: window.cartState.items,
                        cortesia: esCortesia,
                        email: email,
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

    </script>


@endpush