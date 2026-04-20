@extends('layouts.app')

@section('title', 'Inscripciones')

@section('content')

    <div class="card-header d-flex align-items-center">

        <div class="card-title">
            <h3 class="fw-bold mb-0">Inscripciones</h3>
        </div>

        <div class="ms-auto d-flex gap-2 flex-wrap">
            @foreach($events as $event)
                <a href="{{ route('admin.registrations.export', $event->id) }}" class="btn btn-sm btn-success">
                    Exportar {{ $event->name }}
                </a>
            @endforeach
        </div>

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
                @foreach($sales as $sale)
                    <tr>
                        <td>{{ $sale['email'] }}</td>
                        <td>{{ $sale['event'] }}</td>
                        <td>{{ optional($sale['date'])->format('d/m/Y H:i') }}</td>
                        <td class="text-end">

                            @if($sale['type'] === 'registration')
                                <button class="btn btn-sm btn-light-primary btn-view-registration me-2"
                                    data-instance='@json($sale["model"])'>
                                    Ver registro
                                </button>

                                <a target="_blank" href="{{ route('admin.registrations.reprint', $sale['model']) }}"
                                    class="btn btn-sm btn-light-primary">
                                    Reimprimir
                                </a>
                            @endif

                            @if($sale['type'] === 'ticket')
                                <a target="_blank" href="{{ route('admin.ticket_instances.reprint', $sale['model']) }}"
                                    class="btn btn-sm btn-light-primary">
                                    Reimprimir
                                </a>
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
                                <th>Relacion Cumbres</th>
                                <th>Capitan</th>
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

                $('#modalEvent').text(instance.evento?.name ?? '-');
                $('#modalEmail').text(instance.email);

                let headers = '';
                let rows = '';
                let teamName = 'Registro';

                const data = instance.form_data ?? null;
                teamName = data?.team_name ?? instance.team_name ?? teamName;
                if (data?.full_name) {
                    teamName = data.full_name;
                }

                if (!data) return;

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
                            <th>Relacion Cumbres</th>
                            <th>Capitan</th>
                        </tr>
                    `;

                    data.players.forEach((p, index) => {

                        const cumbres = Array.isArray(p.cumbres)
                            ? p.cumbres.join(', ')
                            : (p.cumbres ?? '-');

                        const isCaptain = p.is_captain !== undefined
                            ? Boolean(p.is_captain)
                            : index === 0;

                        rows += `
                            <tr>
                                <td>${p.name ?? '-'}</td>
                                <td>${p.email ?? '-'}</td>
                                <td>${p.phone ?? '-'}</td>
                                <td>${p.campo ?? '-'}</td>
                                <td>${p.handicap ?? '-'}</td>
                                <td>${p.ghin ?? '-'}</td>
                                <td>${p.shirt ?? '-'}</td>
                                <td>${cumbres}</td>
                                <td>${isCaptain ? 'Si' : '-'}</td>
                            </tr>
                        `;
                    });
                }

                else if (data.participants && data.participants.length > 0) {

                    headers = `
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Celular</th>
                            <th>Tipo</th>
                            <th>Generacion</th>
                        </tr>
                    `;

                    data.participants.forEach(p => {

                        rows += `
                            <tr>
                                <td>${p.nombre ?? '-'}</td>
                                <td>${p.email ?? '-'}</td>
                                <td>${p.celular ?? '-'}</td>
                                <td>${p.tipo ?? '-'}</td>
                                <td>${p.generacion ?? '-'}</td>
                            </tr>
                        `;
                    });

                    teamName = 'Invitados';
                }

                else {
                    headers = `
                        <tr>
                            <th>Campo</th>
                            <th>Valor</th>
                        </tr>
                    `;

                    const labels = {
                        full_name: 'Nombre completo',
                        age: 'Edad',
                        city: 'Ciudad',
                        state: 'Estado',
                        phone: 'Telefono',
                        email: 'Correo electronico',
                        game_id: 'ID del juego',
                        console: 'Consola',
                        participated_before: 'Participo antes',
                        participation_count: 'Cuantas veces',
                        how_known_label: 'Como nos conocio',
                        how_known: 'Como nos conocio',
                        stream_user: 'Usuario Twitch/YouTube',
                        receipt_file_url: 'Recibo',
                        reference: 'Referencia',
                    };

                    const ignoredKeys = ['template_form', 'receipt_file_path'];

                    Object.entries(data).forEach(([key, value]) => {
                        if (ignoredKeys.includes(key) || value === null || value === '') {
                            return;
                        }

                        const label = labels[key] ?? key;
                        let displayValue = value;

                        if (Array.isArray(value)) {
                            displayValue = value.join(', ');
                        } else if (typeof value === 'object') {
                            displayValue = JSON.stringify(value);
                        }

                        if (key === 'receipt_file_url' && typeof displayValue === 'string') {
                            rows += `
                                <tr>
                                    <td>${label}</td>
                                    <td><a href="${displayValue}" target="_blank" rel="noopener">Ver archivo</a></td>
                                </tr>
                            `;
                            return;
                        }

                        rows += `
                            <tr>
                                <td>${label}</td>
                                <td>${displayValue}</td>
                            </tr>
                        `;
                    });
                }

                $('#modalTeam').text(teamName);
                $('#registrationModal thead').html(headers);
                $('#modalPlayers').html(rows);

                modal.show();
            });

        });
    </script>
@endpush
