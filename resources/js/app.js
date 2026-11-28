import './bootstrap';

let minutes = Array.isArray(window.__MINUTES__) ? window.__MINUTES__ : [];
let currentUser = window.__CURRENT_USER__ ?? null;

let mailItems = [];

let users = [];
let auditLogs = [];

let serviceCatalog = new Map();
let catalogRequest = 0;

const icon = (name) => `<svg class="icon"><use href="#i-${name}"/></svg>`;
const byId = (id) => document.getElementById(id);
let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let selectedMailId = null;
let selectedMinuteId = null;
let toastTimer;
let activeServiceSession = null;
let serviceTimerInterval = null;
let serviceTimerBaseline = null;

function statusClass(status) {
    if (['Enviada', 'Enviado', 'Vigente', 'Activo'].includes(status)) return 'sent';
    if (['Pendiente', 'Por vencer', 'Simulada', 'Simulado'].includes(status)) return 'pending';
    return 'error';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function showToast(message) {
    const toast = byId('toast');
    toast.querySelector('span').textContent = message;
    toast.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 3000);
}

function updateCsrfToken(token) {
    if (!token) return;
    csrfToken = token;
    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
}

function userInitials(name) {
    return String(name ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('') || 'ID';
}

function applyUserToInterface(user) {
    const initials = userInitials(user.name);
    document.querySelectorAll('[data-user-name]').forEach((element) => { element.textContent = user.name; });
    document.querySelectorAll('[data-user-role]').forEach((element) => { element.textContent = user.roleLabel; });
    document.querySelectorAll('[data-user-initials]').forEach((element) => { element.textContent = initials; });
    byId('greetingName').textContent = user.name.split(' ')[0];

    document.querySelectorAll('[data-admin-only]').forEach((element) => {
        element.classList.toggle('is-hidden', !user.permissions.viewConfiguration);
    });
    document.querySelectorAll('[data-action="new-minute"]').forEach((element) => {
        element.classList.toggle('is-hidden', !user.permissions.createMinutes);
    });
}

function showApplication(user) {
    currentUser = user;
    applyUserToInterface(user);
    if (user.permissions.viewConfiguration) renderSetting('general');
    byId('loginError').classList.add('is-hidden');
    byId('loginScreen').classList.add('is-hidden');
    byId('appShell').classList.remove('is-hidden');
    navigate('dashboard');
}

function showLogin() {
    currentUser = null;
    minutes = [];
    mailItems = [];
    users = [];
    auditLogs = [];
    selectedMailId = null;
    selectedMinuteId = null;
    ['minuteModal', 'resendModal', 'newMinuteModal'].forEach(closeModal);
    ['minuteDocument', 'resendData', 'settingsContent'].forEach((id) => byId(id).replaceChildren());
    byId('newMinuteForm').reset();
    byId('loginPassword').value = '';
    byId('loginPassword').type = 'password';
    renderDashboard();
    filterMinutes();
    renderMails();
    byId('appShell').classList.add('is-hidden');
    byId('loginScreen').classList.remove('is-hidden');
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            ...(options.headers ?? {}),
        },
    });

    const payload = await response.json().catch(() => ({}));
    updateCsrfToken(payload.csrfToken);

    if (!response.ok) {
        if (response.status === 401 && currentUser) showLogin();
        const firstValidationError = Object.values(payload.errors ?? {}).flat()[0];
        throw new Error(firstValidationError || payload.message || 'No fue posible completar la operación.');
    }

    return payload;
}

async function login(email, password, remember) {
    const payload = await requestJson('/api/acceso', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, remember }),
    });

    byId('loginPassword').value = '';
    byId('loginPassword').type = 'password';
    showApplication(payload.data);
    await Promise.all([
        loadMinutesFromDatabase(false),
        loadMailDeliveries(false),
    ]);
    showToast(payload.message);
}

async function logout() {
    const payload = await requestJson('/api/salir', { method: 'POST' });
    showLogin();
    showToast(payload.message);
}

async function loadMinutesFromDatabase(showMessage = true) {
    try {
        const payload = await requestJson('/api/minutas');
        minutes = Array.isArray(payload.data) ? payload.data : [];
        renderDashboard();
        filterMinutes();
        if (showMessage) showToast(`${minutes.length} minutas consultadas.`);
    } catch (error) {
        showToast(error.message || 'Ocurrió un error al consultar las minutas.');
    }
}

async function saveMinuteToDatabase(minute) {
    return requestJson('/api/minutas', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(minute),
    });
}

async function loadMailDeliveries(showMessage = false) {
    try {
        const payload = await requestJson('/api/correos');
        mailItems = Array.isArray(payload.data) ? payload.data : [];
        selectedMailId = mailItems.some((mail) => mail.id === selectedMailId)
            ? selectedMailId
            : (mailItems[0]?.id ?? null);
        renderMails();
        if (showMessage) showToast(`${mailItems.length} registros de correo consultados.`);
    } catch (error) {
        showToast(error.message || 'No fue posible consultar los correos.');
    }
}

