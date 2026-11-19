<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Minuta {{ $minute->folio }}</title>
    <style>
        @page { margin: 28px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #183044; font-family: "DejaVu Sans", sans-serif; font-size: 10px; }
        .header { background: #0d2d44; color: #fff; padding: 24px 28px; }
        .header table { width: 100%; border-collapse: collapse; }
        .brand { font-size: 22px; font-weight: bold; }
        .brand small { display: block; margin-top: 4px; color: #b8d9dc; font-size: 9px; font-weight: normal; }
        .title { text-align: right; }
        .title span { display: block; color: #9fd4cf; font-size: 8px; letter-spacing: 1px; text-transform: uppercase; }
        .title strong { display: block; margin-top: 5px; font-size: 18px; }
        .folio { margin-top: 5px; font-size: 11px; }
        .content { padding: 20px 28px 10px; }
        .time-table, .data-table { width: 100%; border-collapse: collapse; }
        .time-table td { width: 16.66%; padding: 10px 8px; background: #f1f6f7; border-right: 2px solid #fff; vertical-align: top; }
        .time-table span, .data-table span { display: block; margin-bottom: 4px; color: #70818d; font-size: 7px; letter-spacing: .4px; text-transform: uppercase; }
        .time-table strong { font-size: 10px; }
        .time-table .highlight { background: #dff3ef; }
        h2 { margin: 17px 0 8px; color: #54717d; font-size: 9px; letter-spacing: .5px; text-transform: uppercase; }
        .data-box { padding: 12px 14px; background: #f4f8f9; border-radius: 6px; }
        .data-table td { width: 33.33%; padding: 5px 8px; vertical-align: top; }
        .data-table strong { font-size: 9px; }
        .block { padding: 11px 13px; background: #f4f8f9; border-left: 3px solid #15939a; line-height: 1.5; }
        .block.issue { border-color: #d76868; }
        .block.pending { border-color: #e3a247; }
        .signatures { margin-top: 34px; width: 100%; border-collapse: collapse; }
        .signatures td { width: 50%; padding: 0 35px; text-align: center; }
        .signature-line { padding-top: 7px; border-top: 1px solid #8a9aa3; color: #657985; }
        .footer { position: fixed; right: 28px; bottom: 15px; left: 28px; padding-top: 7px; border-top: 1px solid #dbe3e6; color: #7c8c95; font-size: 7px; }
        .footer-right { float: right; }
    </style>
</head>
<body>
    <header class="header">
        <table>
            <tr>
                <td class="brand">i. Infodata<small>Soluciones de tecnología empresarial</small></td>
                <td class="title"><span>Documento de servicio</span><strong>Minuta de servicio</strong><div class="folio">{{ $minute->folio }}</div></td>
            </tr>
        </table>
    </header>

    <main class="content">
        <table class="time-table">
            <tr>
                <td><span>Fecha</span><strong>{{ $minute->service_date->format('d/m/Y') }}</strong></td>
                <td><span>Hora inicio</span><strong>{{ substr($minute->start_time, 0, 5) }}</strong></td>
                <td><span>Hora fin</span><strong>{{ substr($minute->end_time, 0, 5) }}</strong></td>
                <td><span>Tiempo real</span><strong>{{ \App\Support\ServiceDuration::human((int) $minute->elapsed_seconds) }}</strong></td>
                <td class="highlight"><span>Descuento de póliza</span><strong>{{ number_format((float) $minute->hours_used, 2) }} h</strong></td>
                <td><span>Estatus</span><strong>{{ $minute->service_status }}</strong></td>
            </tr>
        </table>

        <h2>Datos del cliente y póliza</h2>
        <div class="data-box">
            <table class="data-table">
                <tr>
                    <td><span>Cliente</span><strong>{{ $minute->client_name }}</strong></td>
                    <td><span>Póliza</span><strong>{{ $minute->policy_number }}</strong></td>
                    <td><span>Vigencia</span><strong>{{ $minute->validity }}</strong></td>
                </tr>
                <tr>
                    <td><span>Horas contratadas</span><strong>{{ number_format((float) $minute->contracted_hours, 2) }} h</strong></td>
                    <td><span>Disponibles antes</span><strong>{{ number_format((float) $minute->available_before_hours, 2) }} h</strong></td>
                    <td><span>Tiempo restante</span><strong>{{ number_format((float) $minute->available_hours, 2) }} h</strong></td>
                </tr>
                <tr>
                    <td><span>Consultor</span><strong>{{ $minute->adviser }}</strong></td>
                    <td><span>Servicio origen</span><strong>{{ $minute->source_service ?: '—' }}</strong></td>
                    <td><span>Porcentaje restante</span><strong>{{ $minute->contracted_hours > 0 ? number_format(((float) $minute->available_hours / (float) $minute->contracted_hours) * 100, 2) : '0.00' }}%</strong></td>
                </tr>
            </table>
        </div>

        <h2>Participantes</h2>
        <div class="data-box">
            <table class="data-table"><tr>
                <td><span>Quién reporta</span><strong>{{ $minute->reporter }}</strong></td>
                <td><span>Quién recibe</span><strong>{{ $minute->recipient }}</strong></td>
                <td><span>Correo</span><strong>{{ $minute->recipient_email }}</strong></td>
            </tr></table>
        </div>

        <h2>Servicio solicitado</h2>
        <div class="block issue">{{ $minute->issue }}</div>

        <h2>Resultado obtenido / servicio realizado</h2>
        <div class="block">{{ $minute->work_performed }}</div>

        <h2>Actividad pendiente</h2>
        <div class="block pending">{{ $minute->pending_activity ?: 'Sin actividades pendientes.' }}</div>

        <table class="signatures"><tr>
            <td><div class="signature-line">Firma del cliente</div></td>
            <td><div class="signature-line">Firma del asesor</div></td>
        </tr></table>
    </main>

    <footer class="footer">
        Documento generado automáticamente por el Sistema de Minutas Infodata.
        <span class="footer-right">{{ $minute->folio }}</span>
    </footer>
</body>
</html>
