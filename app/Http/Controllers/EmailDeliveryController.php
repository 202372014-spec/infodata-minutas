<?php

namespace App\Http\Controllers;

use App\Models\EmailDelivery;
use Illuminate\Http\JsonResponse;

class EmailDeliveryController extends Controller
{
    public function index(): JsonResponse
    {
        $deliveries = EmailDelivery::query()
            ->with('minute:id,folio,client_name,recipient')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (EmailDelivery $delivery) => [
                'id' => $delivery->id,
                'minute' => $delivery->minute?->folio ?? $delivery->policy_number ?? '—',
                'client' => $delivery->minute?->client_name ?? 'Infodata',
                'recipientName' => $delivery->minute?->recipient,
                'date' => ($delivery->sent_at ?? $delivery->created_at)->format('d/m/Y H:i'),
                'type' => $delivery->type,
                'to' => implode(', ', $delivery->recipients ?? []),
                'cc' => implode(', ', $delivery->copies ?? []),
                'status' => $delivery->status,
                'subject' => $delivery->subject,
                'body' => $delivery->body,
                'error' => $delivery->error_message ?? '',
                'hasAttachment' => $delivery->has_attachment,
                'downloadUrl' => $delivery->minute
                    ? route('minutes.pdf.download', $delivery->minute->folio)
                    : null,
            ]);

        return response()->json(['data' => $deliveries]);
    }
}
