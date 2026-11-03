<?php

namespace App\Http\Controllers;

use App\Models\Minute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MinuteController extends Controller
{
    public function index(): View
    {
        return view('prototype', [
            'initialMinutes' => $this->frontendCollection($this->queryMinutes()->get()),
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        $minutes = $this->frontendCollection($this->queryMinutes()->get());

        return response()->json([
            'data' => $minutes,
            'meta' => [
                'count' => $minutes->count(),
                'source' => 'SQLite local',
            ],
        ]);
    }

    public function show(Minute $minute): JsonResponse
    {
        return response()->json(['data' => $this->toFrontend($minute)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:30', 'unique:minutes,folio'],
            'sourceService' => ['nullable', 'string', 'max:60', 'unique:minutes,source_service'],
            'client' => ['required', 'string', 'max:180'],
            'date' => ['required', 'date'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i', 'after:start'],
            'used' => ['required', 'numeric', 'gt:0'],
            'policy' => ['required', 'string', 'max:60'],
            'contracted' => ['required', 'numeric', 'gte:0'],
            'available' => ['required', 'numeric', 'gte:0'],
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

        $minute = Minute::create([
            'folio' => $validated['id'],
            'source_service' => $validated['sourceService'] ?? null,
            'client_name' => $validated['client'],
            'service_date' => $validated['date'],
            'start_time' => $validated['start'],
            'end_time' => $validated['end'],
            'hours_used' => $validated['used'],
            'policy_number' => $validated['policy'],
            'contracted_hours' => $validated['contracted'],
            'available_hours' => $validated['available'],
            'validity' => $validated['validity'],
            'adviser' => $validated['adviser'],
            'reporter' => $validated['reporter'],
            'recipient' => $validated['recipient'],
            'recipient_email' => $validated['email'],
            'service_status' => $validated['serviceStatus'],
            'send_status' => 'Pendiente',
            'issue' => $validated['issue'],
            'work_performed' => $validated['work'],
            'pending_activity' => $validated['pending'] ?? 'Sin actividades pendientes.',
        ]);

        return response()->json([
            'message' => "La minuta {$minute->folio} se guardó en la base local.",
            'data' => $this->toFrontend($minute),
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
        return [
            'id' => $minute->folio,
            'sourceService' => $minute->source_service,
            'client' => $minute->client_name,
            'date' => $minute->service_date->format('d/m/Y'),
            'start' => substr($minute->start_time, 0, 5),
            'end' => substr($minute->end_time, 0, 5),
            'used' => (float) $minute->hours_used,
            'policy' => $minute->policy_number,
            'contracted' => (float) $minute->contracted_hours,
            'available' => (float) $minute->available_hours,
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
        ];
    }
}
