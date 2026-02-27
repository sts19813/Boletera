@extends('layouts.app')

@section('title', 'Inscripciones')

@section('content')

    <div class="card card-flush">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title">
                <h3 class="fw-bold mb-0">Inscripciones</h3>
            </div>

            <a href="{{ route('admin.registrations.export') }}" class="btn btn-sm btn-success">
                Exportar Excel
            </a>
        </div>

        <div class="card-body">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_registrations">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                        <th>Email</th>
                        <th>Evento</th>
                      
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($instances as $instance)
                        @php $registration = $instance->registration; @endphp
                        <tr>
                            <td>{{ $instance->email }}</td>
                            <td>{{ $instance->evento?->name ?? '—' }}</td>
                            <td>{{ $instance->registered_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end">
                                @if($registration)
                                    <button class="btn btn-sm btn-light-primary btn-view-registration me-2"
                                        data-instance='@json($instance)' data-registration='@json($registration)'>
                                        Ver registro
                                    </button>

                                    <a target="_blank" href="{{ route('admin.registrations.reprint', $instance) }}"
                                        class="btn btn-sm btn-light-primary">
                                        Reimprimir
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    </div>

    <div class="modal fade" id="registrationModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTeam"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-4">
                        <strong>Evento:</strong> <span id="modalEvent"></span><br>
                        <strong>Email:</strong> <span id="modalEmail"></span>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Jugador</th>
                                <th>Email</th>
                                <th>Celular</th>
                                <th>Campo</th>
                                <th>Handicap</th>
                                <th>GHIN</th>
                                <th>Playera</th>
                                <th>Relación Cumbres</th>
                                <th>Capitán</th>
                            </tr>
                        </thead>
                        <tbody id="modalPlayers"></tbody>
                    </table>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function () {
            $('#kt_registrations').DataTable({
                pageLength: 25,
                ordering: false,
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json"
                }
            });
        });
    </script>

    <script>
        $(function () {

            const modal = new bootstrap.Modal(document.getElementById('registrationModal'));

            $('.btn-view-registration').on('click', function () {

                const instance = $(this).data('instance');
                const registration = $(this).data('registration');

                $('#modalEvent').text(instance.evento?.name ?? '—');
                $('#modalEmail').text(instance.email);

                let headers = '';
                let rows = '';
                let teamName = 'Registro';

                let data = null;

                // ================================
                // 1️⃣ MODELO VIEJO
                // ================================
                if (registration.players && registration.players.length > 0) {
                    data = { players: registration.players };
                    teamName = registration.team_name ?? 'Equipo';
                }

                // ================================
                // 2️⃣ MODELO NUEVO JSON
                // ================================
                else if (registration.form_data) {
                    data = registration.form_data;
                }

                if (!data) return;

                /*
                ======================================================
                EVENTO GOLF (players)
                ======================================================
                */
                if (data.players && data.players.length > 0) {

                    headers = `
                    <tr>
                        <th>Jugador</th>
                        <th>Email</th>
                        <th>Celular</th>
                        <th>Campo</th>
                        <th>Handicap</th>
                        <th>GHIN</th>
                        <th>Playera</th>
                        <th>Relación Cumbres</th>
                        <th>Capitán</th>
                    </tr>
                `;

                    data.players.forEach((p, index) => {

                        const cumbres = (p.cumbres && p.cumbres.length)
                            ? p.cumbres.join(', ')
                            : '—';

                        rows += `
                        <tr>
                            <td>${p.name ?? '—'}</td>
                            <td>${p.email ?? '—'}</td>
                            <td>${p.phone ?? '—'}</td>
                            <td>${p.campo ?? '—'}</td>
                            <td>${p.handicap ?? '—'}</td>
                            <td>${p.ghin ?? '—'}</td>
                            <td>${p.shirt ?? '—'}</td>
                            <td>${cumbres}</td>
                            <td>${index === 0 ? 'Sí' : '—'}</td>
                        </tr>
                    `;
                    });

                    teamName = data.team_name ?? teamName;
                }

                /*
                ======================================================
                EVENTO CENA (participants)
                ======================================================
                */
                else if (data.participants && data.participants.length > 0) {

                    headers = `
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Celular</th>
                        <th>Tipo</th>
                        <th>Generación</th>
                    </tr>
                `;

                    data.participants.forEach(p => {

                        rows += `
                        <tr>
                            <td>${p.nombre ?? '—'}</td>
                            <td>${p.email ?? '—'}</td>
                            <td>${p.celular ?? '—'}</td>
                            <td>${p.tipo ?? '—'}</td>
                            <td>${p.generacion ?? '—'}</td>
                        </tr>
                    `;
                    });

                    teamName = 'Invitados';
                }

                $('#modalTeam').text(teamName);
                $('#registrationModal thead').html(headers);
                $('#modalPlayers').html(rows);

                modal.show();
            });

        });
    </script>
@endpush