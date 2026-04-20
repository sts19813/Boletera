<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $evento->name }} - StomTickets</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:40px 0;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:8px; overflow:hidden;">

                    <tr>
                        <td align="center" style="background:#000; padding:30px 20px;">
                            <img src="{{ $message->embed(public_path('assets/Stom_Tickets_logo.png')) }}" alt="StomTickets"
                                width="200">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:40px;">
                            <h1 style="margin:0 0 18px 0; font-size:26px; color:#111;">Gracias por tu registro</h1>

                            <p style="font-size:15px; color:#555; margin:0 0 10px 0;">
                                Ya estas inscrito al torneo de EAFC 26 patrocinado por Super Willys.
                            </p>

                            <p style="font-size:15px; color:#555; margin:0 0 24px 0;">
                                Unete al grupo de WhatsApp para que te demos mas informacion del evento.
                            </p>

                            <div style="margin:0 0 26px 0;">
                                <a href="{{ $whatsappLink }}"
                                    style="display:inline-block; background:#25D366; color:#fff; text-decoration:none; padding:12px 20px; border-radius:6px; font-weight:bold;">
                                    Unirme al grupo de WhatsApp
                                </a>
                            </div>

                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="background:#f8f9fa; border-radius:6px; margin:8px 0 0 0;">
                                <tr>
                                    <td style="padding:20px; font-size:14px; color:#333; line-height:1.7;">
                                        <strong>Evento:</strong> {{ $evento->name }}<br>
                                        <strong>Nombre:</strong> {{ $registration['full_name'] ?? '-' }}<br>
                                        <strong>Correo:</strong> {{ $registration['email'] ?? '-' }}<br>
                                        <strong>Telefono:</strong> {{ $registration['phone'] ?? '-' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

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
                                (c) {{ date('Y') }} StomTickets. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
