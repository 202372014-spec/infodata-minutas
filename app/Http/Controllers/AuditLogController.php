<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function index(): JsonResponse
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'date' => $log->created_at->format('d/m/Y H:i:s'),
                'user' => $log->user?->name ?? 'Sin sesión',
                'email' => $log->user?->email ?? ($log->metadata['email'] ?? '—'),
                'action' => $log->action,
                'subject' => $log->subject_type && $log->subject_id
                    ? "{$log->subject_type}:{$log->subject_id}"
                    : '—',
                'ip' => $log->ip_address ?? '—',
            ]);

        return response()->json(['data' => $logs]);
    }
}
