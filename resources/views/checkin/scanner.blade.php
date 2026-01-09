@extends('layouts.app')

@section('content')
	<div class="container py-5 text-center">

		<h2 class="fw-bold mb-3">Escanear boletos</h2>
		<p class="text-muted mb-4">Apunta la cámara al QR del boleto</p>

		<div id="reader" style="width:100%; max-width:420px; margin:auto;"></div>
		<div id="result" class="alert mt-4 d-none"></div>

	</div>

	<script src="https://unpkg.com/html5-qrcode"></script>
	<script>
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
					showResult(
						'success',
						'✅ <strong>Acceso permitido</strong><br>' +
						data.email +
						'<br><small>Hora: ' + data.used_at + '</small>'
					);

				} else if (data.status === 'used') {
					showResult(
						'warning',
						'⚠️ <strong>Boleto ya utilizado</strong><br>' +
						'Usado a las: ' + data.used_at
					);
				} else {
					showResult('danger', '❌ ' + data.message);
				}

				await qrScanner.pause();

				// ⏱️ tiempo REAL de lectura
				setTimeout(() => {
					scanningLocked = false;
					qrScanner.resume();
				}, 4000);
			}
		);
	</script>
@endsection