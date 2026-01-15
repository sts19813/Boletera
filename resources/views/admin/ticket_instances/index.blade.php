@extends('layouts.app')

@section('title', 'Reimpresión de Boletos')

@section('content')
<div class="card card-flush">
    <div class="card-header">
        <div class="card-title">
            <h3 class="fw-bold">Reimpresión de Boletos</h3>
        </div>
    </div>

    <div class="card-body">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_ticket_instances">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                    <th>Boleto</th>
                    <th>Email</th>
                    <th>Referencia</th>
                    <th>Compra</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($instances as $instance)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $instance->ticket->name }}</div>
                            <div class="text-muted fs-7">
                                {{ optional($instance->ticket->stage)->name }}
                            </div>
                        </td>

                        <td>{{ $instance->email }}</td>

                        <td>
                            <span class="badge badge-light-primary">
                                {{ $instance->payment_intent_id ?? $instance->reference }}
                            </span>
                        </td>

                        <td>
                            {{ optional($instance->purchased_at)->format('d/m/Y H:i') }}
                        </td>

                        <td>
                            @if($instance->used_at)
                                <span class="badge badge-light-danger">Usado</span>
                            @else
                                <span class="badge badge-light-success">Válido</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <a href="{{ route('admin.ticket_instances.reprint', $instance) }}"
                               class="btn btn-sm btn-light-primary">
                                Reimprimir
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection


@push('scripts')
<script>
$(document).ready(function () {
    $('#kt_ticket_instances').DataTable({
        pageLength: 25,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json"
        }
    });
});
</script>
@endpush
