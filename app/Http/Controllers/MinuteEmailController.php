<?php

namespace App\Http\Controllers;

use App\Models\Minute;
use App\Services\AuditLogger;
use App\Services\MinuteMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class MinuteEmailController extends Controller
{
    public function send(
        Request $request,
        Minute $minute,
        MinuteMailService $mailService,
        AuditLogger $audit,
    ): JsonResponse {
        try {
            $delivery = $mailService->send($minute);
        } catch (Throwable $error) {
            $audit->record($request, 'minute.email_failed', 'minute', $minute->folio, [
                'error' => $error->getMessage(),
            ]);

            return response()->json([
                'message' => 'No fue posible enviar el correo. El error quedó registrado para seguimiento.',
            ], 502);
        }

        $audit->record($request, 'minute.email_sent', 'minute', $minute->folio, [
            'deliveryId' => $delivery->id,
            'status' => $delivery->status,
        ]);

        return response()->json([
            'message' => $delivery->status === 'Simulado'
                ? 'El correo, su contenido y el PDF se generaron correctamente en modo de demostración.'
                : 'La minuta y su PDF se enviaron correctamente.',
            'data' => [
                'status' => $delivery->status,
                'sentAt' => $delivery->sent_at?->format('d/m/Y H:i'),
            ],
        ]);
    }
}
