@extends('layouts.app')

@section('title', 'Pago exitoso')

@section('content')
<div class="container py-15 text-center">

    <div class="card card-flush shadow-sm mx-auto" style="max-width: 520px">
        <div class="card-body py-10">

            <div class="mb-6">
                <i class="ki-duotone ki-check-circle fs-3x text-success">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </div>

            <h2 class="fw-bold mb-3">¡Pago confirmado!</h2>

            <p class="text-gray-600 mb-6">
                Tu compra se realizó correctamente.<br>
                Presenta este QR el día del evento.
            </p>

           @if(!empty($qr))
                <div class="text-center mt-5">
                    {!! $qr !!}
                </div>
            @endif


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
@endsection
