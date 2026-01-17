@extends('layouts.checkin')

@section('title', 'Estadísticas de Check-in')

@section('content')
<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">📊 Estadísticas de Check-in</h2>

        <a href="/checkin" class="btn btn-light-primary">
            ← Volver al escáner
        </a>
    </div>

    <div class="row g-4">

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $total }}</div>
                    <div class="text-muted">Boletos generados</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold text-success">{{ $scanned }}</div>
                    <div class="text-muted">Boletos escaneados</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold text-warning">{{ $courtesyScanned }}</div>
                    <div class="text-muted">Cortesías escaneadas</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold text-danger">{{ $pending }}</div>
                    <div class="text-muted">Faltan por escanear</div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
