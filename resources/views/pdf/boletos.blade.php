<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        .ticket {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
        }
        .muted {
            color: #666;
        }
        .qr {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

@foreach($boletos as $boleto)
    <div class="ticket">

        <div class="title">{{ $boleto['event']['name'] }}</div>
        <div class="muted">
            {{ $boleto['event']['date'] }} · {{ $boleto['event']['time'] }}
        </div>

        <hr>

        <p>
            <strong>Boleto:</strong> {{ $boleto['ticket']['name'] }}<br>
            <strong>Precio:</strong> ${{ number_format($boleto['ticket']['price'], 2) }} MXN<br>
            <strong>Asiento:</strong>
            {{ $boleto['ticket']['row'] ?? 'General' }}
            {{ $boleto['ticket']['seat'] }}
        </p>

        <p>
            <strong>Comprador:</strong> {{ $boleto['user']['email'] }}<br>
            <strong>Orden:</strong> {{ $boleto['order']['payment_intent'] }}
        </p>

        <div class="qr">
          <img src="{{ public_path(parse_url($boleto['qr'], PHP_URL_PATH)) }}" width="160">

        
        </div>

    </div>
@endforeach

</body>
</html>
