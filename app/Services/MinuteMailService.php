<?php

namespace App\Services;

use App\Mail\MinuteServiceMail;
use App\Models\EmailDelivery;
use App\Models\Minute;
use App\Support\ServiceDuration;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MinuteMailService
{
    public function __construct(private readonly MinutePdfGenerator $pdfGenerator) {}

    public function send(Minute $minute): EmailDelivery
    {
        $copies = array_values(array_filter([(string) config('minutes.minute_cc')]));
        $subject = "Minuta de servicio {$minute->folio} - {$minute->client_name}";
        $body = implode("\n", [
            "Estimado/a {$minute->recipient}:",
            '',
            "Folio: {$minute->folio}",
            "Servicio solicitado: {$minute->issue}",
            "Resultado obtenido: {$minute->work_performed}",
            'Tiempo real: '.ServiceDuration::human((int) $minute->elapsed_seconds),
            "Tiempo descontado de la póliza: {$minute->hours_used} h",
            "Tiempo restante de la póliza: {$minute->available_hours} h",
            "Actividad pendiente: {$minute->pending_activity}",
            '',
            'Se adjunta la minuta completa en formato PDF.',
            'Atentamente,',
            'Equipo de Atención Infodata',
        ]);

        $delivery = EmailDelivery::create([
            'minute_id' => $minute->id,
            'policy_number' => $minute->policy_number,
            'type' => 'Minuta terminada',
            'recipients' => [$minute->recipient_email],
            'copies' => $copies,
            'subject' => $subject,
            'body' => $body,
            'has_attachment' => true,
            'status' => 'Pendiente',
        ]);

        try {
            $pdfContents = $this->pdfGenerator->generate($minute);
            $pendingMail = Mail::to($minute->recipient_email);

            if ($copies !== []) {
                $pendingMail->cc($copies);
            }

            $pendingMail->send(new MinuteServiceMail($minute, $pdfContents));

            $simulated = in_array(config('mail.default'), ['array', 'log'], true);
            $delivery->update([
                'status' => $simulated ? 'Simulado' : 'Enviado',
                'sent_at' => now(),
                'error_message' => null,
            ]);
            $minute->update([
                'send_status' => $simulated ? 'Simulada' : 'Enviada',
                'sent_at' => now(),
            ]);
        } catch (Throwable $error) {
            $delivery->update([
                'status' => 'Error',
                'error_message' => $error->getMessage(),
            ]);
            $minute->update(['send_status' => 'Error']);

            throw $error;
        }

        return $delivery->fresh(['minute']);
    }
}
