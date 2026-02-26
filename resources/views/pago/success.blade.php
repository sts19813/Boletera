@extends('layouts.iframe')

@section('title', 'Pago exitoso')

@section('content')
	<x-event-header :evento="$evento" />
	<div class="container py-15 text-center" style="margin-top: -130px">

		<div class="card card-flush shadow-sm mx-auto" style="max-width: 520px">
			<div class="card-body py-10">

				<div class="mb-6">
					<i class="ki-duotone ki-check-circle fs-3x text-success">
						<span class="path1"></span>
						<span class="path2"></span>
					</i>
				</div>

				<h2 class="fw-bold mb-3">¡Pago confirmado!</h2>


				@if(!auth()->check() || !auth()->user()->is_admin)
					<button onclick="downloadTicketsPDF()" class="btn btn-light-primary fw-bold mb-6">
						<i class="ki-duotone ki-download fs-5 me-2"></i>
						Descargar boletos en PDF
					</button>
				@endif


				@if(auth()->check() && auth()->user()->is_admin)
					@php
						$reference = $boletos[0]['order']['payment_intent'] ?? null;
					@endphp

					@if($reference)
						<a href="{{ route('boletos.reprint', ['ref' => $reference]) }}" class="btn btn-light-primary fw-bold mb-6"
							target="_blank" id="btnAutoPrint">
							<i class="ki-duotone ki-printer fs-5 me-2"></i>
							Imprimir boletos
						</a>
					@endif
				@endif

				{{-- BOTONES GOOGLE WALLET --}}
				<div class="mt-6 no-print d-none">

					<h4 class="fw-bold mb-4">Agregar boletos a Google Wallet</h4>

					@foreach ($boletos as $boleto)
						@if(!empty($boleto['wallet']['instance_id']))
							<a href="{{ route('wallet.add', $boleto['wallet']['instance_id']) }}" target="_blank"
								class="btn btn-dark fw-bold d-inline-flex align-items-center gap-2 px-4 py-2 mb-6">
								<i class="bi bi-wallet2 fs-5"></i>
								Agregar a Google Wallet {{ $boleto['ticket']['name'] }}
							</a>
						@endif
					@endforeach

				</div>

				<p class="text-gray-600 mb-6">
					Tu compra se realizó correctamente.<br>
					Presenta este QR el día del evento.
				</p>

				@foreach ($boletos as $index => $boleto)
					<div class="card card-flush shadow-sm mb-10 printable-ticket">
						<div class="card-body">
							<h3 class="fw-bold mb-1">{{ $boleto['event']['name'] }}</h3>
							<div class="text-muted mb-4">
								{{ $boleto['event']['date'] }} · {{ $boleto['event']['time'] }}
							</div>

							<div class="row mb-6 text-start">
								<div class="col-6">
									<div class="fw-semibold">Boleto</div>
									<div class="text-muted">{{ $boleto['ticket']['name'] }}</div>
								</div>
								<div class="col-6">
									<div class="fw-semibold">Precio</div>
									<div class="text-muted">${{ number_format($boleto['ticket']['price'], 2) }}</div>
								</div>
							</div>

							<div class="row mb-6 text-start">
								<div class="col-6">
									<div class="fw-semibold">Comprador</div>
									<div class="text-muted">{{ $boleto['user']['email'] }}</div>
								</div>
								<div class="col-6">
									<div class="fw-semibold">Lugar</div>
									<div class="text-muted">{{ $boleto['event']['venue'] }}</div>
								</div>
							</div>

							<div class="text-center my-6">
								<img src="{{ $boleto['qr'] }}" width="200">
							</div>

							<div class="text-muted text-center fs-8">
								Orden: {{ $boleto['order']['payment_intent'] ?? 'N/A' }}
							</div>

						</div>
					</div>
				@endforeach


				<div class="text-gray-700 mb-8">
					Correo del comprador:<br>
					<strong>{{ $email }}</strong>
				</div>

				<a href="{{ url('/') }}" class="btn btn-primary fw-bold">
					Volver al inicio
				</a>

			</div>
		</div>

	</div>


	<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
	<script>
		async function downloadTicketsPDF() {

			const { jsPDF } = window.jspdf;

			const tickets = document.querySelectorAll('.printable-ticket');

			if (!tickets.length) {
				alert('No se encontraron boletos');
				return;
			}

			const pdf = new jsPDF({
				orientation: 'portrait',
				unit: 'px',
				format: 'a4'
			});

			for (let i = 0; i < tickets.length; i++) {

				const ticket = tickets[i];

				// Captura el card
				const canvas = await html2canvas(ticket, {
					scale: 2,
					backgroundColor: '#ffffff',
					useCORS: true
				});

				const imgData = canvas.toDataURL('image/png');

				const pageWidth = pdf.internal.pageSize.getWidth();
				const pageHeight = pdf.internal.pageSize.getHeight();

				const imgWidth = pageWidth - 40;
				const imgHeight = (canvas.height * imgWidth) / canvas.width;

				if (i > 0) {
					pdf.addPage();
				}

				pdf.addImage(
					imgData,
					'PNG',
					20,
					20,
					imgWidth,
					imgHeight
				);
			}

			pdf.save('boletos.pdf');
		}
	</script>

	{{-- Si es admin y hay referencia, autoimprimir
	@if(auth()->check() && auth()->user()->is_admin && !empty($reference))
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const btn = document.getElementById('btnAutoPrint');
			if (btn) {
				btn.click();
			}
		});
	</script>
	@endif
	--}}
@endsection