async function sendMinuteEmail(id) {
    return requestJson(`/api/minutas/${encodeURIComponent(id)}/enviar`, { method: 'POST' });
}

function formatServiceDate(value) {
    if (!value) return '';
    const [year, month, day] = value.split('-');
    return `${day}/${month}/${year}`;
}

function timerDisplay(seconds) {
    const safeSeconds = Math.max(0, Number(seconds) || 0);
    const hours = Math.floor(safeSeconds / 3600);
    const minutesPart = Math.floor((safeSeconds % 3600) / 60);
    const secondsPart = Math.floor(safeSeconds % 60);
    return [hours, minutesPart, secondsPart].map((value) => String(value).padStart(2, '0')).join(':');
}

function humanTimerDisplay(seconds) {
    const safeSeconds = Math.max(0, Number(seconds) || 0);
    const hours = Math.floor(safeSeconds / 3600);
    const minutesPart = Math.floor((safeSeconds % 3600) / 60);
    const secondsPart = Math.floor(safeSeconds % 60);
    return `${hours} h ${String(minutesPart).padStart(2, '0')} min ${String(secondsPart).padStart(2, '0')} s`;
}

function localDateParts(value) {
    const date = new Date(value);
    const offsetDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
    return {
        date: offsetDate.toISOString().slice(0, 10),
        time: offsetDate.toISOString().slice(11, 16),
    };
}

function currentElapsedSeconds(session) {
    if (!session) return 0;
    if (session.status === 'completed') return Number(session.elapsedSeconds) || 0;
    if (!serviceTimerBaseline) return Number(session.elapsedSeconds) || 0;
    const locallyElapsed = Math.floor((performance.now() - serviceTimerBaseline.receivedAt) / 1000);
    return Math.max(0, serviceTimerBaseline.elapsedSeconds + locallyElapsed);
}

function stopTimerUpdates() {
    clearInterval(serviceTimerInterval);
    serviceTimerInterval = null;
}

function refreshTimerDisplay() {
    const elapsed = currentElapsedSeconds(activeServiceSession);
    byId('serviceTimerDisplay').textContent = timerDisplay(elapsed);

    const form = byId('newMinuteForm');
    if (form && activeServiceSession?.status === 'completed') {
        form.elements.actualTime.value = humanTimerDisplay(elapsed);
    }
}

function applyServiceSession(session) {
    stopTimerUpdates();
    activeServiceSession = session;
    serviceTimerBaseline = session?.status === 'running'
        ? {
            elapsedSeconds: Number(session.elapsedSeconds) || 0,
            receivedAt: performance.now(),
        }
        : null;

    const form = byId('newMinuteForm');
    const timer = byId('serviceTimer');
    const startButton = byId('startServiceButton');
    const stopButton = byId('stopServiceButton');
    const generateButton = byId('generateMinuteButton');

    form.elements.sessionId.value = session?.id ?? '';
    timer.classList.toggle('is-running', session?.status === 'running');
    timer.classList.toggle('is-completed', session?.status === 'completed');

    if (!session) {
        form.elements.date.value = '';
        form.elements.start.value = '';
        form.elements.end.value = '';
        form.elements.actualTime.value = '';
        form.elements.used.value = '';
        form.elements.used.setCustomValidity('');
        form.elements.used.disabled = true;
        startButton.classList.remove('is-hidden');
        stopButton.classList.add('is-hidden');
        generateButton.disabled = true;
        byId('timerStatusTitle').textContent = 'Servicio sin iniciar';
        byId('timerStatusText').textContent = 'Selecciona un documento y pulsa “Iniciar servicio” al comenzar.';
        refreshTimerDisplay();
        return;
    }

    const start = localDateParts(session.startedAt);
    form.elements.date.value = start.date;
    form.elements.start.value = start.time;

    if (session.status === 'running') {
        form.elements.end.value = '';
        form.elements.actualTime.value = '';
        form.elements.used.disabled = true;
        startButton.classList.add('is-hidden');
        stopButton.classList.remove('is-hidden');
        generateButton.disabled = true;
        byId('timerStatusTitle').textContent = 'Servicio en curso';
        byId('timerStatusText').textContent = `Iniciado por ${session.startedBy ?? 'el asesor actual'}. El tiempo se guarda en el servidor.`;
        refreshTimerDisplay();
        serviceTimerInterval = setInterval(refreshTimerDisplay, 1000);
        return;
    }

    const end = localDateParts(session.endedAt);
    form.elements.end.value = end.time;
    form.elements.actualTime.value = humanTimerDisplay(session.elapsedSeconds);
    form.elements.used.disabled = false;
    form.elements.used.max = form.elements.available.value;
    startButton.classList.add('is-hidden');
    stopButton.classList.add('is-hidden');
    generateButton.disabled = false;
    byId('timerStatusTitle').textContent = 'Servicio finalizado';
    byId('timerStatusText').textContent = 'Indica el tiempo efectivo que se descontará de la póliza.';
    refreshTimerDisplay();
}

