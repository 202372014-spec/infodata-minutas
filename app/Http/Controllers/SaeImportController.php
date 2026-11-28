<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Output\BufferedOutput;

class SaeImportController extends Controller
{
    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'extensions:json', 'mimetypes:application/json,text/plain'],
        ]);

        $output = new BufferedOutput;
        $status = Artisan::call('sae:import', [
            'file' => $request->file('file')->getPathname(),
            '--json' => true,
        ], $output);

        if ($status !== 0) {
            throw ValidationException::withMessages([
                'file' => ['El archivo no contiene un lote válido de servicios de SAE.'],
            ]);
        }

        $counts = json_decode($output->fetch(), true, 512, JSON_THROW_ON_ERROR);
        $audit->record($request, 'sae.services_imported', 'sae_import', metadata: $counts);

        return response()->json(['message' => 'Servicios importados correctamente.', 'data' => $counts]);
    }
}
