import './bootstrap';

let minutes = Array.isArray(window.__MINUTES__) && window.__MINUTES__.length ? window.__MINUTES__ : [
    {
        id: 'INF-260301', client: 'Comercializadora del Centro', date: '01/10/2026', start: '09:00', end: '10:30', used: 1.5,
        policy: 'POL-1042', contracted: 20, available: 12, validity: 'Vigente', adviser: 'Maricela Cázares',
        reporter: 'Ana Ruiz', recipient: 'Ana Ruiz', email: 'ana.ruiz@comercializadora.mx', serviceStatus: 'Concluido', sendStatus: 'Enviada', sentAt: '01/10/2026 11:04',
        issue: 'Aspel SAE no permitía generar el reporte de existencias por almacén.',
        work: 'Se validaron los parámetros del reporte, se reconstruyeron los índices y se verificó la generación correcta del documento.',
        pending: 'Sin actividades pendientes.'
    },
    {
        id: 'INF-260302', client: 'Transportes del Bajío', date: '01/10/2026', start: '12:00', end: '14:00', used: 2,
        policy: 'POL-0988', contracted: 10, available: 1, validity: 'Vigente', adviser: 'Carlos Consultor',
        reporter: 'Luis Mendoza', recipient: 'Patricia León', email: 'sistemas@transportesbajio.mx', serviceStatus: 'Con actividad pendiente', sendStatus: 'Pendiente', sentAt: '—',
        issue: 'Error de timbrado al emitir comprobantes desde el módulo de facturación.',
        work: 'Se actualizó la configuración del PAC y se realizaron pruebas con un comprobante de demostración.',
        pending: 'Renovar el certificado de sello digital antes del 08/10/2026.'
    },
    {
        id: 'INF-260303', client: 'Grupo Textil Rivera', date: '02/10/2026', start: '08:30', end: '09:30', used: 1,
        policy: 'POL-1120', contracted: 25, available: 18, validity: 'Vigente', adviser: 'Laura Rodríguez',
        reporter: 'Sofía Rivera', recipient: 'Sofía Rivera', email: 'sofia@textilrivera.mx', serviceStatus: 'Concluido', sendStatus: 'Enviada', sentAt: '02/10/2026 10:02',
        issue: 'Los usuarios no podían consultar movimientos históricos del inventario.',
        work: 'Se ajustaron los permisos de consulta y se regeneraron las vistas de movimientos históricos.',
        pending: 'Sin actividades pendientes.'
    },
    {
        id: 'INF-260304', client: 'Clínica San Gabriel', date: '02/10/2026', start: '11:00', end: '13:15', used: 2.25,
        policy: 'POL-0914', contracted: 10, available: 0.8, validity: 'Vigente', adviser: 'Maricela Cázares',
        reporter: 'Eduardo Nieto', recipient: 'Karla Sánchez', email: 'administracion@clinicasangabriel.mx', serviceStatus: 'Concluido', sendStatus: 'Error', sentAt: '02/10/2026 14:10',
        issue: 'La estación de caja perdió conexión con la base de datos de Aspel SAE.',
        work: 'Se restableció la ruta de red, se verificó el cliente Firebird y se probaron operaciones de consulta.',
        pending: 'Validar con infraestructura la estabilidad del enlace de red.'
    },
    {
        id: 'INF-260305', client: 'Ferretería Modelo', date: '02/10/2026', start: '15:00', end: '16:00', used: 1,
        policy: 'POL-1107', contracted: 15, available: 9.5, validity: 'Vigente', adviser: 'Carlos Consultor',
        reporter: 'Miguel Torres', recipient: 'Miguel Torres', email: 'miguel@ferreteriamodelo.mx', serviceStatus: 'Concluido', sendStatus: 'Enviada', sentAt: '02/10/2026 16:22',
        issue: 'Diferencia entre existencias físicas y existencias mostradas por el sistema.',
        work: 'Se revisaron movimientos pendientes y se explicó el proceso de recálculo de existencias.',
        pending: 'Realizar inventario físico del almacén principal.'
    },
    {
        id: 'INF-260306', client: 'Constructora Horizonte', date: '03/10/2026', start: '08:00', end: '09:45', used: 1.75,
        policy: 'POL-1051', contracted: 12, available: 6, validity: 'Vigente', adviser: 'Laura Rodríguez',
        reporter: 'Daniel Campos', recipient: 'Mónica Vela', email: 'sistemas@horizonte.mx', serviceStatus: 'Concluido', sendStatus: 'Pendiente', sentAt: '—',
        issue: 'El catálogo de clientes mostraba registros duplicados después de una importación.',
        work: 'Se identificó el lote duplicado y se documentó el procedimiento de depuración para autorización.',
        pending: 'Autorizar la depuración en una ventana de mantenimiento.'
    },
    {
        id: 'INF-260307', client: 'Distribuidora Nova', date: '03/10/2026', start: '10:00', end: '11:30', used: 1.5,
        policy: 'POL-0875', contracted: 8, available: 0.7, validity: 'Por vencer', adviser: 'Maricela Cázares',
        reporter: 'Raúl Medina', recipient: 'Raúl Medina', email: 'raul.medina@nova.mx', serviceStatus: 'Concluido', sendStatus: 'Enviada', sentAt: '03/10/2026 12:01',
        issue: 'Falla al respaldar la base de datos desde el administrador de periodos.',
        work: 'Se corrigieron permisos del directorio de respaldos y se verificó la integridad del archivo generado.',
        pending: 'Renovar la póliza antes del próximo servicio.'
    }
];