async function selectService() {
    const form = byId('newMinuteForm');
    const serviceId = form.elements.sourceService.value;
    const service = serviceCatalog.get(serviceId);
    const request = ++catalogRequest;
    applyServiceSession(null);
    byId('startServiceButton').disabled = true;
    for (const field of ['folio', 'client', 'adviser', 'serviceStatus', 'policy', 'contracted',
        'available', 'validity', 'reporter', 'recipient', 'email', 'issue', 'work', 'pending']) {
        form.elements[field].value = service?.[field] ?? '';
    }
    form.elements.sourceDate.value = formatServiceDate(service?.sourceDate);
    if (!service) return;
    try {
        const payload = await requestJson(`/api/servicios/${encodeURIComponent(serviceId)}/sesion`);
        if (request !== catalogRequest) return;
        applyServiceSession(payload.data);
        byId('startServiceButton').disabled = false;
    } catch (error) {
        if (request === catalogRequest) showToast(error.message);
    }
}

async function changeServiceTimer(action, button) {
    const select = byId('saeServiceSelect');
    const serviceId = select.value;
    if (!serviceCatalog.has(serviceId) || button.disabled) return;
    const request = catalogRequest;
    select.disabled = true;
    button.disabled = true;
    try {
        const payload = await requestJson(`/api/servicios/${encodeURIComponent(serviceId)}/${action}`, { method: 'POST' });
        if (request !== catalogRequest) return;
        applyServiceSession(payload.data);
        showToast(payload.message);
    } catch (error) {
        if (request === catalogRequest) showToast(error.message);
    } finally {
        if (request === catalogRequest) {
            select.disabled = false;
            button.disabled = false;
        }
    }
}

