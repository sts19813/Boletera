<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $evento['name'] }} - StomTickets</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:40px 0;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:8px; overflow:hidden;">

                    <!-- HEADER -->
                    <tr>
                        <td align="center" style="background:#000; padding:30px 20px;">
                            <img src="{{ $message->embed(public_path('assets/Stom_Tickets_logo.png')) }}" alt="StomTickets"
                                width="200">
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding:40px;">

                            <h1 style="margin:0 0 10px 0; font-size:24px; color:#111;">
                                {{ $evento['name'] }}
                            </h1>

                            <p style="font-size:15px; color:#555; margin:0 0 20px 0;">
                                Tu compra fue confirmada exitosamente.
                                Adjuntamos tus boletos en formato PDF.
                            </p>

                            <!-- RESUMEN EVENTO -->
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="background:#f8f9fa; border-radius:6px; margin:25px 0;">
                                <tr>
                                    <td style="padding:20px; font-size:14px; color:#333; line-height:1.6;">
                                        <strong>Detalles del evento</strong><br><br>

                                        📅 <strong>Fecha:</strong> {{ $evento['date'] }}<br>
                                        ⏰ <strong>Hora:</strong> {{ $evento['time'] }}<br>
                                        📍 <strong>Lugar:</strong> {{ $evento['venue'] }}<br><br>

                                         <strong>Nombre:</strong> {{ $boletos[0]['user']['nombre'] }}<br>
                                         <strong>Correo:</strong> {{ $boletos[0]['user']['email'] }}<br>
                                         <strong>Orden:</strong>
                                        {{ $boletos[0]['order']['payment_intent'] ?? $boletos[0]['order']['reference'] }}
                                    </td>
                                </tr>
                            </table>

                            <!-- BOLETOS -->
                            <h3 style="margin:30px 0 15px 0; font-size:16px; color:#111;">
                                Resumen de boletos
                            </h3>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                @foreach($boletos as $boleto)
                                    <tr>
                                        <td style="padding:12px 0; border-bottom:1px solid #eee; font-size:14px;">
                                            <strong>{{ $boleto['ticket']['name'] }}</strong><br>
                                            Precio: ${{ number_format($boleto['ticket']['price'], 2) }} MXN
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <p style="margin-top:30px; font-size:14px; color:#444;">
                                Presenta el código QR incluido en el PDF el día del evento.
                            </p>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td align="center" style="padding:30px; background:#fafafa; border-top:1px solid #eee;">

                            <p style="font-size:13px; color:#777; margin:0 0 8px 0;">
                                Organiza tus eventos con
                                <a href="https://www.stomtickets.com/realiza-tu-evento"
                                    style="color:#000; font-weight:bold; text-decoration:none;">
                                    stomtickets.com
                                </a>
                            </p>

                            <p style="font-size:12px; color:#aaa; margin:0;">
                                © {{ date('Y') }} StomTickets. Todos los derechos reservados.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>