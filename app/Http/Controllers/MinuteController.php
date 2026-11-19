<?php

namespace App\Http\Controllers;

use App\Models\Minute;
use App\Models\ServiceSession;
use App\Services\AuditLogger;
use App\Services\PolicyAlertService;
use App\Support\ServiceDuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MinuteController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('prototype', [
            'initialMinutes' => $user
                ? $this->frontendCollection($this->queryMinutes()->get())
                : collect(),
            'initialUser' => $user?->frontendProfile(),
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        $minutes = $this->frontendCollection($this->queryMinutes()->get());

        return response()->json([
            'data' => $minutes,
            'meta' => [
                'count' => $minutes->count(),
                'source' => 'SQLite de demostración',
            ],
        ]);
    }

    public function show(Request $request, Minute $minute, AuditLogger $audit): JsonResponse
    {
        $audit->record($request, 'minute.viewed', 'minute', $minute->folio);

        return response()->json(['data' => $this->toFrontend($minute)]);
    }

    public function store(
        Request $request,
        AuditLogger $audit,
        PolicyAlertService $alertService,
    ): JsonResponse {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:30', 'unique:minutes,folio'],
            'sessionId' => ['required', 'integer', 'exists:service_sessions,id'],
            'sourceService' => ['required', 'string', 'max:60', 'unique:minutes,source_service'],
            'client' => ['required', 'string', 'max:180'],
            'used' => ['required', 'numeric', 'gt:0'],
            'policy' => ['required', 'string', 'max:60'],
            'contracted' => ['required', 'numeric', 'gt:0'],
            'availableBefore' => ['required', 'numeric', 'gte:0'],
            'validity' => ['required', 'string', 'max:30'],
            'adviser' => ['required', 'string', 'max:150'],
            'reporter' => ['required', 'string', 'max:150'],
            'recipient' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180'],
            'serviceStatus' => ['required', 'string', 'max:60'],
            'issue' => ['required', 'string'],
            'work' => ['required', 'string'],
            'pending' => ['nullable', 'string'],
        ]);

        $session = ServiceSession::query()->findOrFail($validated['sessionId']);

        if ($session->source_service !== $validated['sourceService']) {
            throw ValidationException::withMessages([
                'sourceService' => ['El cronómetro no corresponde al servicio seleccionado.'],
            ]);
        }

        if ($session->status !== 'completed' || ! $session->ended_at) {
            throw ValidationException::withMessages([
                'sessionId' => ['Primero debes finalizar el servicio para generar la minuta.'],
            ]);
        }

        $discountedHours = round((float) $validated['used'], 2);
        $availableBefore = round((float) $validated['availableBefore'], 2);

        if ($discountedHours > $availableBefore) {
            throw ValidationException::withMessages([
                'used' => ['El tiempo a descontar no puede superar las horas disponibles de la póliza.'],
            ]);
        }

        $minute = DB::transaction(function () use (
            $validated,
            $session,
            $discountedHours,
            $availableBefore,
        ): Minute {
            return Minute::create([
                'folio' => $validated['id'],
                'source_service' => $validated['sourceService'],
                'client_name' => $validated['client'],
                'service_date' => $session->started_at->toDateString(),
                'start_time' => $session->started_at->format('H:i:s'),
                'end_time' => $session->ended_at->format('H:i:s'),
                'elapsed_seconds' => $session->elapsed_seconds,
                'hours_used' => $discountedHours,
                'policy_number' => mb_strtoupper($validated['policy']),
                'contracted_hours' => $validated['contracted'],
                'available_before_hours' => $availableBefore,
                'available_hours' => round($availableBefore - $discountedHours, 2),
                'validity' => $validated['validity'],
                'adviser' => $validated['adviser'],
                'reporter' => $validated['reporter'],
                'recipient' => $validated['recipient'],
                'recipient_email' => mb_strtolower($validated['email']),
                'service_status' => $validated['serviceStatus'],
                'send_status' => 'Pendiente',
                'issue' => $validated['issue'],
                'work_performed' => $validated['work'],
                'pending_activity' => $validated['pending'] ?? 'Sin actividades pendientes.',
            ]);
        });

        $alert = $alertService->sendIfRequired($minute);

        $audit->record($request, 'minute.created', 'minute', $minute->folio, [
            'sourceService' => $minute->source_service,
            'client' => $minute->client_name,
            'elapsedSeconds' => $minute->elapsed_seconds,
            'discountedHours' => (float) $minute->hours_used,
            'remainingHours' => (float) $minute->available_hours,
            'policyAlertStatus' => $alert?->status,
        ]);

        return response()->json([
            'message' => "La minuta {$minute->folio} se guardó con el tiempo real y el descuento de póliza separados.",
            'data' => $this->toFrontend($minute),
            'alert' => $alert ? [
                'policy' => $alert->policy_number,
                'percentage' => (float) $alert->remaining_percentage,
                'status' => $alert->status,
            ] : null,
        ], 201);
    }

    private function queryMinutes()
    {
        return Minute::query()
            ->orderByDesc('service_date')
            ->orderByDesc('start_time')
            ->orderByDesc('id');
    }

    private function frontendCollection(Collection $minutes): Collection
    {
        return $minutes->map(fn (Minute $minute) => $this->toFrontend($minute))->values();
    }

    private function toFrontend(Minute $minute): array
    {
        $contracted = (float) $minute->contracted_hours;
        $remaining = (float) $minute->available_hours;

        return [
            'id' => $minute->folio,
            'sourceService' => $minute->source_service,
            'client' => $minute->client_name,
            'date' => $minute->service_date->format('d/m/Y'),
            'start' => substr($minute->start_time, 0, 5),
            'end' => substr($minute->end_time, 0, 5),
            'actualSeconds' => (int) $minute->elapsed_seconds,
            'actualDuration' => ServiceDuration::human((int) $minute->elapsed_seconds),
            'used' => (float) $minute->hours_used,
            'discountedHours' => (float) $minute->hours_used,
            'policy' => $minute->policy_number,
            'contracted' => $contracted,
            'availableBefore' => (float) ($minute->available_before_hours ?? $remaining),
            'available' => $remaining,
            'remainingPercentage' => $contracted > 0
                ? round(($remaining / $contracted) * 100, 2)
                : 0,
            'validity' => $minute->validity,
            'adviser' => $minute->adviser,
            'reporter' => $minute->reporter,
            'recipient' => $minute->recipient,
            'email' => $minute->recipient_email,
            'serviceStatus' => $minute->service_status,
            'sendStatus' => $minute->send_status,
            'sentAt' => $minute->sent_at?->format('d/m/Y H:i') ?? '—',
            'issue' => $minute->issue,
            'work' => $minute->work_performed,
            'pending' => $minute->pending_activity ?: 'Sin actividades pendientes.',
            'pdfUrl' => route('minutes.pdf.download', $minute->folio),
        ];
    }
}
