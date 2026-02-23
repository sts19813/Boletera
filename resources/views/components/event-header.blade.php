<section class="event-header position-relative">
    <div class="container">
        <div class="d-flex justify-content-between align-items-start flex-wrap">

            {{-- INFO EVENTO --}}
            <div class="event-info">
                <img src="{{ asset('assets/logo.svg') }}" alt="Stom Tickets" class="event-logo mb-6"
                    style="width:180px;">

                <h1 class="event-title">
                    {{ $evento->name }}
                </h1>

                <div class="event-meta">
                    @if($evento->hora_inicio && $evento->hora_fin)
                        <div>
                            <i class="ki-duotone ki-time fs-5 me-2"></i>
                            {{ \Carbon\Carbon::parse($evento->hora_inicio)->format('g:i A') }} -
                            {{ \Carbon\Carbon::parse($evento->hora_fin)->format('g:i A') }}
                        </div>
                    @endif

                    @if($evento->location)
                        <div>
                            <i class="ki-duotone ki-geolocation fs-5 me-2"></i>
                            {{ $evento->location }}
                        </div>
                    @endif

                    @if($evento->description)
                        <div style="max-width: 950px">
                            <i class="ki-duotone ki-information fs-5 me-2"></i>
                            {{ $evento->description }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- FECHA --}}
            @if($evento->event_date)
                <div class="event-date">
                    <span class="day">{{ $evento->event_date->format('d') }}</span>
                    <span class="month">{{ strtoupper($evento->event_date->format('M')) }}</span>
                </div>
            @endif

        </div>
    </div>
</section>