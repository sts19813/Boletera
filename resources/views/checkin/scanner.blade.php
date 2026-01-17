@extends('layouts.checkin')

@section('content')

	<style>
		.scanner-wrapper {
			border: 6px solid #dee2e6;
			border-radius: 12px;
			padding: 8px;
			transition: border-color 0.3s ease, box-shadow 0.3s ease;
		}

		.scanner-success {
			border-color: #198754;
			box-shadow: 0 0 20px rgba(25, 135, 84, 0.6);
		}

		.scanner-error {
			border-color: #dc3545;
			box-shadow: 0 0 20px rgba(220, 53, 69, 0.6);
		}

		.scanner-warning {
			border-color: #ffc107;
			box-shadow: 0 0 20px rgba(255, 193, 7, 0.6);
		}
	</style>

	<div class="container py-5 text-center">

		<h2 class="fw-bold mb-3">Escanear boletos</h2>
		<div class="d-flex justify-content-end mb-3">
			<a href="{{ route('checkin.stats') }}" class="btn btn-sm btn-primary">
				📊 Ver estadísticas
			</a>
		</div>

		<p class="text-muted mb-4">Apunta la cámara al QR del boleto</p>

		<div id="scanner-wrapper" class="scanner-wrapper">
			<div id="reader"></div>
		</div>

		<div id="result" class="alert mt-4 d-none"></div>

	</div>

	<script src="https://unpkg.com/html5-qrcode"></script>
	<script>

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

		const qrScanner = new Html5Qrcode("reader");

		qrScanner.start(
			{ facingMode: "environment" },
			{ fps: 10, qrbox: 250 },
			async (decodedText) => {

				if (scanningLocked) return;
				scanningLocked = true;

				let payload;

				try {
					payload = JSON.parse(decodedText);
				} catch {
					showResult('danger', 'QR inválido');
					scanningLocked = false;
					return;
				}

				const res = await fetch('/checkin/validate', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': '{{ csrf_token() }}'
					},
					body: JSON.stringify(payload)
				});

				const data = await res.json();

				if (data.status === 'success') {
					setScannerState('success');
					showResult(
						'success',
						'✅ <strong>Acceso permitido</strong><br>' +
						(data.email ? data.email + '<br>' : '') +
						(data.progress ? '<strong>Progreso:</strong> ' + data.progress + '<br>' : '') +
						'<small>Hora: ' + data.used_at + '</small>'
					);
					if (navigator.vibrate) navigator.vibrate(200);

				} else if (data.status === 'used') {

					setScannerState('warning');



					let historyHtml = '';

					if (data.history && data.history.length) {
						historyHtml = '<hr><ul class="list-unstyled mb-0">' +
							data.history.map(h =>
								`<li>✔ ${h.numero} — ${h.hora}</li>`
							).join('') +
							'</ul>';
					}

					showResult(
						'warning',
						'⚠️ <strong>' + data.message + '</strong>' +
						historyHtml
					);

				} else {

					setScannerState('error');


					showResult('danger', '❌ ' + data.message);
				}


				await qrScanner.pause();

				// ⏱️ tiempo REAL de lectura
				setTimeout(() => {
					setScannerState(null);       // apaga color
					scanningLocked = false;
					qrScanner.resume();
				}, 3000);
			}
		);
	</script>
@endsection