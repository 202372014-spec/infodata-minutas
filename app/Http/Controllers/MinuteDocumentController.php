<?php

namespace App\Http\Controllers;

use App\Models\Minute;
use App\Services\AuditLogger;
use App\Services\MinutePdfGenerator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MinuteDocumentController extends Controller
{
    public function download(
        Request $request,
        Minute $minute,
        MinutePdfGenerator $pdfGenerator,
        AuditLogger $audit,
    ): Response {
        $audit->record($request, 'minute.pdf_downloaded', 'minute', $minute->folio);

        return response($pdfGenerator->generate($minute), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$minute->folio}.pdf\"",
        ]);
    }
}
