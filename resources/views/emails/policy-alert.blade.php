<!doctype html>
<html lang="es">
<body style="margin:0;background:#f7f4ee;color:#183044;font-family:Arial,sans-serif">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 0">
    <tr><td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border-radius:10px;overflow:hidden">
            <tr><td style="background:#a85d19;color:#fff;padding:22px 28px">
                <div style="font-size:20px;font-weight:bold">Alerta automática de póliza</div>
                <div style="margin-top:5px;font-size:12px">Umbral de {{ $alert->threshold_percentage }}% alcanzado</div>
            </td></tr>
            <tr><td style="padding:26px 28px;line-height:1.55">
                <p>La póliza <strong>{{ $alert->policy_number }}</strong> del cliente <strong>{{ $alert->client_name }}</strong> llegó al nivel configurado de atención.</p>
                <ul>
                    <li>Horas contratadas: <strong>{{ number_format((float) $alert->contracted_hours, 2) }} h</strong></li>
                    <li>Horas restantes: <strong>{{ number_format((float) $alert->remaining_hours, 2) }} h</strong></li>
                    <li>Porcentaje disponible: <strong>{{ number_format((float) $alert->remaining_percentage, 2) }}%</strong></li>
                </ul>
                <p>Se recomienda revisar la renovación o ampliación de la póliza antes del siguiente servicio.</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