let mailItems = [
    { id: 1, minute: 'INF-260307', client: 'Distribuidora Nova', date: '03/10/2026 12:01', type: 'Minuta terminada', to: 'raul.medina@nova.mx', cc: 'atencion@infodata.mx', status: 'Enviado', subject: 'Minuta de servicio INF-260307 – Distribuidora Nova', error: '' },
    { id: 2, minute: 'INF-260304', client: 'Clínica San Gabriel', date: '02/10/2026 14:10', type: 'Minuta terminada', to: 'administracion@clinicasangabriel.mx', cc: 'atencion@infodata.mx', status: 'Error', subject: 'Minuta de servicio INF-260304 – Clínica San Gabriel', error: 'Error SMTP 550: el buzón del destinatario rechazó el mensaje.' },
    { id: 3, minute: 'INF-260302', client: 'Transportes del Bajío', date: '02/10/2026 08:00', type: 'Alerta de póliza', to: 'sistemas@transportesbajio.mx', cc: 'direccion@infodata.mx', status: 'Pendiente', subject: 'Alerta: póliza POL-0988 con 10% disponible', error: '' },
    { id: 4, minute: 'INF-260305', client: 'Ferretería Modelo', date: '02/10/2026 16:22', type: 'Minuta terminada', to: 'miguel@ferreteriamodelo.mx', cc: 'atencion@infodata.mx', status: 'Enviado', subject: 'Minuta de servicio INF-260305 – Ferretería Modelo', error: '' },
    { id: 5, minute: 'INF-260301', client: 'Comercializadora del Centro', date: '01/10/2026 11:04', type: 'Minuta terminada', to: 'ana.ruiz@comercializadora.mx', cc: 'atencion@infodata.mx', status: 'Enviado', subject: 'Minuta de servicio INF-260301 – Comercializadora del Centro', error: '' }
];

const users = [
    { name: 'Jonatan Salazar', email: 'jonatan@infodata.mx', role: 'Administrador', status: 'Activo' },
    { name: 'Maricela Cázares', email: 'maricela@infodata.mx', role: 'Consultor', status: 'Activo' },
    { name: 'Carlos Consultor', email: 'carlos@infodata.mx', role: 'Consultor', status: 'Activo' }
];

const automatedServices = {
    'SAE-SRV-4832': {
        client: 'Industrias Demo Norte', date: '2026-10-03', start: '08:30', end: '09:45', adviser: 'Jonatan Salazar',
        serviceStatus: 'Concluido', policy: 'POL-1218', contracted: 20, available: 14.75, validity: 'Vigente',
        reporter: 'Andrea Morales', recipient: 'Andrea Morales', email: 'andrea.morales@example.com',
        issue: 'El usuario reportó que no podía consultar el reporte de ventas acumuladas en Aspel SAE.',
        work: 'Se revisaron los permisos del usuario, se actualizó la configuración del reporte y se comprobó su generación correcta.',
        pending: 'Sin actividades pendientes.'
    },
    'SAE-SRV-4831': {
        client: 'Distribuidora Ejemplo Central', date: '2026-10-03', start: '10:15', end: '12:00', adviser: 'Maricela Cázares',
        serviceStatus: 'Con actividad pendiente', policy: 'POL-1205', contracted: 15, available: 6.25, validity: 'Vigente',
        reporter: 'Roberto Díaz', recipient: 'Elena Castro', email: 'elena.castro@example.com',
        issue: 'Se detectó una diferencia entre el inventario físico y las existencias mostradas en el almacén principal.',
        work: 'Se revisaron movimientos pendientes y se ejecutó una validación de existencias sin modificar la base de datos.',
        pending: 'El cliente realizará un conteo físico para continuar con la conciliación.'
    },
    'SAE-SRV-4829': {
        client: 'Hotel Demostración del Valle', date: '2026-10-02', start: '16:00', end: '17:30', adviser: 'Carlos Consultor',
        serviceStatus: 'Concluido', policy: 'POL-1196', contracted: 10, available: 2.5, validity: 'Por vencer',
        reporter: 'Sofía Herrera', recipient: 'Sofía Herrera', email: 'sofia.herrera@example.com',
        issue: 'El proceso de respaldo mostraba un aviso de permisos insuficientes en la carpeta de destino.',
        work: 'Se corrigieron los permisos del directorio y se validó la creación e integridad del archivo de respaldo.',
        pending: 'Programar la renovación de la póliza antes del siguiente servicio.'
    }
};

