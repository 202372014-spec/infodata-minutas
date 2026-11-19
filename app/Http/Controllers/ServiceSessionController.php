<?php

namespace App\Http\Controllers;

use App\Models\ServiceSession;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceSessionController extends Controller
{
    public function show(string $sourceService): JsonResponse
    {
        $session = ServiceSession::query()
            ->with('user:id,name')
            ->where('source_service', $sourceService)
            ->first();

        return response()->json([
            'data' => $session?->frontendProfile(),
        ]);
    }

    public function start(Request $request, string $sourceService, AuditLogger $audit): JsonResponse
    {
        abort_if(mb_strlen($sourceService) > 60, 422, 'El identificador del servicio no es válido.');

        $existing = ServiceSession::query()
            ->with('user:id,name')
            ->where('source_service', $sourceService)
            ->first();

        if ($existing?->status === 'completed') {
            return response()->json([
                'message' => 'Este servicio ya fue finalizado y está listo para generar su minuta.',
                'data' => $existing->frontendProfile(),
            ], 409);
        }

        if ($existing) {
            return response()->json([
                'message' => 'El cronómetro del servicio ya se encuentra activo.',
                'data' => $existing->frontendProfile(),
            ]);
        }

        $session = ServiceSession::create([
            'source_service' => $sourceService,
            'user_id' => $request->user()->id,
            'started_at' => now(),
            'status' => 'running',
        ])->load('user:id,name');

        $audit->record($request, 'service.started', 'service_session', $session->id, [
            'sourceService' => $sourceService,
        ]);

        return response()->json([
            'message' => 'Servicio iniciado. El cronómetro seguirá activo aunque recargues la página.',
            'data' => $session->frontendProfile(),
        ], 201);
    }

    public function stop(Request $request, string $sourceService, AuditLogger $audit): JsonResponse
    {
        $session = ServiceSession::query()
            ->with('user:id,name')
            ->where('source_service', $sourceService)
            ->firstOrFail();

        if ($session->status === 'completed') {
            return response()->json([
                'message' => 'El servicio ya se encontraba finalizado.',
                'data' => $session->frontendProfile(),
            ]);
        }

        abort_unless(
            $session->user_id === $request->user()->id || $request->user()->role === 'administrator',
            403,
            'Solamente quien inició el servicio o un administrador puede finalizarlo.',
        );

        $endedAt = now();
        $session->update([
            'ended_at' => $endedAt,
            'elapsed_seconds' => max(1, (int) $session->started_at->diffInSeconds($endedAt)),
            'status' => 'completed',
        ]);

        $audit->record($request, 'service.stopped', 'service_session', $session->id, [
            'sourceService' => $sourceService,
            'elapsedSeconds' => $session->elapsed_seconds,
        ]);

        return response()->json([
            'message' => 'Servicio finalizado. Ya puedes indicar el tiempo que se descontará de la póliza.',
            'data' => $session->fresh('user:id,name')->frontendProfile(),
        ]);
    }
}
