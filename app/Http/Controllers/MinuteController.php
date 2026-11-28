<?php

namespace App\Http\Controllers;

use App\Models\Minute;
use App\Models\SaePolicy;
use App\Models\SaeService;
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
                'source' => 'Base de minutas',
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
        $minute = config('minutes.imported_services')
            ? $this->storeImportedMinute($request)
            : $this->storeLegacyMinute($request);

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

    private function storeImportedMinute(Request $request): Minute
    {
        $validated = $request->validate([
            'sessionId' => ['required', 'integer', 'exists:service_sessions,id'],
            'sourceService' => ['required', 'string', 'max:60', 'unique:minutes,source_service'],
            'used' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,9999.99'],
            'email' => ['nullable', 'email', 'max:180'],
        ]);

        return DB::transaction(function () use ($request, $validated): Minute {
            $session = ServiceSession::query()->lockForUpdate()->findOrFail($validated['sessionId']);
            $this->validateCompletedSession($request, $session, $validated['sourceService']);

            $service = SaeService::query()->available()->find($session->source_service);

            if (! $service) {
                throw ValidationException::withMessages([
                    'sourceService' => ['El servicio seleccionado ya no está disponible para generar una minuta.'],
                ]);
            }

            $email = $validated['email'] ?? $service->recipient_email;

            if (! $email) {
                throw ValidationException::withMessages([
                    'email' => ['Confirma el correo del destinatario antes de generar la minuta.'],
                ]);
            }

            $policy = SaePolicy::query()->lockForUpdate()->findOrFail($service->policy_id);
            $availableBefore = (float) $policy->available_hours;
            $discount = (float) $validated['used'];

            $this->validateDiscount($discount, $availableBefore);

            $remaining = round($availableBefore - $discount, 2);
            $policy->update(['available_hours' => $remaining]);

            return Minute::create([
                'folio' => 'INF-SAE-'.str_pad((string) $session->id, 6, '0', STR_PAD_LEFT),
                'source_service' => $service->id,
                'sae_policy_id' => $policy->id,
                'client_name' => $service->client_name,
                ...$this->serviceTiming($session),
                'hours_used' => $discount,
                'policy_number' => $policy->policy_number,
                'contracted_hours' => $policy->contracted_hours,
                'available_before_hours' => $availableBefore,
                'available_hours' => $remaining,
                'validity' => 'Sin vigencia registrada',
                'adviser' => $service->adviser ?: $session->user->name,
                'reporter' => $service->reporter ?: 'Sin registrar en SAE',
                'recipient' => $service->recipient ?: 'Contacto del cliente',
                'recipient_email' => mb_strtolower($email),
                'service_status' => $service->service_status,
                'send_status' => 'Pendiente',
                'issue' => $service->issue ?: 'Sin registrar en SAE',
                'work_performed' => $service->work ?: 'Sin registrar en SAE',
                'pending_activity' => $service->pending ?: 'Sin registrar en SAE',
            ]);
        }, 3);
    }

    private function storeLegacyMinute(Request $request): Minute
    {
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

        $this->validateCompletedSession($request, $session, $validated['sourceService']);
        $discountedHours = round((float) $validated['used'], 2);
        $availableBefore = round((float) $validated['availableBefore'], 2);
        $this->validateDiscount($discountedHours, $availableBefore);

        return Minute::create([
            'folio' => $validated['id'],
            'source_service' => $validated['sourceService'],
            'client_name' => $validated['client'],
            ...$this->serviceTiming($session),
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
    }

    private function validateCompletedSession(Request $request, ServiceSession $session, string $sourceService): void
    {
        abort_unless(
            $session->user_id === $request->user()->id || $request->user()->role === 'administrator',
            403,
            'Solamente quien inició el servicio o un administrador puede generar su minuta.',
        );

        if ($session->source_service !== $sourceService) {
            throw ValidationException::withMessages([
                'sourceService' => ['El cronómetro no corresponde al servicio seleccionado.'],
            ]);
        }

        if ($session->status !== 'completed' || ! $session->ended_at) {
            throw ValidationException::withMessages([
                'sessionId' => ['Primero debes finalizar el servicio para generar la minuta.'],
            ]);
        }
    }

    private function validateDiscount(float $discount, float $available): void
    {
        if ($discount > $available) {
            throw ValidationException::withMessages([
                'used' => ['El tiempo a descontar no puede superar las horas disponibles de la póliza.'],
            ]);
        }
    }

    private function serviceTiming(ServiceSession $session): array
    {
        return [
            'service_date' => $session->started_at->toDateString(),
            'start_time' => $session->started_at->format('H:i:s'),
            'end_time' => $session->ended_at->format('H:i:s'),
            'elapsed_seconds' => $session->elapsed_seconds,
        ];
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