const icon = (name) => `<svg class="icon"><use href="#i-${name}"/></svg>`;
const byId = (id) => document.getElementById(id);
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let selectedMailId = mailItems[0].id;
let selectedMinuteId = null;
let toastTimer;

function statusClass(status) {
    if (['Enviada', 'Enviado', 'Vigente', 'Activo'].includes(status)) return 'sent';
    if (['Pendiente', 'Por vencer'].includes(status)) return 'pending';
    return 'error';
}

function showToast(message) {
    const toast = byId('toast');
    toast.querySelector('span').textContent = message;
    toast.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 3000);
}

function nextMinuteId() {
    const minuteNumbers = minutes.map((minute) => Number(minute.id.replace(/\D/g, '')) || 0);
    const lastNumber = minuteNumbers.length ? Math.max(...minuteNumbers) : 260300;
    return `INF-${String(lastNumber + 1).padStart(6, '0')}`;
}

async function loadMinutesFromDatabase(showMessage = true) {
    try {
        const response = await fetch('/api/minutas', {
            headers: { Accept: 'application/json' }
        });

        if (!response.ok) throw new Error('No fue posible consultar la base local.');

        const payload = await response.json();
        minutes = Array.isArray(payload.data) ? payload.data : [];
        renderDashboard();
        filterMinutes();
        if (showMessage) showToast(`${minutes.length} minutas consultadas desde SQLite.`);
    } catch (error) {
        showToast(error.message || 'Ocurrió un error al consultar la base local.');
    }
}

async function saveMinuteToDatabase(minute) {
    const response = await fetch('/api/minutas', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(minute),
    });

    const payload = await response.json();
    if (!response.ok) {
        const firstValidationError = Object.values(payload.errors ?? {}).flat()[0];
        throw new Error(firstValidationError || payload.message || 'No fue posible guardar la minuta.');
    }

    return payload.data;
}

function formatServiceDate(value) {
    const [year, month, day] = value.split('-');
    return `${day}/${month}/${year}`;
}

function serviceDuration(start, end) {
    const [startHour, startMinute] = start.split(':').map(Number);
    const [endHour, endMinute] = end.split(':').map(Number);
    return ((endHour * 60 + endMinute) - (startHour * 60 + startMinute)) / 60;
}

function populateAutomatedService(serviceId) {
    const service = automatedServices[serviceId];
    const form = byId('newMinuteForm');
    if (!service || !form) return;

    Object.entries(service).forEach(([field, value]) => {
        if (form.elements[field]) form.elements[field].value = value;
    });
}

