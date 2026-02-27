<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Ticket;
use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Services\RegistrationBuilderService;
use App\Services\RegistrationService;
use App\Services\TicketBuilderService;
use App\Services\TicketService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\BoletosMail;

class TaquillaController extends Controller
{
    public function __construct(
        private TicketBuilderService $ticketBuilder,
        private RegistrationBuilderService $registrationBuilder,
        private RegistrationService $registrationService,
        private TicketService $ticketService
    ) {
    }

    public function index()
    {
        $tickets = Ticket::where('status', 'available')->get();
        return view('taquilla.index', compact('tickets'));
    }

    public function sell(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'email' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,cortesia',
            'event_id' => 'required|string',
            'registration' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($request) {

            $paymentInput = $request->input('payment_method');
            $esCortesia = $paymentInput === 'cortesia';
            $paymentMethod = $esCortesia ? 'cash' : $paymentInput;

            $emailInput = $request->input('email');

            $validator = Validator::make(
                ['email' => $emailInput],
                ['email' => 'nullable|email']
            );

            $emailValido = !$validator->fails() && $emailInput;

            $email = $esCortesia
                ? 'CORTESIA'
                : ($emailValido ? $emailInput : 'taquilla@local');


            $reference = 'TAQ-' . now()->format('YmdHis');
            $boletos = [];

            $evento = Eventos::findOrFail($request->input('event_id'));

            foreach ($request->cart as $item) {

                // =============================
                // REGISTRATIONS
                // =============================
                if (($item['type'] ?? null) === 'registration') {

                    $instances = $this->registrationService->create(
                        $evento,
                        [
                            'qty' => $item['qty'] ?? 1,
                            'email' => $email,
                            'nombre' => 'taquilla',
                            'celular' => null,
                            'form_data' => $request->input('registration'),
                            'reference' => $reference,
                            'sale_channel' => 'taquilla',
                            'payment_method' => $paymentMethod,
                            'price' => $item['price'] ?? $evento->price
                        ]
                    );

                    foreach ($instances as $instance) {
                        $boletos[] = $this->registrationBuilder->build(
                            $evento,
                            $instance,
                            $instance->email
                        );
                    }

                    continue;
                }

                // =============================
                // TICKETS (delegado al service)
                // =============================
                $boletos = array_merge(
                    $boletos,
                    $this->ticketService->createFromTaquilla(
                        $evento,
                        $item,
                        $email,
                        $reference,
                        $paymentMethod
                    )
                );

            }


            if ($emailValido) {

                if (
                    $emailValido &&
                    !$esCortesia &&
                    $email !== 'taquilla@local' &&
                    count($boletos) > 0
                ) {
                    $pdfContent = $this->generateBoletosPdf($boletos, $email);

                    Mail::to($email)->send(
                        new BoletosMail($pdfContent, $boletos)
                    );
                }
            }

            return view('pago.success', [
                'boletos' => $boletos,
                'email' => $email,
                'evento' => $evento
            ]);
        });
    }

    private function generateBoletosPdf(array $boletos, string $email)
    {
        return Pdf::loadView('pdf.boletos', [
            'boletos' => $boletos,
            'email' => $email,
        ])->setPaper([0, 0, 400, 700])->output();

    }

    public function pdf(TicketInstance $instance)
    {
        $evento = Eventos::findOrFail($instance->event_id);

        $boleto = $this->ticketBuilder->build(
            ticket: $instance->ticket,
            instance: $instance,
            email: $instance->email ?? 'taquilla@local',
            purchasedAt: $instance->purchased_at,
            event: $evento,
            reference: $instance->reference
        );

        return Pdf::loadView('pdf.boletos', [
            'boletos' => [$boleto],
            'email' => $instance->email,
        ])->download('boleto-taquilla.pdf');
    }
}