<!doctype html>
<html lang="es">
<body style="margin:0;background:#f3f7f8;color:#183044;font-family:Arial,sans-serif">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f7f8;padding:24px 0">
    <tr><td align="center">
        <table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;border-radius:10px;overflow:hidden">
            <tr><td style="background:#0d2d44;color:#fff;padding:22px 28px">
                <div style="font-size:20px;font-weight:bold">i. Infodata</div>
                <div style="margin-top:5px;color:#b8d9dc;font-size:12px">Minuta de servicio {{ $minute->folio }}</div>
            </td></tr>
            <tr><td style="padding:26px 28px;line-height:1.55">
                <p>Estimado/a <strong>{{ $minute->recipient }}</strong>:</p>
                <p>Compartimos el reporte correspondiente al servicio realizado. El PDF completo se encuentra adjunto a este correo.</p>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="margin:18px 0;background:#f3f7f8;border-collapse:collapse">
                    <tr><td style="color:#687b86">Cliente</td><td><strong>{{ $minute->client_name }}</strong></td></tr>
                    <tr><td style="color:#687b86">Servicio solicitado</td><td>{{ $minute->issue }}</td></tr>
                    <tr><td style="color:#687b86">Resultado obtenido</td><td>{{ $minute->work_performed }}</td></tr>
                    <tr><td style="color:#687b86">Tiempo real</td><td><strong>{{ \App\Support\ServiceDuration::human((int) $minute->elapsed_seconds) }}</strong></td></tr>
                    <tr><td style="color:#687b86">Tiempo descontado</td><td><strong>{{ number_format((float) $minute->hours_used, 2) }} h</strong></td></tr>
                    <tr><td style="color:#687b86">Tiempo restante de póliza</td><td><strong>{{ number_format((float) $minute->available_hours, 2) }} h</strong></td></tr>
                    <tr><td style="color:#687b86">Actividad pendiente</td><td>{{ $minute->pending_activity ?: 'Sin actividades pendientes.' }}</td></tr>
                </table>
                <p>Quedamos a sus órdenes para cualquier aclaración.</p>
                <p style="margin-bottom:0">Atentamente,<br><strong>Equipo de Atención Infodata</strong></p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
