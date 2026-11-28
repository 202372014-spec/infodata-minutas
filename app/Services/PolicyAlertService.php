<?php

namespace App\Services;

use App\Mail\PolicyThresholdAlertMail;
use App\Models\EmailDelivery;
use App\Models\Minute;
use App\Models\PolicyAlert;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PolicyAlertService
{
    public function sendIfRequired(Minute $minute): ?PolicyAlert
    {
        $contracted = (float) $minute->contracted_hours;

        if ($contracted <= 0) {
            return null;
        }

        $percentage = round(((float) $minute->available_hours / $contracted) * 100, 2);
        $threshold = (int) config('minutes.policy_alert_threshold', 10);

        if ($percentage > $threshold) {
            return null;
        }

        $recipients = config('minutes.policy_alert_recipients', []);
        $state = [
            'policy_number' => $minute->policy_number,
            'client_name' => $minute->client_name,
            'contracted_hours' => $contracted,
            'remaining_hours' => (float) $minute->available_hours,
            'remaining_percentage' => $percentage,
            'recipients' => $recipients,
            'status' => 'Pendiente',
            'sent_at' => null,
            'error_message' => null,
        ];
        $alert = PolicyAlert::query()->firstOrCreate([
            'policy_identity' => $minute->sae_policy_id ?? hash('sha256', $minute->policy_number),
            'threshold_percentage' => $threshold,
        ], $state);

        if (! $alert->wasRecentlyCreated) {
            $retry = PolicyAlert::query()->whereKey($alert->id)->where('status', 'Error')
                ->update(['status' => 'Pendiente']);

            if (! $retry) {
                return $alert->refresh();
            }

            $alert->update($state);
        }

        $subject = "Alerta: póliza {$minute->policy_number} con {$percentage}% disponible";
        $body = implode("\n", [
            'Alerta automática de póliza',
            "Cliente: {$minute->client_name}",
            "Póliza: {$minute->policy_number}",
            "Horas contratadas: {$contracted} h",
            "Horas restantes: {$minute->available_hours} h",
            "Porcentaje disponible: {$percentage}%",
        ]);

        $delivery = EmailDelivery::create([
            'minute_id' => $minute->id,
            'policy_number' => $minute->policy_number,
            'type' => 'Alerta de póliza',
            'recipients' => $recipients,
            'copies' => [],
            'subject' => $subject,
            'body' => $body,
            'has_attachment' => false,
            'status' => 'Pendiente',
        ]);

        try {
            Mail::to($recipients)->send(new PolicyThresholdAlertMail($alert));
            $status = in_array(config('mail.default'), ['array', 'log'], true)
                ? 'Simulado'
                : 'Enviado';

            $alert->update([
                'status' => $status,
                'sent_at' => now(),
            ]);
            $delivery->update([
                'status' => $status,
                'sent_at' => now(),
            ]);
        } catch (Throwable $error) {
            $alert->update([
                'status' => 'Error',
                'error_message' => $error->getMessage(),
            ]);
            $delivery->update([
                'status' => 'Error',
                'error_message' => $error->getMessage(),
            ]);
        }

        return $alert->fresh();
    }
}
