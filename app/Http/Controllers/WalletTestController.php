<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Firebase\JWT\JWT;
use App\Models\TicketInstance;
class WalletTestController extends Controller
{
    public function testWallet(TicketInstance $instance)
    {
        $issuerId = env('GOOGLE_WALLET_ISSUER_ID');

        $serviceAccount = json_decode(
            file_get_contents(storage_path('app/google-wallet.json')),
            true
        );

        $classId = $issuerId . '.evento_siglo21_v5';
        $objectId = $issuerId . '.boleto_' . $instance->id;

        // 🔑 ESTE ES TU QR REAL
        $qrValue = json_encode([
            'ticket_instance_id' => $instance->id,
            'hash' => $instance->qr_hash,
        ]);

        $payload = [
            'iss' => $serviceAccount['client_email'],
            'aud' => 'google',
            'typ' => 'savetowallet',
            'iat' => time(),
            'payload' => [

                // ========= CLASE =========
                'eventTicketClasses' => [
                    [
                        'id' => $classId,
                        'eventCategoryId' => 'EVENT_OTHER',
                        'eventName' => [
                            'defaultValue' => [
                                'language' => 'es-MX',
                                'value' => 'Evento - '
                            ]
                        ],
                        'issuerName' => 'Stom Tickets',
                        'reviewStatus' => 'UNDER_REVIEW',

                        // 🎨 Diseño
                        'hexBackgroundColor' => '#7723FF',
                        'logo' => [
                            'sourceUri' => [
                                'uri' => asset('https://stomtickets.com/cdn/shop/files/Logo-stomtickets-new-white.svg')
                            ],
                            'contentDescription' => [
                                'defaultValue' => [
                                    'language' => 'es-MX',
                                    'value' => 'Stom Tickets'
                                ]
                            ]
                        ],
                        'heroImage' => [
                            'sourceUri' => [
                                'uri' => 'https://stomtickets.com/cdn/shop/files/WhatsApp_Image_2025-12-16_at_3.38.16_PM.jpg'
                            ]
                        ],
                        'linksModuleData' => [
                            'uris' => [
                                [
                                    'uri' => 'https://stomtickets.com',
                                    'description' => ''
                                ]
                            ]
                        ]
                    ]
                ],

                // ========= OBJETO =========
                'eventTicketObjects' => [
                    [
                        'id' => $objectId,
                        'classId' => $classId,
                        'state' => 'ACTIVE',
                        'ticketHolderName' => $instance->email,

                        'barcode' => [
                            'type' => 'QR_CODE',
                            'value' => $qrValue,
                            'alternateText' => 'Presenta este QR en acceso'
                        ],

                        // Texto adicional
                        'textModulesData' => [
                            [
                                'header' => 'Zona',
                                'body' => $instance->ticket->name
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $jwt = JWT::encode(
            $payload,
            $serviceAccount['private_key'],
            'RS256'
        );

        return redirect("https://pay.google.com/gp/v/save/{$jwt}");
    }
}
