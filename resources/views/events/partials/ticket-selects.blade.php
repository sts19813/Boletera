@php
    $ticketsByType = $tickets
        ->filter(fn($t) => $t->stock > 0)
        ->groupBy('type');
@endphp

<div class="card shadow-sm">
    <div class="card-body">

        {{-- SELECT 1: TIPO --}}
        <div class="mb-5">
            <label class="form-label fw-bold">
                Tipo de boleto
            </label>

            <select id="ticketType" class="form-select">
                <option value="">Selecciona un tipo</option>

                @foreach($ticketsByType as $type => $group)
                    <option value="{{ $type }}">
                        {{ ucfirst($type) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- SELECT 2: ASIENTO / BOLETO --}}
        <div class="mb-5">
            <label class="form-label fw-bold">
                Asiento
            </label>

            <select id="ticketSeat" class="form-select" disabled>
                <option value="">Selecciona un tipo primero</option>
            </select>
        </div>

        <button id="btnAddTicket" class="btn btn-primary w-100 fw-bold" disabled>
            Agregar boleto
        </button>

    </div>
</div>

<script>
    window.ticketsByType = @json($ticketsByType);
</script>