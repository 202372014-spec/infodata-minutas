<?php

namespace App\Http\Controllers;

use App\Models\SaePolicy;
use App\Models\SaeService;
use App\Models\ServiceSession;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $available = SaeService::query()->available()->with('policy')->where(function ($services): void {
            $services->whereHas('policy', fn ($policy) => $policy->where('available_hours', '>', 0))
                ->orWhereHas('session', fn ($session) => $session->where('status', 'running'));
        });

        if ($user->role !== 'administrator') {
            $available->whereDoesntHave('session', fn ($sessions) => $sessions->where('user_id', '!=', $user->id));
        }

        return response()->json([
            'data' => $available->orderByDesc('source_date')->orderBy('source_folio')->get()
                ->map(fn (SaeService $service) => $service->frontendProfile()),
        ]);
    }

    public function show(Request $request, string $sourceService): JsonResponse
    {
        $this->validateService($sourceService);

        $session = ServiceSession::query()
            ->with('user:id,name')
            ->where('source_service', $sourceService)
            ->first();

        if ($session && config('minutes.imported_services')) {
            $this->authorizeSession($request, $session);
        }

        return response()->json([
            'data' => $session?->frontendProfile(),
        ]);
    }

    public function start(Request $request, string $sourceService, AuditLogger $audit): JsonResponse
    {
        $this->validateService($sourceService);

        $session = $this->startSession($request, $sourceService)->load('user:id,name');

        if (config('minutes.imported_services')) {
            $this->authorizeSession($request, $session);
        }

        if ($session->status === 'completed') {
            return response()->json([
                'message' => 'Este servicio ya fue finalizado y está listo para generar su minuta.',
                'data' => $session->frontendProfile(),
            ], 409);
        }

        if (! $session->wasRecentlyCreated) {
            return response()->json([
                'message' => 'El cronómetro del servicio ya se encuentra activo.',
                'data' => $session->frontendProfile(),
            ]);
        }

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
        $this->validateService($sourceService);

        $session = ServiceSession::query()
            ->with('user:id,name')
            ->where('source_service', $sourceService)
            ->firstOrFail();

        $this->authorizeSession($request, $session);

        $endedAt = now();
        $elapsed = max(1, (int) $session->started_at->diffInSeconds($endedAt));
        $stopped = ServiceSession::query()->whereKey($session->id)->where('status', 'running')->update([
            'ended_at' => $endedAt,
            'elapsed_seconds' => $elapsed,
            'status' => 'completed',
        ]);

        $session->refresh();

        if (! $stopped) {
            return response()->json([
                'message' => 'El servicio ya se encontraba finalizado.',
                'data' => $session->frontendProfile(),
            ]);
        }

        $audit->record($request, 'service.stopped', 'service_session', $session->id, [
            'sourceService' => $sourceService,
            'elapsedSeconds' => $session->elapsed_seconds,
        ]);

        return response()->json([
            'message' => 'Servicio finalizado. Ya puedes indicar el tiempo que se descontará de la póliza.',
            'data' => $session->frontendProfile(),
        ]);
    }

    private function validateService(string $sourceService): void
    {
        abort_if(mb_strlen($sourceService) > 60, 422, 'El identificador del servicio no es válido.');

        if (config('minutes.imported_services')) {
            abort_unless(SaeService::query()->available()->whereKey($sourceService)->exists(), 404, 'Servicio no disponible.');
        }
    }

    private function startSession(Request $request, string $sourceService): ServiceSession
    {
        return DB::transaction(function () use ($request, $sourceService): ServiceSession {
            if (config('minutes.imported_services')) {
                $service = SaeService::query()->available()->findOrFail($sourceService);
                $policy = SaePolicy::query()->lockForUpdate()->findOrFail($service->policy_id);

                if ((float) $policy->available_hours <= 0) {
                    $session = ServiceSession::query()->where('source_service', $sourceService)->first();
                    abort_unless($session, 422, 'La póliza del servicio ya no tiene horas disponibles.');

                    return $session;
                }
            }

            return ServiceSession::query()->firstOrCreate(
                ['source_service' => $sourceService],
                ['user_id' => $request->user()->id, 'started_at' => now(), 'status' => 'running'],
            );
        }, 3);
    }

    private function authorizeSession(Request $request, ServiceSession $session): void
    {
        abort_unless(
            $session->user_id === $request->user()->id || $request->user()->role === 'administrator',
            403,
            'Solamente quien inició el servicio o un administrador puede modificar su cronómetro.',
        );
    }
}
