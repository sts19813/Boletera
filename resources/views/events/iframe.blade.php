@extends('layouts.iframe')

@section('title', 'Stom Tickets')
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('content')

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

                        {{-- Imagen base PNG --}}
                        @if ($lot->png_image)
                            <img src="{{ asset('/' . $lot->png_image) }}" alt="PNG" style="width:100%; height:auto;">
                        @endif

                        {{-- SVG encima --}}
                        @if ($lot->svg_image)
                            <div style="position:absolute; top:0; left:0; width:100%;">
                                {!! file_get_contents(public_path($lot->svg_image)) !!}
                            </div>
                        @endif

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

                        <button id="btnCheckout" class="btn btn-primary w-100 fw-semibold" disabled>
                            Continuar pago
                        </button>

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

    <script src="/assets/js/shared-svg.js"></script>
    <script src="/assets/js/iframe.js"></script>
@endpush