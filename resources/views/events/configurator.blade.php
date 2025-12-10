@extends('layouts.app')

@section('title', 'Configurador de Lote')

@section('content')

	<link rel="stylesheet" href="/assets/css/configurador.css">

	<style>
		{{ $lot->modal_selector ?? 'svg g *' }}
		{
			fill: transparent !important;
			stroke: #00aeef;
			stroke-miterlimit: 10;
			cursor: pointer;
			transition: fill 0.3s ease;
		}

		{{ $lot->modal_selector ?? 'svg g *' }}:hover {
			fill: rgb(0, 200, 0) !important;
		}
	</style>

	<div class="card shadow-sm">
		<div class="card-header">
			<h3 class="card-title">Configurador: {{ $lot->name }}</h3>
			<div class="card-toolbar">
				<a href="{{ route('admin.index') }}" class="btn btn-secondary">Regresar</a>
			</div>
		</div>
		<div class="card-body text-center">
			<div style="position: relative; display: inline-block;">
				@if ($lot->png_image)
					<img src="{{ asset('/' . $lot->png_image) }}" alt="PNG" style="width:900px; height:auto;">
				@endif

				@if ($lot->svg_image)
					<div style="position: absolute; top:0; left:0; width:100%;">
						{!! file_get_contents(public_path($lot->svg_image)) !!}
					</div>
				@endif
			</div>
		</div>
	</div>

	<!-- Modal -->
	<div class="modal fade" id="polygonModal" tabindex="-1" aria-labelledby="polygonModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-xl">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="polygonModalLabel">Elemento seleccionado</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
				</div>
				<div class="modal-body">
					<p>Elemento seleccionado: <strong id="selectedElementId"></strong></p>
					<form id="polygonForm">
						@csrf
						<input type="hidden" id="polygonId" name="polygonId">

						{{-- Solo select de lote --}}
						<div class="row g-3 mb-4">
							<div class="col-md-12 mb-3">
								<label for="lot_id" class="form-label fw-bold">Lote</label>
								<select id="modal_lot_id" name="lot_id" class="form-select form-select-solid">
									<option value="">Seleccione un lote...</option>
								</select>
							</div>
						</div>

						{{-- Información adicional del polígono --}}
						<div class="form-check mb-3">
							<input class="form-check-input" type="checkbox" value="1" id="redirect" name="redirect">
							<label class="form-check-label" for="redirect">
								¿Aplicar redirección?
							</label>
						</div>

						<div class="mb-3">
							<label for="redirect_url" class="form-label">Redirigir a desarrollo:</label>
							<select id="redirect_url" name="redirect_url" class="form-select form-select-solid" disabled>
								<option value="">Seleccione un desarrollo...</option>
								@foreach ($desarrollos as $desarrollo)
									<option value="{{ $desarrollo->id }}">{{ $desarrollo->name }}</option>
								@endforeach
							</select>
						</div>

						<div class="row mb-3">
							<div class="col-md-6">
								<label for="color" class="form-label">Color</label>
								<input type="color" id="color" name="color" class="form-control form-control-color"
									value="#34c759ff" disabled>
							</div>
							<div class="col-md-6">
								<label for="color_active" class="form-label">Color Activo (hover)</label>
								<input type="color" id="color_active" name="color_active"
									class="form-control form-control-color" value="#2c7be5ff" disabled>
							</div>
						</div>
						<div class="d-flex justify-content-end">
							<button type="submit" class="btn btn-primary">Guardar</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		let selector = @json($lot->modal_selector ?? 'svg g *');
		window.Laravel = {
			csrfToken: "{{ csrf_token() }}",
			routes: {
				lotsFetch: "{{ route('lots.fetch') }}",
				lotesStore: "{{ route('lotes.store') }}"
			}
		};

		window.preloadedLots = @json($lots);
		window.currentLot = @json($lot);
		window.dbLotes = @json($dbLotes);

		window.projects = @json($projects);
		window.idDesarrollo = {{ $lot->id }};

		let redireccion = false;
	</script>

	<script src="/assets/js/lotes.js"></script>
	<script src="/assets/js/iframePublico/Mainiframe.js"></script>
@endpush