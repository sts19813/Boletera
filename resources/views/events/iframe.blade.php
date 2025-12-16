@extends('layouts.iframe')

@section('title', 'Configurador de Lote')
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('content')

	<link rel="stylesheet" href="/assets/css/configurador.css">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

	<style>
		.table {
			font-family: 'Inter', sans-serif;
			/* Fuente limpia como en la imagen */
			font-size: 0.95rem;
		}

		.table th {
			font-weight: 600;
			color: #333;
		}

		.table td {
			vertical-align: middle;
		}

		.table td,
		.table th {
			border: none !important;
		}

		.table-light {
			font-weight: bold !important;
			--bs-table-bg: #FFFFFF !important;
		}

		.text-primary {
			color: #1a73e8 !important;
			/* Azul tipo Google */
		}

		.fw-semibold {
			font-weight: 600;
		}

		#divloteDescuento,
		#divloteIntereses {
			display: none
		}

		svg:focus,
		svg g:focus,
		svg path:focus,
		svg rect:focus,
		svg polygon:focus {
			outline: none !important;
			box-shadow: none !important;
		}

		.btn-guardar-flotante {
			position: fixed;
			bottom: 30px;
			right: 30px;
			z-index: 1050;
			/* por encima de tooltips o SVG */
			padding: 12px 20px;
			font-weight: 600;
			border-radius: 8px;
			box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
			transition: all 0.2s ease-in-out;
		}

		.btn-guardar-flotante:hover {
			transform: translateY(-2px);
			box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
		}

		/* Cursor siempre como flecha */
		body, html, div, span, p, a, button, input, textarea, svg, g, path, polygon, rect {
			cursor: default !important;
			user-select: none; /* opcional: evita selección de texto si no quieres que se marque */
		}
	</style>

	@if(auth()->check() && auth()->user()->isAdmin())
		<button id="btnGuardarAsientos" class="btn btn-warning btn-guardar-flotante">
			Guardar Asientos
		</button>
	@endif

	<div class="text-center">
		<div style="position: relative; display: inline-block;">

			{{-- Imagen base PNG --}}
			@if ($lot->png_image)
				<img src="{{ asset('/' . $lot->png_image) }}" alt="PNG" style="width:100%; height:auto;">
			@endif

			{{-- SVG encima --}}
			@if ($lot->svg_image)
				<div style="position: absolute; top:0; left:0; width:100%;">
					{!! file_get_contents(public_path($lot->svg_image)) !!}
				</div>
			@endif

			{{-- 🔗 Iconos flotantes --}}
			<div style="position: absolute; top: 10px; left: 10px; display: flex; gap: 8px;">
				@if ($lot->redirect_return)
					<a href="{{ route('lots.iframe', $lot->redirect_return) }}" class="" title="Regresar">
						<img src="{{ asset('assets/controes/Regresar.svg') }}" alt="Regresar" style="height:24px;">
					</a>
				@endif
				@if ($lot->redirect_previous)
					<a href="{{ route('lots.iframe', $lot->redirect_previous) }}" class="" title="Anterior">
						<img src="{{ asset('assets/controes/Anterior.svg') }}" alt="Anterior" style="height:24px;">
					</a>
				@endif
				@if ($lot->redirect_next)
					<a href="{{ route('lots.iframe', $lot->redirect_next) }}" class="" title="Siguiente">
						<img src="{{ asset('assets/controes/Siguiente.svg') }}" alt="Siguiente" style="height:24px;">
					</a>
				@endif
			</div>
		</div>
	</div>


    {{-- 🛒 CARRITO --}}
<div id="cartPanel" style="
    position: fixed;
    top: 80px;
    right: 20px;
    width: 300px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,.15);
    padding: 16px;
    z-index: 2000;
    display: none;
    font-family: 'Poppins', sans-serif;
">
    <h6 style="margin-bottom: 10px; font-weight:600">Boletos seleccionados</h6>

    <ul id="cartItems" style="list-style:none; padding:0; margin:0"></ul>

    <hr>

    <div style="display:flex; justify-content:space-between; font-weight:600">
        <span>Total</span>
        <span id="cartTotal">$0</span>
    </div>

    <button id="btnCheckout" class="btn btn-primary w-100 mt-3" disabled>
        Continuar pago
    </button>
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