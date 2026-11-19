<?php

namespace App\Services;

use App\Models\Minute;
use Dompdf\Dompdf;
use Dompdf\Options;

class MinutePdfGenerator
{
    public function generate(Minute $minute): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('pdf.minute', ['minute' => $minute])->render(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
