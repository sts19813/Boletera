@extends('layouts.iframe')

@section('title', 'Stom Tickets')
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('content')

    <x-event-header :evento="$lot" />
    <style>
        .btn-metodo.active {
            outline: 3px solid rgba(0, 0, 0, .2);
        }
    </style>

    <link rel="stylesheet"
        href="{{ asset('assets/css/configurador.css') }}?v={{ filemtime(public_path('assets/css/configurador.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <div class="container py-10 position-relative" style="margin-top:-130px;">
        <div class="row g-8">
            {{-- ===================== --}}
            {{-- IZQUIERDA: MAPA + SVG --}}
            {{-- ===================== --}}
            <div class="col-lg-8">

                @if($lot->is_registration)

                    <form id="registrationForm">
                        @if($lot->is_registration && $lot->template_form === 'cena_gala')
                            @include('events.partials.registration-cena-gala')
                        @elseif($lot->is_registration && $lot->template_form === 'golf_team')
                            @include('events.partials.registration-golf-team')
                        @endif
                    </form>
                @elseif($lot->has_seat_mapping)

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
                    {{-- ===================== --}}
                    {{-- MAPA DE ASIENTOS --}}
                    {{-- ===================== --}}
                    @include('events.partials.seat-map')
                @else
                    {{-- ===================== --}}
                    {{-- SELECTS DE TICKETS --}}
                    {{-- ===================== --}}
                    @include('events.partials.ticket-selects')
                @endif
            </div>


            {{-- ===================== --}}
            {{-- DERECHA: CARRITO --}}
            {{-- ===================== --}}
            <div class="col-lg-4">
                @include('events.partials.cartItems')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.isAdmin = @json(auth()->check() && auth()->user()->isAdmin());
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
        window.idDesarrollo = @json($lot->id);
        window.EVENT_ID = @json($lot->id);
        let redireccion = true;

        window.registrationConfig = {
            allowsMultiple: @json($lot->allows_multiple_registrations),
            maxCapacity: @json($lot->max_capacity),
            templateForm: @json($lot->template_form)
        };
    </script>
    <script src="https://unpkg.com/@panzoom/panzoom/dist/panzoom.min.js"></script>
    <script src="/assets/js/shared-svg.js"></script>
    <script src="{{ asset('assets/js/iframe.js') }}?v={{ filemtime(public_path('assets/js/iframe.js')) }}"></script>

    @if(!$lot->has_seat_mapping)
        <script src="/assets/js/ticket-selects.js"></script>
    @endif
    <script src="/assets/js/seat-map.js"></script>
    <script>
        window.isRegistration = @json($lot->is_registration);
        window.registrationTicket = {
            id: 'registration',
            name: 'Inscripción - {{ $lot->name }}',
            total_price: {{ $lot->price ?? 0 }},
            stock: 1,
            qty: 1,
            svg_selector: null
        };
    </script>
@endpush