function openNewMinute() {
    const form = byId('newMinuteForm');
    form.reset();
    form.elements.end.setCustomValidity('');
    byId('newMinuteId').value = nextMinuteId();
    populateAutomatedService(form.elements.sourceService.value);
    byId('newMinuteModal').classList.remove('is-hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(() => form.elements.sourceService.focus(), 100);
}

function navigate(viewName) {
    document.querySelectorAll('.view').forEach((view) => view.classList.toggle('is-active', view.id === `view-${viewName}`));
    document.querySelectorAll('.nav-item').forEach((item) => item.classList.toggle('is-active', item.dataset.view === viewName));
    const activeView = byId(`view-${viewName}`);
    byId('topbarTitle').textContent = activeView?.dataset.title ?? 'Sistema de Minutas';
    byId('sidebar').classList.remove('is-open');
    byId('sidebarScrim').classList.add('is-hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderDashboard() {
    const chartData = [8, 11, 7, 14, 10, 6, 12];
    const days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    byId('activityChart').innerHTML = chartData.map((value, index) => `
        <div class="chart-column"><strong>${value}</strong><div class="chart-bar" style="height:${value * 10}px"></div><span>${days[index]}</span></div>
    `).join('');

    const sent = minutes.filter((minute) => minute.sendStatus === 'Enviada').length;
    const pending = minutes.filter((minute) => minute.sendStatus === 'Pendiente').length;
    const errors = minutes.filter((minute) => minute.sendStatus === 'Error').length;
    const critical = minutes.filter((minute) => minute.contracted > 0 && (minute.available / minute.contracted) <= .1);
    const sentRate = minutes.length ? Math.round((sent / minutes.length) * 100) : 0;

    byId('metricTotal').textContent = minutes.length;
    byId('metricSent').textContent = sent;
    byId('metricSentRate').textContent = `${sentRate}% del total`;
    byId('metricPending').textContent = pending;
    byId('metricCritical').textContent = critical.length;
    byId('summarySent').textContent = sent;
    byId('summaryPending').textContent = pending;
    byId('summaryError').textContent = errors;
    byId('navMinuteCount').textContent = minutes.length;

    byId('policyAlerts').innerHTML = critical.length ? critical.map((minute) => {
        const percentage = Math.round((minute.available / minute.contracted) * 100);
        return `<div class="policy-alert"><div><strong>${minute.client}</strong><small>${minute.policy} · ${minute.available} de ${minute.contracted} horas</small></div><div class="hours-ring">${percentage}%</div></div>`;
    }).join('') : '<p class="muted">No hay pólizas en nivel crítico.</p>';

    byId('recentMinutes').innerHTML = minutes.slice(-4).reverse().map((minute) => `
        <tr><td><strong>${minute.id}</strong></td><td>${minute.client}</td><td>${minute.date}</td><td>${minute.policy}</td><td><span class="badge ${statusClass(minute.sendStatus)}">${minute.sendStatus}</span></td><td><button class="table-action" data-minute="${minute.id}" title="Ver detalle">${icon('eye')}</button></td></tr>
    `).join('');
}

function minuteRow(minute) {
    const percentage = Math.max(0, Math.min(100, Math.round((minute.available / minute.contracted) * 100)));
    const level = percentage <= 10 ? 'critical' : percentage <= 25 ? 'warning' : '';
    return `<tr>
        <td><strong>${minute.id}</strong><br><small>${minute.policy}</small></td>
        <td><div class="client-cell"><strong>${minute.client}</strong><small>${minute.email}</small></div></td>
        <td>${minute.date}<br><small>${minute.start}–${minute.end}</small></td>
        <td>${minute.adviser}</td>
        <td class="hours-cell"><div class="hours-top"><span>${minute.available} de ${minute.contracted} h</span><strong>${percentage}%</strong></div><div class="progress ${level}"><span style="width:${percentage}%"></span></div></td>
        <td><div class="service-text" title="${minute.issue}">${minute.issue}</div></td>
        <td><span class="badge ${statusClass(minute.sendStatus)}">${minute.sendStatus}</span></td>
        <td class="actions-cell"><div class="row-actions"><button class="table-action" data-minute="${minute.id}" title="Ver detalle">${icon('eye')}</button><button class="table-action" data-resend="${minute.id}" title="Reenviar minuta">${icon('send')}</button></div></td>
    </tr>`;
}

function filterMinutes() {
    const query = byId('minuteSearch').value.trim().toLowerCase();
    const status = byId('statusFilter').value;
    const policy = byId('policyFilter').value;
    const filtered = minutes.filter((minute) => {
        const matchesQuery = [minute.id, minute.client, minute.adviser, minute.policy].some((value) => value.toLowerCase().includes(query));
        const matchesStatus = status === 'all' || minute.sendStatus === status;
        const percentage = minute.available / minute.contracted;
        const matchesPolicy = policy === 'all' || (policy === 'critical' && percentage <= .1) || (policy === 'active' && minute.validity === 'Vigente');
        return matchesQuery && matchesStatus && matchesPolicy;
    });
    byId('minutesTable').innerHTML = filtered.length ? filtered.map(minuteRow).join('') : '<tr><td colspan="8" class="muted">No se encontraron minutas con estos filtros.</td></tr>';
    byId('minuteCount').textContent = filtered.length;
    byId('tableStatus').textContent = `Mostrando ${filtered.length} de ${minutes.length} registros almacenados en SQLite`;
}

function openMinute(id) {
    const minute = minutes.find((item) => item.id === id);
    if (!minute) return;
    selectedMinuteId = id;
    byId('minuteModalTitle').textContent = minute.id;
    byId('minuteDocument').innerHTML = `
        <article class="minute-document">
            <header class="doc-head"><div class="doc-brand"><span class="brand-mark">i.</span><div><strong>Infodata</strong><small>Soluciones de tecnología empresarial</small></div></div><div class="doc-title"><span>Documento de servicio</span><h3>Minuta de servicio</h3><strong>${minute.id}</strong></div></header>
            <div class="doc-body">
                <div class="doc-meta"><div><span>Fecha</span><strong>${minute.date}</strong></div><div><span>Hora inicio</span><strong>${minute.start}</strong></div><div><span>Hora fin</span><strong>${minute.end}</strong></div><div><span>Tiempo</span><strong>${minute.used} h</strong></div><div><span>Estatus</span><strong>${minute.serviceStatus}</strong></div></div>
                <section class="doc-section"><h4>Datos del cliente</h4><div class="doc-data-grid"><div><span>Cliente</span><strong>${minute.client}</strong></div><div><span>Póliza</span><strong>${minute.policy}</strong></div><div><span>Vigencia</span><strong>${minute.validity}</strong></div><div><span>Horas contratadas</span><strong>${minute.contracted} h</strong></div><div><span>Horas disponibles</span><strong>${minute.available} h</strong></div><div><span>Consultor</span><strong>${minute.adviser}</strong></div>${minute.sourceService ? `<div><span>Servicio origen</span><strong>${minute.sourceService}</strong></div>` : ''}</div></section>
                <section class="doc-section"><h4>Participantes</h4><div class="doc-data-grid"><div><span>Quién reporta</span><strong>${minute.reporter}</strong></div><div><span>Quién recibe</span><strong>${minute.recipient}</strong></div><div><span>Correo</span><strong>${minute.email}</strong></div></div></section>
                <section class="doc-section"><h4>Falla reportada</h4><div class="doc-block red">${minute.issue}</div></section>
                <section class="doc-section"><h4>Servicio realizado</h4><div class="doc-block">${minute.work}</div></section>
                <section class="doc-section"><h4>Actividad pendiente</h4><div class="doc-block orange">${minute.pending}</div></section>
                <div class="signatures"><div>Firma del cliente</div><div>Firma del asesor</div></div>
                <footer class="doc-foot"><span>Consultado desde SQLite · ${new Date().toLocaleDateString('es-MX')}</span><span>${minute.id} · Página 1 de 1</span></footer>
            </div>
        </article>`;
    byId('minuteModal').classList.remove('is-hidden');
    document.body.style.overflow = 'hidden';
}

function openResend(id) {
    const minute = minutes.find((item) => item.id === id);
    if (!minute) return;
    selectedMinuteId = id;
    byId('resendData').innerHTML = `<span>Cliente</span><strong>${minute.client}</strong><span>No. de minuta</span><strong>${minute.id}</strong><span>Destinatarios</span><strong>${minute.email}, atencion@infodata.mx</strong>`;
    byId('resendModal').classList.remove('is-hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    byId(id).classList.add('is-hidden');
    document.body.style.overflow = '';
}

function renderMails() {
    const query = byId('mailSearch').value.trim().toLowerCase();
    const status = byId('mailStatusFilter').value;
    const filtered = mailItems.filter((mail) => {
        const matchesQuery = [mail.minute, mail.client, mail.to].some((value) => value.toLowerCase().includes(query));
        return matchesQuery && (status === 'all' || mail.status === status);
    });
    byId('mailTable').innerHTML = filtered.length ? filtered.map((mail) => `
        <tr class="mail-row ${mail.id === selectedMailId ? 'is-selected' : ''}" data-mail="${mail.id}"><td>${mail.date}</td><td><div class="client-cell"><strong>${mail.minute}</strong><small>${mail.client}</small></div></td><td>${mail.type}</td><td><span class="badge ${statusClass(mail.status)}">${mail.status}</span></td><td>${icon('eye')}</td></tr>
    `).join('') : '<tr><td colspan="5" class="muted">No se encontraron correos.</td></tr>';
    const selected = mailItems.find((mail) => mail.id === selectedMailId) ?? filtered[0] ?? mailItems[0];
    if (selected) renderMailPreview(selected);
}

function renderMailPreview(mail) {
    const minute = minutes.find((item) => item.id === mail.minute);
    byId('mailPreview').innerHTML = `
        <header class="mail-preview-head"><span>Vista previa del correo</span><h2>${mail.subject}</h2></header>
        <dl class="mail-meta"><dt>Destinatario</dt><dd>${mail.to}</dd><dt>Copias</dt><dd>${mail.cc}</dd><dt>Fecha</dt><dd>${mail.date}</dd><dt>Tipo</dt><dd>${mail.type}</dd><dt>Estatus</dt><dd><span class="badge ${statusClass(mail.status)}">${mail.status}</span></dd></dl>
        ${mail.error ? `<div class="mail-error"><strong>⚠ Error de envío</strong><br>${mail.error}</div>` : ''}
        <div class="mail-body">Estimado/a ${minute?.recipient ?? 'cliente'}:

Adjuntamos la minuta correspondiente al servicio ${mail.minute}. El documento incluye el detalle de la atención y las actividades pendientes.

Quedamos a sus órdenes para cualquier consulta.

Atentamente,
Equipo de Atención Infodata</div>
        <div class="attachment">${icon('file')}<div><strong>${mail.minute}.pdf</strong><small>Minuta de servicio · PDF</small></div></div>
        <div class="mail-actions"><button class="button secondary" data-action="download">${icon('download')}Descargar</button><button class="button primary" data-resend="${mail.minute}">${icon('send')}Reenviar</button></div>`;
}

const settingsTemplates = {
    general: () => `<div class="settings-title"><h2>Datos generales</h2><p>Identidad que se mostrará en el sistema y en las minutas.</p></div><div class="form-grid"><label class="form-field">Nombre de la empresa<input value="Infodata"></label><label class="form-field">Nombre del sistema<input value="Sistema de Minutas Infodata"></label><label class="form-field full">Firma utilizada en correos<textarea>Atentamente,\nEquipo de Atención Infodata\nwww.infodata.mx</textarea><small>Se agregará al final de los correos enviados.</small></label></div>${settingsActions()}`,
    mail: () => `<div class="settings-title"><h2>Correos y destinatarios</h2><p>Cuenta remitente y direcciones para copias automáticas.</p></div><div class="form-grid"><label class="form-field">Cuenta de envío<input value="minutas@infodata.mx"></label><label class="form-field">Nombre del remitente<input value="Sistema de Minutas Infodata"></label><label class="form-field">Atención a clientes<input value="atencion@infodata.mx"></label><label class="form-field">Dirección<input value="direccion@infodata.mx"></label><label class="form-field full">Copias adicionales<input placeholder="correo1@ejemplo.mx, correo2@ejemplo.mx"><small>Separa las direcciones con una coma.</small></label></div><div class="connection-status"><div><span class="status-dot"></span><div><strong>Configuración de demostración</strong><small>La conexión SMTP se realizará en la fase de integración.</small></div></div><button class="button secondary" data-setting-action="mail-test">Enviar prueba</button></div>${settingsActions()}`,
    templates: () => `<div class="settings-title"><h2>Plantillas de correo</h2><p>Mensajes utilizados para minutas concluidas y alertas de póliza.</p></div><div class="form-grid"><label class="form-field full">Asunto de minuta<input value="Minuta de servicio {{folio}} – {{cliente}}"></label><label class="form-field full">Mensaje de minuta<textarea>Estimado/a {{contacto}}:\n\nAdjuntamos la minuta del servicio realizado el {{fecha_servicio}}.\n\nQuedamos a sus órdenes.</textarea></label><label class="form-field full">Asunto de alerta<input value="Alerta: póliza {{poliza}} con {{porcentaje}}% disponible"></label><label class="form-field full">Mensaje de alerta<textarea>Estimado/a {{contacto}}:\n\nLa póliza {{poliza}} dispone de {{horas_disponibles}} horas, equivalente al {{porcentaje}}%. Le recomendamos comunicarse con Infodata para su renovación.</textarea></label></div>${settingsActions()}`,
    automation: () => `<div class="settings-title"><h2>Automatizaciones</h2><p>Reglas que controlarán los envíos y el registro de actividad.</p></div><div class="switch-list"><div class="switch-row"><div><strong>Envío automático de minutas</strong><small>Enviar cuando un servicio sea marcado como concluido.</small></div><button class="switch is-on" aria-label="Envío automático" aria-pressed="true"></button></div><div class="switch-row"><div><strong>Alerta al 10% de horas disponibles</strong><small>Notificar al cliente y a dirección una sola vez por póliza.</small></div><button class="switch is-on" aria-label="Alertas de póliza" aria-pressed="true"></button></div><div class="switch-row"><div><strong>Permitir reenvíos autorizados</strong><small>Los administradores podrán reenviar minutas existentes.</small></div><button class="switch is-on" aria-label="Permitir reenvíos" aria-pressed="true"></button></div><div class="switch-row"><div><strong>Registrar historial de envíos</strong><small>Guardar fecha, usuario, destinatarios y resultado.</small></div><button class="switch is-on" aria-label="Registrar historial" aria-pressed="true"></button></div></div>${settingsActions()}`,
    sae: () => `<div class="settings-title"><h2>Conexión con Aspel SAE</h2><p>Parámetros del servicio de integración de solo lectura.</p></div><div class="info-box">${icon('database')}<div><strong>Acceso protegido de solo lectura.</strong><br>La aplicación consultará servicios, clientes y pólizas; nunca modificará la base de datos de Aspel SAE.</div></div><div class="form-grid"><label class="form-field">Servidor o dirección IP<input value="192.168.1.100"></label><label class="form-field">Puerto<input value="3050"></label><label class="form-field full">Ruta de base Firebird<input value="C:\\Aspel\\SAE\\Datos\\SAEMPRE.FDB"></label><label class="form-field">Usuario de lectura<input value="SAE_READER"></label><label class="form-field">Contraseña<input type="password" value="demostracion"></label></div><div class="connection-status" id="saeStatus"><div><span class="status-dot"></span><div><strong>Modo demostración</strong><small>La prueba no realiza una conexión real.</small></div></div><button class="button secondary" data-setting-action="sae-test">Probar conexión</button></div>${settingsActions()}`,
    users: () => `<div class="settings-title"><h2>Usuarios y permisos</h2><p>Administradores y consultores autorizados para usar el sistema.</p></div><button class="button primary" data-setting-action="show-user-form">${icon('user')}Agregar usuario</button><form class="form-grid is-hidden" id="newUserForm" style="margin-top:18px"><label class="form-field">Nombre<input name="name" required></label><label class="form-field">Correo<input name="email" type="email" required></label><label class="form-field">Rol<select name="role"><option>Consultor</option><option>Administrador</option></select></label><div class="form-field"><span>&nbsp;</span><button class="button secondary" type="submit">Guardar usuario</button></div></form><div class="table-wrap" style="margin-top:18px"><table class="user-list"><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estatus</th><th>Acciones</th></tr></thead><tbody id="usersTable">${userRows()}</tbody></table></div><div class="info-box" style="margin-top:18px">${icon('user')}<div><strong>Administrador:</strong> acceso completo. <strong>Consultor:</strong> consulta minutas y correos; no accede a configuración.</div></div>`
};

function settingsActions() {
    return `<div class="settings-actions"><button class="button secondary" data-setting-action="restore">Restaurar valores</button><button class="button primary" data-setting-action="save">Guardar cambios</button></div>`;
}

function userRows() {
    return users.map((user) => `<tr><td><strong>${user.name}</strong></td><td>${user.email}</td><td>${user.role}</td><td><span class="badge ${statusClass(user.status)}">${user.status}</span></td><td><button class="link-button" data-setting-action="edit-user">Editar</button></td></tr>`).join('');
}

function renderSetting(name) {
    const renderer = settingsTemplates[name] ?? settingsTemplates.general;
    byId('settingsContent').innerHTML = renderer();
    document.querySelectorAll('#settingsNav button').forEach((button) => button.classList.toggle('is-active', button.dataset.setting === name));
}

function handleSettingAction(action, target) {
    if (action === 'save') showToast('Configuración guardada en el prototipo.');
    if (action === 'restore') showToast('Se restauraron los valores de demostración.');
    if (action === 'mail-test') showToast('Prueba simulada: configuración de correo correcta.');
    if (action === 'edit-user') showToast('La edición se habilitará con la autenticación real.');
    if (action === 'show-user-form') byId('newUserForm')?.classList.toggle('is-hidden');
    if (action === 'sae-test') {
        const status = byId('saeStatus');
        status.querySelector('strong').textContent = 'Conexión simulada exitosa';
        status.querySelector('small').textContent = 'El servicio de integración respondió en modo demostración.';
        target.textContent = 'Conexión correcta';
        showToast('Aspel SAE respondió correctamente en la simulación.');
    }
}

function initializeEvents() {
    byId('loginForm').addEventListener('submit', (event) => {
        event.preventDefault();
        byId('loginScreen').classList.add('is-hidden');
        byId('appShell').classList.remove('is-hidden');
        navigate('dashboard');
        showToast('Sesión de demostración iniciada.');
    });

    byId('togglePassword').addEventListener('click', () => {
        const password = byId('loginPassword');
        password.type = password.type === 'password' ? 'text' : 'password';
    });

    byId('logoutButton').addEventListener('click', () => {
        byId('appShell').classList.add('is-hidden');
        byId('loginScreen').classList.remove('is-hidden');
        showToast('Sesión cerrada correctamente.');
    });

    document.querySelectorAll('.nav-item').forEach((button) => button.addEventListener('click', () => navigate(button.dataset.view)));
    document.querySelectorAll('[data-go]').forEach((button) => button.addEventListener('click', () => navigate(button.dataset.go)));
    byId('menuButton').addEventListener('click', () => { byId('sidebar').classList.add('is-open'); byId('sidebarScrim').classList.remove('is-hidden'); });
    byId('sidebarScrim').addEventListener('click', () => { byId('sidebar').classList.remove('is-open'); byId('sidebarScrim').classList.add('is-hidden'); });

    ['minuteSearch', 'statusFilter', 'policyFilter'].forEach((id) => byId(id).addEventListener(id === 'minuteSearch' ? 'input' : 'change', filterMinutes));
    ['mailSearch', 'mailStatusFilter'].forEach((id) => byId(id).addEventListener(id === 'mailSearch' ? 'input' : 'change', renderMails));
    byId('saeServiceSelect').addEventListener('change', (event) => {
        populateAutomatedService(event.target.value);
        showToast(`Datos del servicio ${event.target.value} cargados automáticamente.`);
    });

    document.addEventListener('click', async (event) => {
        const minuteButton = event.target.closest('[data-minute]');
        const resendButton = event.target.closest('[data-resend]');
        const closeButton = event.target.closest('[data-close]');
        const refreshButton = event.target.closest('[data-action="refresh"]');
        const newMinuteButton = event.target.closest('[data-action="new-minute"]');
        const downloadButton = event.target.closest('[data-action="download"]');
        const mailRow = event.target.closest('[data-mail]');
        const settingTab = event.target.closest('[data-setting]');
        const settingAction = event.target.closest('[data-setting-action]');
        const switchButton = event.target.closest('.switch');

        if (minuteButton) openMinute(minuteButton.dataset.minute);
        if (resendButton) openResend(resendButton.dataset.resend);
        if (closeButton) closeModal(closeButton.dataset.close);
        if (refreshButton) await loadMinutesFromDatabase();
        if (newMinuteButton) openNewMinute();
        if (downloadButton) { showToast('Vista de impresión preparada.'); setTimeout(() => window.print(), 250); }
        if (mailRow) { selectedMailId = Number(mailRow.dataset.mail); renderMails(); }
        if (settingTab) renderSetting(settingTab.dataset.setting);
        if (settingAction) handleSettingAction(settingAction.dataset.settingAction, settingAction);
        if (switchButton) { switchButton.classList.toggle('is-on'); switchButton.setAttribute('aria-pressed', String(switchButton.classList.contains('is-on'))); }
    });

    byId('confirmResend').addEventListener('click', () => {
        const minute = minutes.find((item) => item.id === selectedMinuteId);
        if (!minute) return;
        minute.sendStatus = 'Enviada';
        minute.sentAt = '03/10/2026 16:30';
        mailItems = [{ id: Date.now(), minute: minute.id, client: minute.client, date: minute.sentAt, type: 'Reenvío de minuta', to: minute.email, cc: 'atencion@infodata.mx', status: 'Enviado', subject: `Reenvío – Minuta de servicio ${minute.id}`, error: '' }, ...mailItems];
        selectedMailId = mailItems[0].id;
        closeModal('resendModal');
        filterMinutes();
        renderMails();
        showToast(`La minuta ${minute.id} se reenvió correctamente.`);
    });

    byId('settingsContent').addEventListener('submit', (event) => {
        if (event.target.id !== 'newUserForm') return;
        event.preventDefault();
        const data = new FormData(event.target);
        users.push({ name: data.get('name'), email: data.get('email'), role: data.get('role'), status: 'Activo' });
        renderSetting('users');
        showToast('Usuario agregado al prototipo.');
    });

    byId('newMinuteForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const data = new FormData(form);
        const duration = serviceDuration(data.get('start'), data.get('end'));

        if (duration <= 0) {
            form.elements.end.setCustomValidity('La hora de término debe ser posterior a la hora de inicio.');
            form.elements.end.reportValidity();
            return;
        }

        form.elements.end.setCustomValidity('');
        const minuteToSave = {
            id: data.get('id'),
            client: data.get('client').trim(),
            date: data.get('date'),
            start: data.get('start'),
            end: data.get('end'),
            used: Number(duration.toFixed(2)),
            policy: data.get('policy').trim().toUpperCase(),
            contracted: Number(data.get('contracted')),
            available: Number(data.get('available')),
            validity: data.get('validity'),
            adviser: data.get('adviser'),
            reporter: data.get('reporter').trim(),
            recipient: data.get('recipient').trim(),
            email: data.get('email').trim(),
            serviceStatus: data.get('serviceStatus'),
            issue: data.get('issue').trim(),
            work: data.get('work').trim(),
            pending: data.get('pending').trim(),
            sourceService: data.get('sourceService'),
        };

        try {
            const savedMinute = await saveMinuteToDatabase(minuteToSave);
            minutes.push(savedMinute);
            byId('minuteSearch').value = '';
            byId('statusFilter').value = 'all';
            byId('policyFilter').value = 'all';
            closeModal('newMinuteModal');
            renderDashboard();
            filterMinutes();
            navigate('minutas');
            openMinute(savedMinute.id);
            showToast(`La minuta ${savedMinute.id} se guardó en SQLite.`);
        } catch (error) {
            showToast(error.message || 'No fue posible guardar la minuta.');
        }
    });

    [byId('minuteModal'), byId('resendModal'), byId('newMinuteModal')].forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal.id); }));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { closeModal('minuteModal'); closeModal('resendModal'); closeModal('newMinuteModal'); } });
}

renderDashboard();
filterMinutes();
renderMails();
renderSetting('general');
initializeEvents();
byId('topbarDate').textContent = new Intl.DateTimeFormat('es-MX', { dateStyle: 'full' }).format(new Date());
