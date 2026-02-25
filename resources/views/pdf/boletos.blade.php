<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .ticket {
            margin-bottom: 0px;
            page-break-inside: avoid;
        }

        .header {
            text-align: center;
            margin-bottom: 0px;
        }

        .event-name {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 0.5px;
            line-height: 1;
            text-transform: uppercase;
        }

        .event-meta {
            font-size: 14px;
            color: #000;
            margin-top: 0px;
        }

        .divider {
            border-top: 1px dashed #aaa;
            margin: 12px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 4px 4px;
            vertical-align: top;
        }

        .label {
            font-size: 15px;
            text-transform: uppercase;
            color: #000;
        }

        .value {
            font-size: 15px;
            font-weight: bold;
        }

        .qr {
            text-align: center;
        }

        .logoStom {
            text-align: center;
            margin-top: 10px;
        }

        .logoStom img {
            width: 250px;
        }

        .qr img {
            width: 340px;
        }

        .promo {
            text-align: center;
            font-size: 13px;
            margin-top: 15px;
        }

        .promo a {
            color: #000;
            font-weight: bold;
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: #000;
            margin-top: 0px;
        }
    </style>
</head>

<body>

    @foreach($boletos as $boleto)

        <div class="ticket">

            {{-- HEADER --}}
            <div class="header">
                <div class="event-name">{{ $boleto['event']['name'] }}</div>
                <div class="event-meta">
                    {{ $boleto['event']['date'] }} · {{ $boleto['event']['time'] }}<br>
                    {{ $boleto['event']['venue'] }}
                </div>
            </div>

            <div class="divider"></div>

            {{-- INFO --}}
            <table>
                <tr>
                    <td width="50%">
                        <div class="label">Boleto</div>
                        <div class="value">{{ $boleto['ticket']['name'] }}</div>
                    </td>
                    <td width="50%">
                        <div class="label">Precio</div>
                        <div class="value">${{ number_format($boleto['ticket']['price'], 2) }} MXN</div>
                    </td>
                </tr>

                <tr>
                    <td>
                        <div class="label">Nombre</div>
                        <div class="value">
                            {{ $boleto['user']['nombre'] ?? '' }}
                         </div>
                    </td>
                    <td>
                        <div class="label">Correo</div>
                        <div class="value">{{ $boleto['user']['email'] }}</div>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>

            {{-- QR --}}
            <div class="qr">
                <img src="{{ public_path(parse_url($boleto['qr'], PHP_URL_PATH)) }}" alt="QR">
            </div>

            <div class="footer">
                Orden: {{ $boleto['order']['payment_intent'] }}<br>
                Presenta este QR el día del evento
            </div>



            <div class="logoStom">
                <img src="{{ public_path('assets/logoInvert.png') }}" alt="Logo">
            </div>
            <div class="promo">
                Organiza tus eventos con
                <a href="https://stomtickets.com" target="_blank">
                    stomtickets.com
                </a>
            </div>

        </div>
    @endforeach

</body>

</html>