async function openNewMinute() {
    const form = byId('newMinuteForm');
    const select = form.elements.sourceService;
    const request = ++catalogRequest;
    form.reset();
    serviceCatalog.clear();
    applyServiceSession(null);
    select.replaceChildren(new Option('Consultando documentos…', ''));
    select.disabled = true;
    byId('startServiceButton').disabled = true;
    byId('stopServiceButton').disabled = false;
    byId('saeCatalogStatus').textContent = 'Consultando la base de servicios.';
    byId('newMinuteModal').classList.remove('is-hidden');
    document.body.style.overflow = 'hidden';
    try {
        const { data } = await requestJson('/api/servicios');
        if (request !== catalogRequest) return;
        serviceCatalog = new Map(data.map((service) => [service.id, service]));
        select.replaceChildren(new Option(data.length ? 'Selecciona un documento' : 'Sin documentos disponibles', ''));
        for (const service of data) {
            select.add(new Option(`${service.folio} · ${service.client}`, service.id));
        }
        select.disabled = data.length === 0;
        byId('saeCatalogStatus').textContent = data.length
            ? 'Selecciona un documento para recuperar los datos de la atención.'
            : 'No hay documentos importados pendientes de minuta.';
        if (data.length) select.focus();
    } catch (error) {
        if (request === catalogRequest) {
            select.replaceChildren(new Option('Consulta no disponible', ''));
            byId('saeCatalogStatus').textContent = error.message;
        }
    }
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
    const dayFormat = new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const weekday = new Intl.DateTimeFormat('es-MX', { weekday: 'short' });
    const chartData = Array.from({ length: 7 }, (_, index) => {
        const date = new Date();
        date.setDate(date.getDate() - 6 + index);
        const dateLabel = dayFormat.format(date);
        return {
            day: weekday.format(date),
            count: minutes.filter((minute) => minute.date === dateLabel).length,
        };
    });
    const scale = Math.max(1, ...chartData.map(({ count }) => count));
    byId('activityChart').innerHTML = chartData.map(({ day, count }) => `
        <div class="chart-column"><strong>${count}</strong><div class="chart-bar" style="height:${Math.round(count / scale * 130)}px"></div><span>${escapeHtml(day)}</span></div>
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
        return `<div class="policy-alert"><div><strong>${escapeHtml(minute.client)}</strong><small>${escapeHtml(minute.policy)} · ${escapeHtml(minute.available)} de ${escapeHtml(minute.contracted)} horas</small></div><div class="hours-ring">${percentage}%</div></div>`;
    }).join('') : '<p class="muted">No hay pólizas en nivel crítico.</p>';

    byId('recentMinutes').innerHTML = minutes.slice(0, 4).map((minute) => `
        <tr><td><strong>${escapeHtml(minute.id)}</strong></td><td>${escapeHtml(minute.client)}</td><td>${escapeHtml(minute.date)}</td><td>${escapeHtml(minute.policy)}</td><td><span class="badge ${statusClass(minute.sendStatus)}">${escapeHtml(minute.sendStatus)}</span></td><td><button class="table-action" data-minute="${escapeHtml(minute.id)}" title="Ver detalle">${icon('eye')}</button></td></tr>
    `).join('');
}

function minuteRow(minute) {
    const percentage = Math.max(0, Math.min(100, Math.round((minute.available / minute.contracted) * 100)));
    const level = percentage <= 10 ? 'critical' : percentage <= 25 ? 'warning' : '';
    const folio = escapeHtml(minute.id);
    const issue = escapeHtml(minute.issue);
    const resendButton = currentUser?.permissions.createMinutes
        ? `<button class="table-action" data-resend="${folio}" title="Reenviar minuta">${icon('send')}</button>`
        : '';
    return `<tr>
        <td><strong>${folio}</strong><br><small>${escapeHtml(minute.policy)}</small></td>
        <td><div class="client-cell"><strong>${escapeHtml(minute.client)}</strong><small>${escapeHtml(minute.email)}</small></div></td>
        <td>${escapeHtml(minute.date)}<br><small>${escapeHtml(minute.start)}–${escapeHtml(minute.end)}</small></td>
        <td>${escapeHtml(minute.adviser)}</td>
        <td class="hours-cell"><div class="hours-top"><span>${escapeHtml(minute.available)} de ${escapeHtml(minute.contracted)} h</span><strong>${percentage}%</strong></div><div class="progress ${level}"><span style="width:${percentage}%"></span></div></td>
        <td><div class="service-text" title="${issue}">${issue}</div></td>
        <td><span class="badge ${statusClass(minute.sendStatus)}">${escapeHtml(minute.sendStatus)}</span></td>
        <td class="actions-cell"><div class="row-actions"><button class="table-action" data-minute="${folio}" title="Ver detalle">${icon('eye')}</button>${resendButton}</div></td>
    </tr>`;
}

function filterMinutes() {
    const query = byId('minuteSearch').value.trim().toLowerCase();
    const status = byId('statusFilter').value;
    const policy = byId('policyFilter').value;
    const filtered = minutes.filter((minute) => {
        const matchesQuery = [minute.id, minute.client, minute.adviser, minute.policy].some((value) => String(value ?? '').toLowerCase().includes(query));
        const matchesStatus = status === 'all' || minute.sendStatus === status;
        const percentage = minute.available / minute.contracted;
        const matchesPolicy = policy === 'all' || (policy === 'critical' && percentage <= .1) || (policy === 'active' && minute.validity === 'Vigente');
        return matchesQuery && matchesStatus && matchesPolicy;
    });
    byId('minutesTable').innerHTML = filtered.length ? filtered.map(minuteRow).join('') : '<tr><td colspan="8" class="muted">No se encontraron minutas con estos filtros.</td></tr>';
    byId('minuteCount').textContent = filtered.length;
    byId('tableStatus').textContent = `Mostrando ${filtered.length} de ${minutes.length} minutas`;
}

function openMinute(id) {
    const minute = minutes.find((item) => item.id === id);
    if (!minute) return;
    selectedMinuteId = id;
    byId('minuteModalTitle').textContent = minute.id;
    const folio = escapeHtml(minute.id);
    byId('minuteDocument').innerHTML = `
        <article class="minute-document">
            <header class="doc-head"><div class="doc-brand"><span class="brand-mark">i.</span><div><strong>Infodata</strong><small>Soluciones de tecnología empresarial</small></div></div><div class="doc-title"><span>Documento de servicio</span><h3>Minuta de servicio</h3><strong>${folio}</strong></div></header>
            <div class="doc-body">
                <div class="doc-meta"><div><span>Fecha</span><strong>${escapeHtml(minute.date)}</strong></div><div><span>Hora inicio</span><strong>${escapeHtml(minute.start)}</strong></div><div><span>Hora fin</span><strong>${escapeHtml(minute.end)}</strong></div><div><span>Tiempo real</span><strong>${escapeHtml(minute.actualDuration)}</strong></div><div><span>Descuento de póliza</span><strong>${escapeHtml(minute.discountedHours)} h</strong></div><div><span>Estatus</span><strong>${escapeHtml(minute.serviceStatus)}</strong></div></div>
                <section class="doc-section"><h4>Datos del cliente</h4><div class="doc-data-grid"><div><span>Cliente</span><strong>${escapeHtml(minute.client)}</strong></div><div><span>Póliza</span><strong>${escapeHtml(minute.policy)}</strong></div><div><span>Vigencia</span><strong>${escapeHtml(minute.validity)}</strong></div><div><span>Horas contratadas</span><strong>${escapeHtml(minute.contracted)} h</strong></div><div><span>Disponibles antes</span><strong>${escapeHtml(minute.availableBefore)} h</strong></div><div><span>Tiempo restante</span><strong>${escapeHtml(minute.available)} h (${escapeHtml(minute.remainingPercentage)}%)</strong></div><div><span>Consultor</span><strong>${escapeHtml(minute.adviser)}</strong></div>${minute.sourceService ? `<div><span>Servicio origen</span><strong>${escapeHtml(minute.sourceService)}</strong></div>` : ''}</div></section>
                <section class="doc-section"><h4>Participantes</h4><div class="doc-data-grid"><div><span>Quién reporta</span><strong>${escapeHtml(minute.reporter)}</strong></div><div><span>Quién recibe</span><strong>${escapeHtml(minute.recipient)}</strong></div><div><span>Correo</span><strong>${escapeHtml(minute.email)}</strong></div></div></section>
                <section class="doc-section"><h4>Falla reportada</h4><div class="doc-block red">${escapeHtml(minute.issue)}</div></section>
                <section class="doc-section"><h4>Servicio realizado</h4><div class="doc-block">${escapeHtml(minute.work)}</div></section>
                <section class="doc-section"><h4>Actividad pendiente</h4><div class="doc-block orange">${escapeHtml(minute.pending)}</div></section>
                <div class="signatures"><div>Firma del cliente</div><div>Firma del asesor</div></div>
                <footer class="doc-foot"><span>Infodata · ${new Date().toLocaleDateString('es-MX')}</span><span>${folio} · Página 1 de 1</span></footer>
            </div>
        </article>`;
    byId('minuteModal').classList.remove('is-hidden');
    document.body.style.overflow = 'hidden';
}

function openResend(id) {
    const minute = minutes.find((item) => item.id === id);
    if (!minute) return;
    selectedMinuteId = id;
    byId('resendData').innerHTML = `<span>Cliente</span><strong>${escapeHtml(minute.client)}</strong><span>No. de minuta</span><strong>${escapeHtml(minute.id)}</strong><span>Destinatario principal</span><strong>${escapeHtml(minute.email)}</strong>`;
    byId('resendModal').classList.remove('is-hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    byId(id).classList.add('is-hidden');
    document.body.style.overflow = '';
    if (id === 'newMinuteModal') {
        catalogRequest++;
        stopTimerUpdates();
        activeServiceSession = null;
        serviceCatalog.clear();
        byId('newMinuteForm').reset();
        byId('saeServiceSelect').replaceChildren();
    }
}

function renderMails() {
    const query = byId('mailSearch').value.trim().toLowerCase();
    const status = byId('mailStatusFilter').value;
    const filtered = mailItems.filter((mail) => {
        const matchesQuery = [mail.minute, mail.client, mail.to].some((value) => String(value ?? '').toLowerCase().includes(query));
        return matchesQuery && (status === 'all' || mail.status === status);
    });
    byId('mailTable').innerHTML = filtered.length ? filtered.map((mail) => `
        <tr class="mail-row ${mail.id === selectedMailId ? 'is-selected' : ''}" data-mail="${escapeHtml(mail.id)}"><td>${escapeHtml(mail.date)}</td><td><div class="client-cell"><strong>${escapeHtml(mail.minute)}</strong><small>${escapeHtml(mail.client)}</small></div></td><td>${escapeHtml(mail.type)}</td><td><span class="badge ${statusClass(mail.status)}">${escapeHtml(mail.status)}</span></td><td>${icon('eye')}</td></tr>
    `).join('') : '<tr><td colspan="5" class="muted">No se encontraron correos.</td></tr>';
    const selected = filtered.find((mail) => mail.id === selectedMailId) ?? filtered[0] ?? null;
    if (selected) {
        selectedMailId = selected.id;
        renderMailPreview(selected);
    } else {
        byId('mailPreview').innerHTML = '<div class="empty-state"><strong>Sin correos registrados</strong><span>Los envíos de minutas y las alertas de póliza aparecerán aquí.</span></div>';
    }
}

function renderMailPreview(mail) {
    const canSend = currentUser?.permissions.createMinutes && minutes.some((item) => item.id === mail.minute);
    const attachment = mail.hasAttachment
        ? `<div class="attachment">${icon('file')}<div><strong>${escapeHtml(mail.minute)}.pdf</strong><small>Minuta de servicio · PDF adjunto</small></div></div>`
        : '';
    const downloadButton = mail.downloadUrl
        ? `<a class="button secondary" href="/api/minutas/${escapeHtml(encodeURIComponent(mail.minute))}/pdf">${icon('download')}Descargar PDF</a>`
        : '';
    const resendButton = canSend
        ? `<button class="button primary" data-resend="${escapeHtml(mail.minute)}">${icon('send')}Reenviar</button>`
        : '';

    byId('mailPreview').innerHTML = `
        <header class="mail-preview-head"><span>Vista previa del correo</span><h2>${escapeHtml(mail.subject)}</h2></header>
        <dl class="mail-meta"><dt>Destinatario</dt><dd>${escapeHtml(mail.to)}</dd><dt>Copias</dt><dd>${escapeHtml(mail.cc || '—')}</dd><dt>Fecha</dt><dd>${escapeHtml(mail.date)}</dd><dt>Tipo</dt><dd>${escapeHtml(mail.type)}</dd><dt>Estatus</dt><dd><span class="badge ${statusClass(mail.status)}">${escapeHtml(mail.status)}</span></dd></dl>
        ${mail.error ? `<div class="mail-error"><strong>⚠ Error de envío</strong><br>${escapeHtml(mail.error)}</div>` : ''}
        <div class="mail-body">${escapeHtml(mail.body)}</div>
        ${attachment}
        <div class="mail-actions">${downloadButton}${resendButton}</div>`;
}

const settingsTemplates = {
    general: () => `<div class="settings-title"><h2>Datos generales</h2><p>Sistema de Minutas Infodata</p></div>`,
    mail: () => `<div class="settings-title"><h2>Correos y destinatarios</h2><p>Al generar la minuta, el sistema prepara el correo y el PDF. La cuenta de envío se administra desde el servidor.</p></div>`,
    templates: () => `<div class="settings-title"><h2>Plantillas de correo</h2><p>El mensaje incluye el folio, el trabajo realizado, la duración, las horas descontadas y el saldo de la póliza. Puedes consultar el mensaje y el PDF en el historial.</p></div>`,
    automation: () => `<div class="settings-title"><h2>Automatizaciones</h2><p>El cronómetro registra la duración y la aplicación conserva el saldo y el historial de envíos.</p></div><div class="info-box">La alerta se prepara cuando el saldo llega al umbral configurado. La bitácora registra el resultado.</div>`,
    sae: () => `<div class="settings-title"><h2>Aspel SAE</h2><p>La aplicación consulta las facturas y pólizas importadas de una copia de SAE.</p></div><form id="saeImportForm" class="form-grid"><label class="form-field full">Catálogo de servicios preparado para importar<input type="file" name="file" accept=".json,application/json" required><small>Selecciona el archivo JSON generado a partir del respaldo autorizado.</small></label><div class="form-field"><button class="button primary" type="submit">Importar catálogo</button></div><p class="muted" id="saeImportStatus" role="status"></p></form>`,
    users: () => `<div class="settings-title"><h2>Usuarios y permisos</h2><p>Cuentas autorizadas para usar el sistema.</p></div><button class="button primary" data-setting-action="show-user-form">${icon('user')}Agregar usuario</button><form class="form-grid is-hidden" id="newUserForm" style="margin-top:18px"><label class="form-field">Nombre<input name="name" required></label><label class="form-field">Correo<input name="email" type="email" required></label><label class="form-field">Rol<select name="role"><option value="consultant">Consultor</option><option value="administrator">Administrador</option><option value="auditor">Consulta / auditoría</option></select></label><label class="form-field">Contraseña temporal<input name="password" type="password" minlength="10" autocomplete="new-password" required></label><div class="form-field"><span>&nbsp;</span><button class="button secondary" type="submit">Guardar usuario</button></div></form><div class="table-wrap" style="margin-top:18px"><table class="user-list"><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estatus</th><th>Acciones</th></tr></thead><tbody id="usersTable">${userRows()}</tbody></table></div><div class="info-box" style="margin-top:18px">${icon('user')}<div><strong>Administrador:</strong> acceso completo. <strong>Consultor:</strong> consulta y genera minutas. <strong>Auditor:</strong> acceso de solo lectura a minutas y auditoría.</div></div>`,
    audit: () => `<div class="settings-title"><h2>Auditoría de operaciones</h2><p>Últimos accesos y cambios sensibles registrados por el servidor.</p></div><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Elemento</th><th>Dirección IP</th></tr></thead><tbody id="auditTable">${auditRows()}</tbody></table></div><div class="info-box" style="margin-top:18px">${icon('database')}<div>La bitácora conserva accesos correctos o fallidos, cierres de sesión, consultas de detalle, creación de minutas y altas de usuarios.</div></div>`
};

function userRows() {
    return users.length
        ? users.map((user) => `<tr><td><strong>${escapeHtml(user.name)}</strong></td><td>${escapeHtml(user.email)}</td><td>${escapeHtml(user.roleLabel)}</td><td><span class="badge ${statusClass(user.status)}">${escapeHtml(user.status)}</span></td><td><span class="muted">Controlado por rol</span></td></tr>`).join('')
        : '<tr><td colspan="5" class="muted">Consultando usuarios autorizados...</td></tr>';
}

function auditRows() {
    return auditLogs.length
        ? auditLogs.map((log) => `<tr><td>${escapeHtml(log.date)}</td><td><strong>${escapeHtml(log.user)}</strong><br><small>${escapeHtml(log.email)}</small></td><td><code>${escapeHtml(log.action)}</code></td><td>${escapeHtml(log.subject)}</td><td>${escapeHtml(log.ip)}</td></tr>`).join('')
        : '<tr><td colspan="5" class="muted">Consultando la bitácora...</td></tr>';
}

async function loadUsers() {
    try {
        const payload = await requestJson('/api/usuarios');
        users = payload.data ?? [];
        if (byId('usersTable')) byId('usersTable').innerHTML = userRows();
    } catch (error) {
        if (byId('usersTable')) byId('usersTable').innerHTML = `<tr><td colspan="5" class="muted">${escapeHtml(error.message)}</td></tr>`;
    }
}

async function loadAuditLogs() {
    try {
        const payload = await requestJson('/api/auditoria');
        auditLogs = payload.data ?? [];
        if (byId('auditTable')) byId('auditTable').innerHTML = auditRows();
    } catch (error) {
        if (byId('auditTable')) byId('auditTable').innerHTML = `<tr><td colspan="5" class="muted">${escapeHtml(error.message)}</td></tr>`;
    }
}

function renderSetting(name) {
    const renderer = settingsTemplates[name] ?? settingsTemplates.general;
    byId('settingsContent').innerHTML = renderer();
    document.querySelectorAll('#settingsNav button').forEach((button) => button.classList.toggle('is-active', button.dataset.setting === name));
    if (name === 'users') loadUsers();
    if (name === 'audit') loadAuditLogs();
}

function handleSettingAction(action) {
    if (action === 'show-user-form') byId('newUserForm')?.classList.toggle('is-hidden');
}

function initializeEvents() {
    byId('loginForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const submitButton = form.querySelector('button[type="submit"]');
        const errorElement = byId('loginError');
        errorElement.classList.add('is-hidden');
        submitButton.disabled = true;
        submitButton.textContent = 'Verificando acceso...';

        try {
            await login(
                byId('loginEmail').value.trim(),
                byId('loginPassword').value,
                form.querySelector('input[type="checkbox"]').checked,
            );
        } catch (error) {
            errorElement.textContent = error.message;
            errorElement.classList.remove('is-hidden');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Iniciar sesión';
        }
    });

    byId('togglePassword').addEventListener('click', () => {
        const password = byId('loginPassword');
        password.type = password.type === 'password' ? 'text' : 'password';
    });

    byId('logoutButton').addEventListener('click', async () => {
        try {
            await logout();
        } catch (error) {
            showToast(error.message);
        }
    });

    document.querySelectorAll('.nav-item').forEach((button) => button.addEventListener('click', () => navigate(button.dataset.view)));
    document.querySelectorAll('[data-go]').forEach((button) => button.addEventListener('click', () => navigate(button.dataset.go)));
    byId('menuButton').addEventListener('click', () => { byId('sidebar').classList.add('is-open'); byId('sidebarScrim').classList.remove('is-hidden'); });
    byId('sidebarScrim').addEventListener('click', () => { byId('sidebar').classList.remove('is-open'); byId('sidebarScrim').classList.add('is-hidden'); });

    ['minuteSearch', 'statusFilter', 'policyFilter'].forEach((id) => byId(id).addEventListener(id === 'minuteSearch' ? 'input' : 'change', filterMinutes));
    ['mailSearch', 'mailStatusFilter'].forEach((id) => byId(id).addEventListener(id === 'mailSearch' ? 'input' : 'change', renderMails));
    byId('saeServiceSelect').addEventListener('change', selectService);
    byId('startServiceButton').addEventListener('click', (event) => changeServiceTimer('iniciar', event.currentTarget));
    byId('stopServiceButton').addEventListener('click', (event) => changeServiceTimer('finalizar', event.currentTarget));
    byId('newMinuteForm').elements.used.addEventListener('input', (event) => event.target.setCustomValidity(''));

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

        if (minuteButton) openMinute(minuteButton.dataset.minute);
        if (resendButton) openResend(resendButton.dataset.resend);
        if (closeButton) closeModal(closeButton.dataset.close);
        if (refreshButton) {
            if (refreshButton.closest('#view-correos')) await loadMailDeliveries(true);
            else await loadMinutesFromDatabase();
        }
        if (newMinuteButton) await openNewMinute();
        if (downloadButton && selectedMinuteId) {
            window.location.href = `/api/minutas/${encodeURIComponent(selectedMinuteId)}/pdf`;
        }
        if (mailRow) { selectedMailId = Number(mailRow.dataset.mail); renderMails(); }
        if (settingTab) renderSetting(settingTab.dataset.setting);
        if (settingAction) handleSettingAction(settingAction.dataset.settingAction);
    });

    byId('confirmResend').addEventListener('click', async () => {
        const minute = minutes.find((item) => item.id === selectedMinuteId);
        if (!minute) return;
        const button = byId('confirmResend');
        button.disabled = true;
        try {
            const payload = await sendMinuteEmail(minute.id);
            closeModal('resendModal');
            await Promise.all([loadMinutesFromDatabase(false), loadMailDeliveries(false)]);
            navigate('correos');
            showToast(payload.message);
        } catch (error) {
            showToast(error.message);
        } finally {
            button.disabled = false;
        }
    });

    byId('settingsContent').addEventListener('submit', async (event) => {
        if (event.target.id === 'saeImportForm') {
            event.preventDefault();
            const form = event.target;
            const status = byId('saeImportStatus');
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            status.textContent = 'Validando el catálogo…';
            try {
                const payload = await requestJson('/api/servicios/importar', { method: 'POST', body: new FormData(form) });
                status.textContent = payload.message;
                form.reset();
            } catch (error) {
                status.textContent = error.message;
            } finally {
                button.disabled = false;
            }
            return;
        }
        if (event.target.id !== 'newUserForm') return;
        event.preventDefault();
        const data = new FormData(event.target);
        try {
            const payload = await requestJson('/api/usuarios', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: data.get('name'),
                    email: data.get('email'),
                    role: data.get('role'),
                    password: data.get('password'),
                }),
            });
            users.push(payload.data);
            renderSetting('users');
            showToast(payload.message);
        } catch (error) {
            showToast(error.message);
        }
    });

    byId('newMinuteForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const data = new FormData(form);
        const discountedHours = Number(data.get('used'));

        if (!activeServiceSession || activeServiceSession.status !== 'completed') {
            showToast('Primero debes finalizar el servicio.');
            return;
        }

        if (!Number.isFinite(discountedHours) || discountedHours <= 0) {
            form.elements.used.setCustomValidity('Indica un tiempo mayor que cero para descontar de la póliza.');
            form.elements.used.reportValidity();
            return;
        }

        if (discountedHours > Number(data.get('available'))) {
            form.elements.used.setCustomValidity('El descuento no puede superar las horas disponibles.');
            form.elements.used.reportValidity();
            return;
        }

        form.elements.used.setCustomValidity('');
        const minuteToSave = {
            sessionId: Number(data.get('sessionId')),
            used: Number(discountedHours.toFixed(2)),
            email: data.get('email').trim(),
            sourceService: data.get('sourceService'),
        };

        const submitButton = byId('generateMinuteButton');
        submitButton.disabled = true;
        form.elements.sourceService.disabled = true;

        try {
            const creationPayload = await saveMinuteToDatabase(minuteToSave);
            const savedMinute = creationPayload.data;
            minutes.unshift(savedMinute);
            applyServiceSession(null);
            byId('minuteSearch').value = '';
            byId('statusFilter').value = 'all';
            byId('policyFilter').value = 'all';
            closeModal('newMinuteModal');
            renderDashboard();
            filterMinutes();
            navigate('minutas');
            openMinute(savedMinute.id);

            try {
                const mailPayload = await sendMinuteEmail(savedMinute.id);
                await Promise.all([loadMinutesFromDatabase(false), loadMailDeliveries(false)]);
                const alertText = creationPayload.alert
                    ? ` También se generó la alerta de póliza al ${creationPayload.alert.percentage}%.`
                    : '';
                showToast(`${mailPayload.message}${alertText}`);
            } catch (mailError) {
                await loadMinutesFromDatabase(false);
                showToast(`La minuta ${savedMinute.id} se guardó, pero el correo presentó un error: ${mailError.message}`);
            }
        } catch (error) {
            showToast(error.message || 'No fue posible guardar la minuta.');
            submitButton.disabled = false;
            form.elements.sourceService.disabled = false;
        }
    });

    [byId('minuteModal'), byId('resendModal'), byId('newMinuteModal')].forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal.id); }));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { closeModal('minuteModal'); closeModal('resendModal'); closeModal('newMinuteModal'); } });
}

renderDashboard();
filterMinutes();
renderMails();
initializeEvents();
byId('topbarDate').textContent = new Intl.DateTimeFormat('es-MX', { dateStyle: 'full' }).format(new Date());

if (currentUser) {
    showApplication(currentUser);
    Promise.all([
        loadMinutesFromDatabase(false),
        loadMailDeliveries(false),
    ]);
} else {
    showLogin();
}
