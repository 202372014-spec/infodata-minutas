<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Infodata | Sistema de Minutas</title>
    <meta name="description" content="Prototipo navegable del sistema de gestión y envío de minutas de servicio de Infodata.">
    <script>
        window.__MINUTES__ = @json($initialMinutes ?? []);
        window.__CURRENT_USER__ = @json($initialUser ?? null);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <svg class="svg-sprite" aria-hidden="true">
        <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></symbol>
        <symbol id="i-file" viewBox="0 0 24 24"><path d="M6 2h8l4 4v16H6z"/><path d="M14 2v5h5M9 12h6M9 16h6"/></symbol>
        <symbol id="i-mail" viewBox="0 0 24 24"><rect x="2.5" y="5" width="19" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></symbol>
        <symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6 1.7 1.7 0 0 0 10 3v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></symbol>
        <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></symbol>
        <symbol id="i-refresh" viewBox="0 0 24 24"><path d="M20 7v5h-5M4 17v-5h5"/><path d="M6.1 8A7 7 0 0 1 18.5 6L20 8M4 16l1.5 2A7 7 0 0 0 17.9 16"/></symbol>
        <symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></symbol>
        <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
        <symbol id="i-x" viewBox="0 0 24 24"><path d="m5 5 14 14M19 5 5 19"/></symbol>
        <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></symbol>
        <symbol id="i-send" viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></symbol>
        <symbol id="i-download" viewBox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
        <symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 3 2 21h20Z"/><path d="M12 9v5M12 18h.01"/></symbol>
        <symbol id="i-database" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v7c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12v7c0 1.7 3.6 3 8 3s8-1.3 8-3v-7"/></symbol>
        <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 22c0-4.4 3.6-8 8-8s8 3.6 8 8"/></symbol>
        <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
        <symbol id="i-logout" viewBox="0 0 24 24"><path d="M10 4H4v16h6M14 8l4 4-4 4M18 12H9"/></symbol>
        <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    </svg>

    <section class="login-screen" id="loginScreen">
        <div class="login-visual">
            <div class="login-brand"><span class="brand-mark">i.</span><span>infodata</span></div>
            <div class="login-copy">
                <span class="eyebrow light">Gestión de servicios</span>
                <h1>Minutas claras.<br>Seguimiento oportuno.</h1>
                <p>Consulta los servicios registrados en Aspel SAE, documenta la atención y conserva la evidencia de cada envío.</p>
                <div class="login-features">
                    <span><svg class="icon"><use href="#i-check"/></svg> Consulta de solo lectura</span>
                    <span><svg class="icon"><use href="#i-check"/></svg> Trazabilidad de correos</span>
                    <span><svg class="icon"><use href="#i-check"/></svg> Alertas de horas disponibles</span>
                </div>
            </div>
            <p class="login-foot">Prototipo de estancia profesional · 2026</p>
        </div>
        <div class="login-form-wrap">
            <form class="login-card" id="loginForm">
                <div class="mobile-brand"><span class="brand-mark">i.</span><span>infodata</span></div>
                <span class="eyebrow">Acceso al sistema</span>
                <h2>Bienvenido</h2>
                <p class="muted">Ingresa con tu cuenta para consultar y gestionar minutas.</p>
                <label>Correo electrónico<input id="loginEmail" type="email" value="jonatan@infodata.mx" required></label>
                <label>Contraseña
                    <div class="password-field"><input id="loginPassword" type="password" value="Prototipo2026" required><button type="button" id="togglePassword" aria-label="Mostrar contraseña"><svg class="icon"><use href="#i-eye"/></svg></button></div>
                </label>
                <div class="login-options"><label class="check-label"><input type="checkbox" checked> Recordarme</label><button type="button" class="link-button">¿Olvidaste tu contraseña?</button></div>
                <button class="button primary wide" type="submit">Entrar a la demostración</button>
                <p class="login-error is-hidden" id="loginError" role="alert"></p>
                <p class="demo-note"><span></span> Base local de demostración; no se envían datos reales.</p>
            </form>
        </div>
    </section>

    <div class="app-shell is-hidden" id="appShell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand"><span class="brand-mark">i.</span><div><strong>infodata</strong><small>Sistema de Minutas</small></div></div>
            <nav class="main-nav" aria-label="Navegación principal">
                <button class="nav-item is-active" data-view="dashboard"><svg class="icon"><use href="#i-grid"/></svg><span>Resumen</span></button>
                <button class="nav-item" data-view="minutas"><svg class="icon"><use href="#i-file"/></svg><span>Minutas</span><b class="nav-count" id="navMinuteCount">0</b></button>
                <button class="nav-item" data-view="correos"><svg class="icon"><use href="#i-mail"/></svg><span>Correos enviados</span></button>
                <button class="nav-item" data-view="configuracion" data-admin-only><svg class="icon"><use href="#i-settings"/></svg><span>Configuración</span></button>
            </nav>
            <div class="connection-card"><div class="status-dot"></div><div><strong>Aspel SAE</strong><small>Modo demostración</small></div></div>
            <div class="sidebar-user"><span class="avatar" data-user-initials>JS</span><div><strong data-user-name>Jonatan Salazar</strong><small data-user-role>Administrador</small></div><button id="logoutButton" aria-label="Cerrar sesión"><svg class="icon"><use href="#i-logout"/></svg></button></div>
        </aside>

        <div class="workspace">
            <header class="topbar">
                <button class="icon-button mobile-menu" id="menuButton" aria-label="Abrir menú"><svg class="icon"><use href="#i-menu"/></svg></button>
                <div><strong id="topbarTitle">Resumen general</strong><small id="topbarDate">Demostración local</small></div>
                <div class="topbar-actions"><span class="demo-chip">Base SQLite activa</span><button class="icon-button" aria-label="Notificaciones"><svg class="icon"><use href="#i-bell"/></svg><i></i></button><span class="avatar" data-user-initials>JS</span></div>
            </header>

            <main class="main-content">
                <section class="view is-active" id="view-dashboard" data-title="Resumen general">
                    <div class="page-heading"><div><span class="eyebrow">Panel de control</span><h1>Buen día, <span id="greetingName">Jonatan</span></h1><p>Este es el estado general de las minutas y pólizas de servicio.</p></div><div class="heading-actions"><button class="button secondary" data-action="refresh"><svg class="icon"><use href="#i-refresh"/></svg>Actualizar información</button><button class="button primary" data-action="new-minute"><svg class="icon"><use href="#i-plus"/></svg>Nueva minuta</button></div></div>
                    <div class="metric-grid">
                        <article class="metric-card teal"><div class="metric-icon"><svg class="icon"><use href="#i-file"/></svg></div><div><span>Total de minutas</span><strong id="metricTotal">0</strong><small>Registros en SQLite</small></div></article>
                        <article class="metric-card green"><div class="metric-icon"><svg class="icon"><use href="#i-check"/></svg></div><div><span>Minutas enviadas</span><strong id="metricSent">0</strong><small id="metricSentRate">0% del total</small></div></article>
                        <article class="metric-card orange"><div class="metric-icon"><svg class="icon"><use href="#i-clock"/></svg></div><div><span>Pendientes de envío</span><strong id="metricPending">0</strong><small>Requieren seguimiento</small></div></article>
                        <article class="metric-card red"><div class="metric-icon"><svg class="icon"><use href="#i-alert"/></svg></div><div><span>Pólizas con ≤10%</span><strong id="metricCritical">0</strong><small>Alerta calculada</small></div></article>
                    </div>
                    <div class="dashboard-grid">
                        <article class="panel activity-panel"><div class="panel-title"><div><h2>Actividad de minutas</h2><p>Envíos realizados durante los últimos siete días</p></div><span class="legend"><i></i> Enviadas</span></div><div class="chart" id="activityChart"></div></article>
                        <article class="panel alert-panel"><div class="panel-title"><div><h2>Atención prioritaria</h2><p>Pólizas con pocas horas</p></div><button class="link-button" data-go="minutas">Ver todas</button></div><div id="policyAlerts"></div></article>
                    </div>
                    <article class="panel"><div class="panel-title"><div><h2>Minutas recientes</h2><p>Últimos servicios consultados</p></div><button class="link-button" data-go="minutas">Consultar minutas →</button></div><div class="table-wrap"><table><thead><tr><th>Folio</th><th>Cliente</th><th>Servicio</th><th>Póliza</th><th>Envío</th><th>Acción</th></tr></thead><tbody id="recentMinutes"></tbody></table></div></article>
                </section>

                <section class="view" id="view-minutas" data-title="Minutas de servicio">
                    <div class="page-heading"><div><span class="eyebrow">Consulta y seguimiento</span><h1>Minutas de servicio</h1><p>Registros guardados en la base local; la consulta a Aspel SAE continúa simulada.</p></div><div class="heading-actions"><button class="button secondary" data-action="refresh"><svg class="icon"><use href="#i-refresh"/></svg>Consultar base</button><button class="button primary" data-action="new-minute"><svg class="icon"><use href="#i-plus"/></svg>Generar nueva minuta</button></div></div>
                    <div class="summary-strip"><div><span>Resultados</span><strong id="minuteCount">0</strong></div><div><span>Enviadas</span><strong class="text-green" id="summarySent">0</strong></div><div><span>Pendientes</span><strong class="text-orange" id="summaryPending">0</strong></div><div><span>Con error</span><strong class="text-red" id="summaryError">0</strong></div></div>
                    <article class="panel table-panel">
                        <div class="table-tools"><div class="search-field"><svg class="icon"><use href="#i-search"/></svg><input id="minuteSearch" type="search" placeholder="Buscar por folio, cliente o asesor"></div><div class="filter-row"><select id="statusFilter"><option value="all">Todos los envíos</option><option value="Enviada">Enviadas</option><option value="Pendiente">Pendientes</option><option value="Error">Con error</option></select><select id="policyFilter"><option value="all">Todas las pólizas</option><option value="critical">≤10% disponible</option><option value="active">Vigentes</option></select></div></div>
                        <div class="table-wrap minutes-table"><table><thead><tr><th>Folio</th><th>Cliente</th><th>Fecha</th><th>Asesor</th><th>Horas disponibles</th><th>Servicio</th><th>Envío</th><th class="actions-cell">Acciones</th></tr></thead><tbody id="minutesTable"></tbody></table></div>
                        <div class="table-footer"><span id="tableStatus">Consultando la base local</span><div class="pagination"><button disabled>←</button><button class="is-current">1</button><button disabled>→</button></div></div>
                    </article>
                </section>

                <section class="view" id="view-correos" data-title="Correos enviados">
                    <div class="page-heading"><div><span class="eyebrow">Trazabilidad</span><h1>Correos enviados</h1><p>Historial de envíos automáticos, reenvíos y alertas.</p></div><button class="button secondary" data-action="refresh"><svg class="icon"><use href="#i-refresh"/></svg>Actualizar historial</button></div>
                    <div class="mail-layout"><article class="panel mail-list-panel"><div class="table-tools"><div class="search-field"><svg class="icon"><use href="#i-search"/></svg><input id="mailSearch" type="search" placeholder="Buscar minuta o destinatario"></div><select id="mailStatusFilter"><option value="all">Todos</option><option value="Enviado">Enviados</option><option value="Pendiente">Pendientes</option><option value="Error">Errores</option></select></div><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Minuta / cliente</th><th>Tipo</th><th>Estatus</th><th></th></tr></thead><tbody id="mailTable"></tbody></table></div></article><aside class="panel mail-preview" id="mailPreview"></aside></div>
                </section>

                <section class="view" id="view-configuracion" data-title="Configuración">
                    <div class="page-heading"><div><span class="eyebrow">Administración</span><h1>Configuración</h1><p>Parámetros del sistema, automatizaciones y conexión de datos.</p></div><span class="role-chip"><svg class="icon"><use href="#i-user"/></svg>Solo administradores</span></div>
                    <div class="settings-layout">
                        <nav class="settings-nav" id="settingsNav">
                            <button class="is-active" data-setting="general">Datos generales</button><button data-setting="mail">Correos y destinatarios</button><button data-setting="templates">Plantillas</button><button data-setting="automation">Automatizaciones</button><button data-setting="sae">Conexión con SAE</button><button data-setting="users">Usuarios y permisos</button><button data-setting="audit">Auditoría</button>
                        </nav>
                        <article class="panel settings-content" id="settingsContent"></article>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div class="modal-backdrop is-hidden" id="minuteModal" role="dialog" aria-modal="true" aria-labelledby="minuteModalTitle"><div class="modal document-modal"><div class="modal-head"><div><span class="eyebrow">Vista previa del documento</span><h2 id="minuteModalTitle">Minuta</h2></div><div><button class="button secondary" data-action="download"><svg class="icon"><use href="#i-download"/></svg>Descargar</button><button class="icon-button" data-close="minuteModal" aria-label="Cerrar"><svg class="icon"><use href="#i-x"/></svg></button></div></div><div id="minuteDocument"></div></div></div>
    <div class="modal-backdrop is-hidden" id="resendModal" role="dialog" aria-modal="true" aria-labelledby="resendTitle"><div class="modal confirm-modal"><div class="confirm-icon"><svg class="icon"><use href="#i-send"/></svg></div><h2 id="resendTitle">¿Deseas reenviar esta minuta?</h2><p>La acción quedará registrada en la bitácora de correos.</p><div class="confirm-data" id="resendData"></div><div class="modal-actions"><button class="button secondary" data-close="resendModal">Cancelar</button><button class="button primary" id="confirmResend">Confirmar reenvío</button></div></div></div>
    <div class="modal-backdrop is-hidden" id="newMinuteModal" role="dialog" aria-modal="true" aria-labelledby="newMinuteTitle">
        <div class="modal form-modal">
            <div class="modal-head"><div><span class="eyebrow">Automatización con Aspel SAE</span><h2 id="newMinuteTitle">Generar minuta automática</h2></div><button class="icon-button" data-close="newMinuteModal" aria-label="Cerrar"><svg class="icon"><use href="#i-x"/></svg></button></div>
            <form id="newMinuteForm">
                <div class="form-modal-body">
                    <div class="creation-note success"><span class="status-dot"></span><div><strong>Base local preparada</strong><span>Los servicios disponibles simulan la consulta de solo lectura a Aspel SAE. La minuta generada sí se guardará en SQLite.</span></div></div>

                    <section class="form-section">
                        <div class="form-section-title"><span>01</span><div><h3>Servicio concluido detectado</h3><p>Selecciona un registro; el sistema recuperará todos sus datos automáticamente.</p></div></div>
                        <label class="form-field service-picker">Servicio disponible en Aspel SAE<select id="saeServiceSelect" name="sourceService"><option value="SAE-SRV-4832">SAE-SRV-4832 · Industrias Demo Norte · 03/10/2026</option><option value="SAE-SRV-4831">SAE-SRV-4831 · Distribuidora Ejemplo Central · 03/10/2026</option><option value="SAE-SRV-4829">SAE-SRV-4829 · Hotel Demostración del Valle · 02/10/2026</option></select><small>Los servicios son demostrativos; la minuta resultante se almacena en la base local.</small></label>
                    </section>

                    <section class="form-section auto-section">
                        <div class="form-section-title"><span>02</span><div><h3>Información recuperada automáticamente</h3><p>Los campos protegidos provienen de SAE y no necesitan captura manual.</p></div><b class="auto-badge"><svg class="icon"><use href="#i-check"/></svg>Autocompletado</b></div>
                        <div class="minute-form-grid">
                            <label class="form-field">Folio que generará el sistema<input id="newMinuteId" name="id" readonly></label>
                            <label class="form-field span-2">Cliente<input name="client" readonly required></label>
                            <label class="form-field">Fecha del servicio<input name="date" type="date" readonly required></label>
                            <label class="form-field">Hora de inicio<input name="start" type="time" readonly required></label>
                            <label class="form-field">Hora de término<input name="end" type="time" readonly required></label>
                            <label class="form-field">Asesor responsable<input name="adviser" readonly required></label>
                            <label class="form-field">Estatus del servicio<input name="serviceStatus" readonly></label>
                            <label class="form-field">Número de póliza<input name="policy" readonly required></label>
                            <label class="form-field">Horas contratadas<input name="contracted" type="number" readonly required></label>
                            <label class="form-field">Horas disponibles<input name="available" type="number" readonly required></label>
                            <label class="form-field">Vigencia<input name="validity" readonly></label>
                            <label class="form-field">Quién reportó<input name="reporter" readonly required></label>
                            <label class="form-field">Quién recibió el servicio<input name="recipient" readonly required></label>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="form-section-title"><span>03</span><div><h3>Revisión antes de generar</h3><p>La descripción ya viene registrada; solamente el correo puede corregirse.</p></div></div>
                        <div class="minute-form-grid">
                            <label class="form-field full">Falla o solicitud reportada <span class="field-source">Desde SAE</span><textarea name="issue" readonly required></textarea></label>
                            <label class="form-field full">Servicio realizado <span class="field-source">Desde SAE</span><textarea name="work" readonly required></textarea></label>
                            <label class="form-field full">Actividad pendiente <span class="field-source">Desde SAE</span><textarea name="pending" readonly required></textarea></label>
                            <label class="form-field full editable-field">Correo del destinatario <span class="field-source manual">Único dato ajustable</span><input name="email" type="email" required><small>Se recupera del catálogo del cliente, pero puede corregirse antes del envío.</small></label>
                        </div>
                    </section>
                </div>
                <div class="form-modal-actions"><span>Al confirmar, el sistema calculará el tiempo y creará la minuta como <strong>pendiente de envío</strong>.</span><div><button class="button secondary" type="button" data-close="newMinuteModal">Cancelar</button><button class="button primary" type="submit"><svg class="icon"><use href="#i-file"/></svg>Generar automáticamente</button></div></div>
            </form>
        </div>
    </div>
    <div class="toast" id="toast"><svg class="icon"><use href="#i-check"/></svg><span>Operación realizada</span></div>
    <div class="sidebar-scrim is-hidden" id="sidebarScrim"></div>
</body>
</html>
