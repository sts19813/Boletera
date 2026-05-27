@extends('layouts.checkin')

@section('content')

	<style>
		.scanner-shell {
			max-width: 960px;
		}

		.scanner-wrapper {
			border: 2px solid #e4e6ef;
			border-radius: 0.75rem;
			padding: 0.75rem;
			background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
			transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
		}

		.scanner-success {
			border-color: #198754;
			box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.15);
			transform: translateY(-2px);
		}

		.scanner-error {
			border-color: #dc3545;
			box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.15);
			transform: translateY(-2px);
		}

		.scanner-warning {
			border-color: #ffc107;
			box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.18);
			transform: translateY(-2px);
		}

		#reader {
			border-radius: 0.5rem;
			overflow: hidden;
		}

		.event-chip {
			display: inline-flex;
			align-items: center;
			gap: 0.35rem;
			padding: 0.35rem 0.75rem;
			border-radius: 999px;
			background: #f1f1f4;
			color: #3f4254;
			font-size: 0.85rem;
			font-weight: 600;
		}
	</style>

	@php
		$statsRouteParams = [];
		if (!empty($selectedEventId)) {
			$statsRouteParams['event_id'] = $selectedEventId;
		}
		$activeEventName = null;
		if (!empty($selectedEventId)) {
			$activeEvent = $events->firstWhere('id', $selectedEventId);
			$activeEventName = $activeEvent?->name;
		}
	@endphp

	<div class="container py-5 scanner-shell">
		<div class="card shadow-sm">
			<div class="card-body p-6 p-lg-8">
				<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4 mb-6">
					<div>
						<h2 class="fw-bold mb-2">Escanear boletos</h2>
						<p class="text-muted mb-0">Apunta la cámara al QR del boleto o registro.</p>
					</div>
					<div class="d-flex flex-wrap gap-2">
						<a href="{{ route('checkin.stats', $statsRouteParams) }}" class="btn btn-sm btn-primary">
							Ver estadísticas
						</a>
						@role('admin')
							<a href="{{ route('admin.checkin_management.index') }}" class="btn btn-sm btn-light-primary">
								Modulo admin checkin
							</a>
						@endrole
					</div>
				</div>

				<div class="row g-4 align-items-end mb-5">
					<div class="col-lg-6">
						<form method="GET" action="{{ url('/checkin') }}">
							<label class="form-label fw-semibold">Evento activo</label>
							<select class="form-select" name="event_id" onchange="this.form.submit()" @disabled(!$canScanAnyEvent)>
								<option value="">Todos mis eventos permitidos</option>
								@foreach($events as $event)
									<option value="{{ $event->id }}" @selected((string) $selectedEventId === (string) $event->id)>
										{{ $event->name }}
									</option>
								@endforeach
							</select>
						</form>
					</div>
					<div class="col-lg-6 text-lg-end">
						@if($activeEventName)
							<span class="event-chip">Evento: {{ $activeEventName }}</span>
						@else
							<span class="event-chip">Filtro: Todos mis eventos permitidos</span>
						@endif
					</div>
				</div>

				@if(!$canScanAnyEvent)
					<div class="alert alert-warning mb-5">
						No tienes eventos asignados para escanear. Solicita a un administrador que te asigne al menos un evento.
					</div>
				@endif

				<div id="scanner-wrapper" class="scanner-wrapper">
					<div id="reader"></div>
				</div>

				<div id="result" class="alert mt-4 d-none"></div>
			</div>
		</div>


		<script src="https://unpkg.com/html5-qrcode"></script>
		<script>
			const selectedEventId = @json($selectedEventId);
			const canScanAnyEvent = @json($canScanAnyEvent);

			const scannerWrapper = document.getElementById('scanner-wrapper');

			function setScannerState(state) {
				scannerWrapper.classList.remove(
					'scanner-success',
					'scanner-error',
					'scanner-warning'
				);

				if (state) {
					scannerWrapper.classList.add('scanner-' + state);
				}
			}
			const resultBox = document.getElementById('result');
			let scanningLocked = false;

			function showResult(type, message) {
				resultBox.className = `alert alert-${type}`;
				resultBox.innerHTML = message;
				resultBox.classList.remove('d-none');
			}

			function safeVibrate(pattern) {
				if (!('vibrate' in navigator)) {
					return;
				}

				try {
					navigator.vibrate(pattern);
				} catch (error) {
					// Fallback silencioso: no rompe el flujo en equipos sin soporte real.
				}
			}

			if (!canScanAnyEvent) {
				setScannerState(null);
				showResult('warning', 'Sin eventos asignados para escanear.');
			} else {
				const qrScanner = new Html5Qrcode("reader");

				qrScanner.start(
					{ facingMode: "environment" },
					{ fps: 10, qrbox: 260 },
					async (decodedText) => {

						if (scanningLocked) return;
						scanningLocked = true;

						let payload;

						try {
							payload = JSON.parse(decodedText);
						} catch {
							setScannerState('error');
							showResult('danger', 'QR invalido');
							safeVibrate([140, 80, 140]);
							scanningLocked = false;
							return;
						}

						if (selectedEventId) {
							payload.event_id = selectedEventId;
						}

						let data;

						try {
							const res = await fetch('/checkin/validate', {
								method: 'POST',
								headers: {
									'Content-Type': 'application/json',
									'X-CSRF-TOKEN': '{{ csrf_token() }}'
								},
								body: JSON.stringify(payload)
							});
							data = await res.json();
						} catch (error) {
							setScannerState('error');
							showResult('danger', 'No fue posible validar el QR. Revisa conexion e intenta de nuevo.');
							safeVibrate([180, 80, 180]);
							scanningLocked = false;
							return;
						}

						if (data.status === 'success') {
							setScannerState('success');
							showResult(
								'success',
								'<strong>Acceso permitido</strong><br>' +
								(data.email ? data.email + '<br>' : '') +
								(data.progress ? '<strong>Progreso:</strong> ' + data.progress + '<br>' : '') +
								'<small>Hora: ' + data.used_at + '</small>'
							);
							safeVibrate(180);
						} else if (data.status === 'used') {
							setScannerState('warning');

							let historyHtml = '';

							if (data.history && data.history.length) {
								historyHtml = '<hr><ul class="list-unstyled mb-0">' +
									data.history.map(h =>
										`<li>${h.numero} - ${h.hora}</li>`
									).join('') +
									'</ul>';
							}

							showResult(
								'warning',
								'<strong>' + data.message + '</strong>' +
								historyHtml
							);
							safeVibrate([90, 90, 90]);
						} else {
							setScannerState('error');
							showResult('danger', data.message ? data.message : 'No fue posible validar el codigo.');
							safeVibrate([160, 90, 160]);
						}

						await qrScanner.pause();

						setTimeout(() => {
							setScannerState(null);
							scanningLocked = false;
							qrScanner.resume();
						}, 2800);
					}
				);
			}
		</script>
@endsection
