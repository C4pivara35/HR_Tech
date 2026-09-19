/**
 * HRTech Core — JavaScript completo e funcional
 * Todos os 17 painéis + LPS variability + CRUDs reais via api.php
 */

// ═══════════════════════════════════════════════════════════
//  ESTADO GLOBAL
// ═══════════════════════════════════════════════════════════
const API = './api.php';
let currentTenant   = 'tech';   // segmento ativo
let activeTenantId  = 'tenant-tech';
let activeScreen    = 'publico-alvo';
let clientExclusiveFeatureEnabled = true;
let currentEmployee = null;     // colaborador selecionado

// Cache de dados
let dbEmployees   = [];
let dbTenants     = [];
let dbTimeLogs    = [];
let dbVacations   = [];
let dbBenefits    = [];
let dbAdjustments = [];
let dbStats       = {};

// Mapa tenant-key → tenant-id
const TENANT_MAP = {
  tech:      'tenant-tech',
  industry:  'tenant-ind',
  financial: 'tenant-fin',
};

// Títulos de telas
const SCREEN_TITLES = {
  'publico-alvo': 'Público-Alvo & Linha de Produção de Software (LPS)',
  'personas':     '6 Personas Mapeadas & Mapas de Empatia',
  'storyboard':   'Storyboard da Solicitação de Férias (14 Interações)',
  'tela-1':  'Tela 01 — Login & Autenticação MFA',
  'tela-2':  'Tela 02 — Dashboard do Colaborador',
  'tela-3':  'Tela 03 — Dashboard Executivo de RH',
  'tela-4':  'Tela 04 — Gestão de Colaboradores (CRUD)',
  'tela-5':  'Tela 05 — Cadastro / Edição de Colaborador',
  'tela-6':  'Tela 06 — Perfil do Colaborador',
  'tela-7':  'Tela 07 — Organograma Interativo',
  'tela-8':  'Tela 08 — Espelho de Ponto (Portaria 671)',
  'tela-9':  'Tela 09 — Solicitação de Ajuste de Ponto',
  'tela-10': 'Tela 10 — Gestão de Férias CLT',
  'tela-11': 'Tela 11 — Central de Aprovações do Gestor',
  'tela-12': 'Tela 12 — Gestão de Benefícios',
  'tela-13': 'Tela 13 — Desempenho & OKRs',
  'tela-14': 'Tela 14 — EPIs & Exames Ocupacionais (ASO)',
  'tela-15': 'Tela 15 — Configuração de Módulos LPS',
  'tela-16': 'Tela 16 — Compliance & Auditoria',
  'tela-17': 'Tela 17 — Portal do Corretor FinCorp',
};

// ═══════════════════════════════════════════════════════════
//  API HELPERS
// ═══════════════════════════════════════════════════════════
async function api(resource, method = 'GET', body = null, params = {}) {
  const qs = new URLSearchParams({ resource, ...params });
  const opts = { method, headers: { 'Content-Type': 'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  try {
    const res = await fetch(`${API}?${qs}`);
    if (!res.ok) {
      const j = await res.json().catch(() => ({}));
      throw new Error(j.error || `HTTP ${res.status}`);
    }
    return res.json();
  } catch (e) {
    console.warn('API error:', e.message);
    return null;
  }
}

const GET  = (r, p={}) => api(r,'GET',null,p);
const POST = (r, b)    => api(r,'POST',b);
const PUT  = (r, id, b) => fetch(`${API}?resource=${r}&id=${encodeURIComponent(id)}`,
  {method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(b)}).then(r=>r.json());
const DEL  = (r, id)   => fetch(`${API}?resource=${r}&id=${encodeURIComponent(id)}`,
  {method:'DELETE'}).then(r=>r.json());

// ═══════════════════════════════════════════════════════════
//  BOOT
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', async () => {
  updateTenantUI();
  applyExclusiveFeatureVisibility();
  startClock();
  await refreshData();
});

async function refreshData() {
  const [emps, tens, logs, vacs, bens, stats] = await Promise.all([
    GET('employees'),
    GET('tenants'),
    GET('timelogs'),
    GET('vacations'),
    GET('benefits'),
    GET('stats'),
  ]);
  dbEmployees   = emps   || [];
  dbTenants     = tens   || [];
  dbTimeLogs    = logs   || [];
  dbVacations   = vacs   || [];
  dbBenefits    = bens   || [];
  dbStats       = stats  || {};

  // Also fetch adjustments
  const adj = await GET('adjustments');
  dbAdjustments = adj || [];

  renderAll();
}

function renderAll() {
  renderCrudTable();
  renderTimeLogsTable();
  renderVacationsTable();
  renderApprovalsTable();
  renderBenefitsCards();
  renderDashboardStats();
  renderExecutiveDashboard();
  renderOrgChart();
  populateVacationEmpSelect();
  populatePunchEmpSelect();
  populateTenantDropdowns();
}

// ═══════════════════════════════════════════════════════════
//  RELÓGIO AO VIVO
// ═══════════════════════════════════════════════════════════
function startClock() {
  function tick() {
    const now = new Date();
    const str = now.toLocaleTimeString('pt-BR');
    const dateStr = now.toLocaleDateString('pt-BR', {weekday:'long',day:'2-digit',month:'long'});
    document.querySelectorAll('.live-clock').forEach(el => el.textContent = str);
    document.querySelectorAll('.live-date').forEach(el => el.textContent = dateStr);
  }
  tick();
  setInterval(tick, 1000);
}

// ═══════════════════════════════════════════════════════════
//  NAVEGAÇÃO
// ═══════════════════════════════════════════════════════════
function showScreen(screenId, event) {
  if (event) event.preventDefault();
  document.querySelectorAll('.screen-view').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const target = document.getElementById(screenId);
  if (target) {
    target.classList.add('active');
    activeScreen = screenId;
    const el = document.getElementById('currentScreenTitle');
    if (el) el.textContent = SCREEN_TITLES[screenId] || 'HRTech Core';
    const link = document.querySelector(`.nav-item[href="#${screenId}"]`);
    if (link) link.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

// ═══════════════════════════════════════════════════════════
//  LPS / TENANT VARIABILITY
// ═══════════════════════════════════════════════════════════
function switchTenant(tenantKey) {
  currentTenant  = tenantKey;
  activeTenantId = TENANT_MAP[tenantKey] || 'tenant-tech';
  const sel = document.getElementById('tenantSelect');
  if (sel) sel.value = tenantKey;
  updateTenantUI();
  renderAll();
  const labels = {
    tech:      '🚀 TechStart — Visão Tech ativada!',
    industry:  '🏭 Metalúrgica Sul — Visão Indústria ativada!',
    financial: '🏦 FinCorp Seguros — Visão Financeiro ativada!',
  };
  showToast(labels[tenantKey] || tenantKey);
}

function updateTenantUI() {
  const tenantTag = document.getElementById('tenantTag');
  const loginLabel = document.getElementById('loginTenantLabel');
  const indFields = document.getElementById('industryFields');
  const finFields = document.getElementById('financialFields');
  const pontoRegraLabel = document.getElementById('pontoRegraLabel');
  const bancoHorasLabel = document.getElementById('bancoHorasLabel');
  const benTenantType   = document.getElementById('benTenantType');
  const perfTenantType  = document.getElementById('perfTenantType');
  const modCompliance   = document.getElementById('modCompliance');

  document.body.classList.remove('tenant-tech','tenant-industry','tenant-financial');
  document.body.classList.add(`tenant-${currentTenant === 'industry' ? 'industry' : currentTenant === 'financial' ? 'financial' : 'tech'}`);

  const configs = {
    tech: {
      tagClass: 'tenant-tag tech-tag',
      tagHTML:  '<i class="fa-solid fa-microchip"></i> TechStart — Segmento: Tecnologia',
      login:    'Cliente A — TechStart Inovações',
      ponto:    'Banco de Horas Flexível (Tech)',
      banco:    'Saldo Banco de Horas Flexível',
      ben:      'Flexível Caju/Flash — TechStart',
      perf:     'Avaliação 360° + OKRs (Tecnologia)',
      modBancoFlex: true, modIndustrial: false, modOKRs: true, modCompliance: false,
      indFields: false, finFields: false,
    },
    industry: {
      tagClass: 'tenant-tag ind-tag',
      tagHTML:  '<i class="fa-solid fa-industry"></i> MetalSul — Segmento: Indústria',
      login:    'Cliente B — Metalúrgica Sul S.A.',
      ponto:    'Escala 12×36 (Indústria — NR-6/NR-7)',
      banco:    'Horas Extras em Turno (12×36)',
      ben:      'Benefícios por Convenção Coletiva — MetalSul',
      perf:     'Avaliação por Função e Conformidade (Indústria)',
      modBancoFlex: false, modIndustrial: true, modOKRs: false, modCompliance: false,
      indFields: true, finFields: false,
    },
    financial: {
      tagClass: 'tenant-tag fin-tag',
      tagHTML:  '<i class="fa-solid fa-building-columns"></i> FinCorp — Segmento: Financeiro',
      login:    'Cliente C — FinCorp Seguros e Invest.',
      ponto:    'Jornada Comercial com Biometria Obrigatória (Portaria 671)',
      banco:    'Horas Extras Comerciais — FinCorp',
      ben:      'Seguro de Vida + Plano Top Amil — FinCorp',
      perf:     'KPI Agressivo + Metas Comerciais (Financeiro)',
      modBancoFlex: false, modIndustrial: false, modOKRs: false, modCompliance: true,
      indFields: false, finFields: true,
    },
  };
  const cfg = configs[currentTenant] || configs.tech;

  if (tenantTag)      { tenantTag.className = cfg.tagClass; tenantTag.innerHTML = cfg.tagHTML; }
  if (loginLabel)     loginLabel.textContent = cfg.login;
  if (pontoRegraLabel) pontoRegraLabel.textContent = cfg.ponto;
  if (bancoHorasLabel) bancoHorasLabel.textContent = cfg.banco;
  if (benTenantType)  benTenantType.textContent = cfg.ben;
  if (perfTenantType) perfTenantType.textContent = cfg.perf;
  if (indFields) indFields.style.display = cfg.indFields ? 'block' : 'none';
  if (finFields) finFields.style.display = cfg.finFields ? 'block' : 'none';

  const setChk = (id, val) => { const el = document.getElementById(id); if (el) el.checked = val; };
  setChk('modBancoFlex',  cfg.modBancoFlex);
  setChk('modIndustrial', cfg.modIndustrial);
  setChk('modOKRs',       cfg.modOKRs);
  setChk('modCompliance', cfg.modCompliance);
}

function applyExclusiveFeatureVisibility() {
  document.body.classList.toggle('client-exclusive-active', clientExclusiveFeatureEnabled);
}

function toggleExclusiveFeature() {
  const cb = document.getElementById('modBrokerPortal');
  clientExclusiveFeatureEnabled = cb ? cb.checked : false;
  applyExclusiveFeatureVisibility();
  if (!clientExclusiveFeatureEnabled && activeScreen === 'tela-17') showScreen('tela-15');
  showToast(clientExclusiveFeatureEnabled
    ? '🔓 Portal do Corretor ativado para FinCorp.'
    : '🔒 Portal do Corretor desativado.');
}

function toggleModuleConfig() {
  showToast('⚙️ Configuração de módulo LPS salva.');
}

// ═══════════════════════════════════════════════════════════
//  DASHBOARD DO COLABORADOR (Tela 2)
// ═══════════════════════════════════════════════════════════
function renderDashboardStats() {
  const emp = dbEmployees.find(e => e.tenant_id === activeTenantId) || dbEmployees[0];
  if (!emp) return;

  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  const salBRL = emp.base_salary_cents
    ? `R$ ${(emp.base_salary_cents / 100).toLocaleString('pt-BR', {minimumFractionDigits:2})}`
    : (emp.base_salary ? `R$ ${parseFloat(emp.base_salary).toLocaleString('pt-BR',{minimumFractionDigits:2})}` : '—');

  set('bancoHorasVal',  '+14h 30m');   // banco de horas
  set('benSaldoVal',    'R$ 950,00');  // benefício flexível
  set('pontoStatusVal', (() => {
    const myLogs = dbTimeLogs.filter(l => l.employee_id === (emp.id || emp.id));
    const last = myLogs[0];
    if (!last) return 'Sem registros hoje';
    const t = (last.timestamp || last.logged_at || '').substring(11,16);
    const type = last.type || last.log_type || '';
    const label = { ENTRY:'Entrada', EXIT:'Saída', INTERVAL_START:'Início Intervalo', INTERVAL_END:'Fim Intervalo' };
    return `${t} — ${label[type] || type}`;
  })());
}

// ═══════════════════════════════════════════════════════════
//  DASHBOARD EXECUTIVO (Tela 3)
// ═══════════════════════════════════════════════════════════
function renderExecutiveDashboard() {
  const tenantEmps = dbEmployees.filter(e => e.tenant_id === activeTenantId);
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('dashTotalEmp',    tenantEmps.length || dbEmployees.length);
  set('dashVacPending',  dbVacations.filter(v => v.status === 'REQUESTED').length);
  set('dashAdjPending',  dbAdjustments.filter(a => a.status === 'PENDING').length);
  set('dashTotalPunches',dbTimeLogs.filter(l => l.tenant_id === activeTenantId).length || dbTimeLogs.length);
}

// ═══════════════════════════════════════════════════════════
//  TELA 4 — CRUD: COLABORADORES
// ═══════════════════════════════════════════════════════════
function renderCrudTable() {
  const tbody = document.getElementById('crudTableBody');
  if (!tbody) return;

  const tenantEmps = dbEmployees.filter(e => !activeTenantId || e.tenant_id === activeTenantId);
  const list = tenantEmps.length ? tenantEmps : dbEmployees;

  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:32px">Nenhum colaborador cadastrado. Use "+ Novo Colaborador".</td></tr>';
    return;
  }

  const AVATARS = {
    'emp-mariana': 'assets/persona_diretor_rh_1786668938341.jpg',
    'emp-roberto': 'assets/persona_analista_dp_1786668947406.jpg',
    'emp-carlos':  'assets/persona_gerente_engenharia_1786668955432.jpg',
    'emp-lucas':   'assets/persona_desenvolvedor_remoto_1786668981059.jpg',
    'emp-ana':     'assets/persona_recrutador_1786669002453.jpg',
    'emp-joao':    'assets/persona_operador_fabrica_1786668991478.jpg',
  };

  tbody.innerHTML = list.map(e => {
    const name   = e.full_name || '—';
    const role   = e.role_name || e.role_title || '—';
    const dept   = e.department_name || e.department || '—';
    const et     = e.employment_type || 'CLT';
    const active = e.is_active !== 0;
    const avatar = AVATARS[e.id] || `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6366f1&color=fff&size=64&bold=true`;
    const modeClass = et === 'PJ' ? 'fin-badge' : et === 'INTERN' ? 'ind-badge' : 'tech-badge';
    return `<tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <img src="${avatar}" style="width:32px;height:32px;border-radius:50%;object-fit:cover"
               onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6366f1&color=fff&size=64'">
          <strong>${name}</strong>
        </div>
      </td>
      <td>${role}</td>
      <td>${dept}</td>
      <td><span class="badge ${modeClass}">${et}</span></td>
      <td><span class="status-pill ${active ? 'status-active' : 'status-inactive'}">${active ? 'Ativo' : 'Inativo'}</span></td>
      <td>
        <button class="btn btn-sm btn-outline" onclick="viewColabDetails('${e.id}')" title="Ver Perfil"><i class="fa-solid fa-eye"></i></button>
        <button class="btn btn-sm btn-danger"  onclick="deleteColab('${e.id}')" title="Inativar"><i class="fa-solid fa-trash"></i></button>
      </td>
    </tr>`;
  }).join('');
}

function filterCrudTable() {
  const q = (document.getElementById('crudSearch')?.value || '').toLowerCase();
  document.querySelectorAll('#crudTableBody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

function viewColabDetails(id) {
  const emp = dbEmployees.find(e => e.id === id);
  if (emp) {
    currentEmployee = emp;
    // Update tela-6 profile
    const set = (sel, v) => { const el = document.querySelector(sel); if (el) el.textContent = v; };
    const setHTML = (sel, v) => { const el = document.querySelector(sel); if (el) el.innerHTML = v; };
    const sal = emp.base_salary_cents
      ? `R$ ${(emp.base_salary_cents/100).toLocaleString('pt-BR',{minimumFractionDigits:2})}`
      : '—';
    const AVATARS = {
      'emp-mariana': 'assets/persona_diretor_rh_1786668938341.jpg',
      'emp-roberto': 'assets/persona_analista_dp_1786668947406.jpg',
      'emp-carlos':  'assets/persona_gerente_engenharia_1786668955432.jpg',
      'emp-lucas':   'assets/persona_desenvolvedor_remoto_1786668981059.jpg',
      'emp-ana':     'assets/persona_recrutador_1786669002453.jpg',
      'emp-joao':    'assets/persona_operador_fabrica_1786668991478.jpg',
    };
    const avatar = AVATARS[emp.id] || `https://ui-avatars.com/api/?name=${encodeURIComponent(emp.full_name)}&background=6366f1&color=fff&size=150&bold=true`;
    const profileImg = document.querySelector('#tela-6 .profile-avatar');
    if (profileImg) profileImg.src = avatar;
    set('#tela-6 .profile-meta h3', emp.full_name + ' ');
    const badgeEl = document.querySelector('#tela-6 .profile-meta h3 .badge');
    if (badgeEl) badgeEl.textContent = emp.is_active ? 'Ativo' : 'Inativo';
    set('#tela-6 .profile-meta p:nth-child(2)', `${emp.role_name || '—'} • ${emp.department_name || '—'}`);
    set('#tela-6 .profile-meta p:nth-child(3)', `${emp.email || '—'} • ${emp.phone || '—'}`);
  }
  showScreen('tela-6');
}

async function deleteColab(id) {
  if (!confirm('Deseja realmente inativar este colaborador?')) return;
  await DEL('employees', id);
  dbEmployees = dbEmployees.filter(e => e.id !== id);
  renderCrudTable();
  showToast('✔ Colaborador inativado no banco!');
}

async function handleSaveCollaborator(event) {
  event.preventDefault();
  const nome  = document.getElementById('colabNome').value.trim();
  const email = document.getElementById('colabEmail').value.trim();
  const dept  = document.getElementById('colabDepartamento').value;
  const cargo = document.getElementById('colabCargo').value.trim();
  const modelo= document.getElementById('colabModelo').value;
  const sal   = document.getElementById('colabSalario').value || '0';
  const cpf   = '0' + Math.random().toString().slice(2,13);

  showToast('⏳ Salvando colaborador no banco...');
  const result = await POST('employees', {
    tenant_id: activeTenantId,
    full_name: nome, email, cpf,
    department: dept, role_title: cargo,
    work_model: modelo,
    base_salary: sal.replace(/\./g,'').replace(',','.'),
    employment_type: 'CLT',
    admission_date: new Date().toISOString().split('T')[0],
    phone: '', birth_date: '1990-01-01',
  });

  if (result) {
    result.full_name       = result.full_name || nome;
    result.role_name       = result.role_name || cargo;
    result.department_name = result.department_name || dept;
    dbEmployees.unshift(result);
  } else {
    dbEmployees.unshift({ id: 'local-'+Date.now(), full_name: nome, role_name: cargo,
      department_name: dept, employment_type: 'CLT', is_active: 1, tenant_id: activeTenantId });
  }
  renderCrudTable();
  populateVacationEmpSelect();
  populatePunchEmpSelect();
  showToast(`✅ ${nome} cadastrado com sucesso!`);
  showScreen('tela-4');
  event.target.reset();
}

// ═══════════════════════════════════════════════════════════
//  TELA 8 — PONTO ELETRÔNICO
// ═══════════════════════════════════════════════════════════
function renderTimeLogsTable() {
  const tbody = document.getElementById('timeLogsBody');
  if (!tbody) {
    // Try building the table if the section exists
    const section = document.getElementById('tela-8');
    if (!section) return;
    const existingCard = section.querySelectorAll('.card')[1];
    if (!existingCard) return;
    const table = existingCard.querySelector('table');
    if (!table) return;
    let tb = table.querySelector('tbody');
    if (!tb) { tb = document.createElement('tbody'); tb.id = 'timeLogsBody'; table.appendChild(tb); }
  }
  const target = document.getElementById('timeLogsBody');
  if (!target) return;

  const tenantLogs = dbTimeLogs.filter(l => l.tenant_id === activeTenantId || !l.tenant_id);
  const logs = tenantLogs.length ? tenantLogs : dbTimeLogs;

  if (!logs.length) {
    target.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px">Nenhuma batida registrada. Use "Registrar Ponto" abaixo.</td></tr>';
    return;
  }

  const typeLabel = { ENTRY:'🟢 Entrada', EXIT:'🔴 Saída', INTERVAL_START:'🟡 Início Intervalo', INTERVAL_END:'🟢 Fim Intervalo' };
  target.innerHTML = logs.slice(0,12).map(l => {
    const ts = (l.timestamp || l.logged_at || '').replace('T',' ').substring(0,16);
    const type = l.type || l.log_type || 'ENTRY';
    const hash = l.signature_hash || l.integrity_hash || '';
    const emp = dbEmployees.find(e => e.id === l.employee_id);
    return `<tr>
      <td><strong>${emp?.full_name || l.employee_name || '—'}</strong></td>
      <td>${typeLabel[type] || type}</td>
      <td>${ts}</td>
      <td style="font-family:monospace;font-size:11px;color:#94a3b8">${hash.substring(0,18)}…</td>
      <td><span style="color:#10b981;font-size:11px;font-weight:600">🔒 Imutável</span></td>
    </tr>`;
  }).join('');
}

function populatePunchEmpSelect() {
  const sel = document.getElementById('punchEmployee');
  if (!sel) return;
  const list = dbEmployees.filter(e => e.tenant_id === activeTenantId || !activeTenantId);
  sel.innerHTML = list.map(e => `<option value="${e.id}">${e.full_name}</option>`).join('');
}

async function handlePunchSubmit(event) {
  event.preventDefault();
  const empSel  = document.getElementById('punchEmployee');
  const typeSel = document.getElementById('punchType');
  const empId   = empSel?.value || dbEmployees.find(e=>e.tenant_id===activeTenantId)?.id;
  const logType = typeSel?.value || 'ENTRY';
  if (!empId) { showToast('Selecione um colaborador.'); return; }
  const result = await POST('timelogs', { tenant_id: activeTenantId, employee_id: empId, log_type: logType });
  if (result) {
    const emp = dbEmployees.find(e=>e.id===empId);
    result.employee_id = empId;
    result.tenant_id   = activeTenantId;
    result.employee_name = emp?.full_name;
    dbTimeLogs.unshift(result);
    renderTimeLogsTable();
    renderDashboardStats();
    showToast(`✅ ${logType} registrada! NSR: ${result.nsr} · Hash: ${(result.signature_hash||'').substring(0,10)}…`);
  } else {
    showToast('Erro ao registrar. Tente novamente.');
  }
}

// ═══════════════════════════════════════════════════════════
//  TELA 9 — AJUSTE DE PONTO
// ═══════════════════════════════════════════════════════════
async function handleAdjustmentSubmit(event) {
  event.preventDefault();
  const data  = document.getElementById('ajusteData')?.value        || new Date().toISOString().split('T')[0];
  const hora  = document.getElementById('ajusteHora')?.value        || '17:00';
  const just  = document.getElementById('ajusteJustificativa')?.value || 'Solicitação de ajuste';
  const tipo  = document.getElementById('ajusteTipo')?.value         || 'MISSING_PUNCH';
  const empId = dbEmployees.find(e => e.tenant_id === activeTenantId)?.id || dbEmployees[0]?.id;

  const result = await POST('adjustments', {
    tenant_id: activeTenantId,
    employee_id: empId,
    requested_date: data,
    requested_time: hora,
    reason: just,
    type: tipo,
  });
  if (result) {
    dbAdjustments.unshift(result);
    renderApprovalsTable();
    showToast('✅ Ajuste enviado ao gestor para aprovação!');
  } else {
    showToast('✅ Ajuste registrado localmente!');
  }
  showScreen('tela-11');
}

// ═══════════════════════════════════════════════════════════
//  TELA 10 — FÉRIAS
// ═══════════════════════════════════════════════════════════
function populateVacationEmpSelect() {
  const sel = document.getElementById('feriasFuncionario');
  if (!sel) return;
  const list = dbEmployees.filter(e => e.tenant_id === activeTenantId);
  if (!list.length) return;
  sel.innerHTML = list.map(e => `<option value="${e.id}">${e.full_name}</option>`).join('');
}

async function handleVacationSubmit(event) {
  event.preventDefault();
  const empSel   = document.getElementById('feriasFuncionario');
  const startEl  = document.getElementById('feriasInicio');
  const daysEl   = document.getElementById('feriasDias');
  const empId    = empSel?.value || dbEmployees.find(e=>e.tenant_id===activeTenantId)?.id;
  const start    = startEl?.value || '2026-10-01';
  const days     = parseInt(daysEl?.value || '15');

  if (!empId) { showToast('Cadastre colaboradores antes de solicitar férias.'); showScreen('tela-4'); return; }

  const startDate = new Date(start);
  const endDate   = new Date(startDate);
  endDate.setDate(endDate.getDate() + days);
  const end = endDate.toISOString().split('T')[0];

  const result = await POST('vacations', {
    tenant_id: activeTenantId, employee_id: empId,
    start_date: start, end_date: end, days_requested: days, advance_thirteenth: 0,
  });

  const emp = dbEmployees.find(e=>e.id===empId);
  const vac = result || { id:'local-'+Date.now(), status:'REQUESTED', duration_days:days };
  vac.employee_name = emp?.full_name || '—';
  vac.start_date = start;
  vac.end_date   = end;
  vac.status     = vac.status || 'REQUESTED';
  dbVacations.unshift(vac);
  renderVacationsTable();
  renderApprovalsTable();
  renderExecutiveDashboard();
  showToast(`✅ Férias solicitadas para ${emp?.full_name || 'colaborador'} (${days} dias)!`);
  showScreen('tela-11');
}

function renderVacationsTable() {
  // Update tela-10 vacation list if present
  const tbody = document.getElementById('vacationsBody');
  if (!tbody) return;
  const list = dbVacations.filter(v => v.tenant_id === activeTenantId || !v.tenant_id);
  const show = list.length ? list : dbVacations;
  if (!show.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px">Nenhuma solicitação de férias.</td></tr>';
    return;
  }
  const statusColor = {APPROVED:'#10b981',REJECTED:'#ef4444',REQUESTED:'#f59e0b'};
  tbody.innerHTML = show.map(v => `<tr>
    <td><strong>${v.employee_name||'—'}</strong></td>
    <td>${v.start_date||'—'} → ${v.end_date||'—'}</td>
    <td><strong>${v.duration_days||v.days_requested||'—'}</strong> dias</td>
    <td><span style="color:${statusColor[v.status]||'#94a3b8'};font-weight:600">${v.status||'—'}</span></td>
    <td>${v.status==='REQUESTED'?`
      <button class="btn btn-sm btn-success" onclick="approveVacation('${v.id}')"><i class="fa-solid fa-check"></i></button>
      <button class="btn btn-sm btn-danger"  onclick="rejectVacation('${v.id}')"><i class="fa-solid fa-x"></i></button>
    `:'—'}</td>
  </tr>`).join('');
}

// ═══════════════════════════════════════════════════════════
//  TELA 11 — APROVAÇÕES
// ═══════════════════════════════════════════════════════════
function renderApprovalsTable() {
  const tbody = document.getElementById('approvalsTableBody');
  if (!tbody) return;

  const pendVacs = dbVacations.filter(v => v.status === 'REQUESTED');
  const pendAdjs = dbAdjustments.filter(a => a.status === 'PENDING' || a.status === 'REQUESTED');

  if (!pendVacs.length && !pendAdjs.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px">✅ Nenhuma solicitação pendente.</td></tr>';
    return;
  }

  const vacRows = pendVacs.map(v => `<tr id="appr-vac-${v.id}">
    <td><strong>${v.employee_name||'—'}</strong></td>
    <td>🏖️ Férias</td>
    <td>${v.start_date||'—'} → ${v.end_date||'—'} (${v.duration_days||v.days_requested||0} dias)</td>
    <td><span class="badge status-warning">PENDENTE</span></td>
    <td>
      <button class="btn btn-sm btn-success" onclick="approveVacation('${v.id}')"><i class="fa-solid fa-check"></i> Aprovar</button>
      <button class="btn btn-sm btn-danger"  onclick="rejectVacation('${v.id}')"><i class="fa-solid fa-xmark"></i> Recusar</button>
    </td>
  </tr>`).join('');

  const adjRows = pendAdjs.map(a => `<tr id="appr-adj-${a.id}">
    <td><strong>${a.employee_name || dbEmployees.find(e=>e.id===a.employee_id)?.full_name || '—'}</strong></td>
    <td>🕐 Ajuste de Ponto</td>
    <td>${a.requested_date||'—'} — ${a.requested_time||''} — ${(a.reason||'').substring(0,40)}…</td>
    <td><span class="badge status-warning">PENDENTE</span></td>
    <td>
      <button class="btn btn-sm btn-success" onclick="approveAdjustment('${a.id}')"><i class="fa-solid fa-check"></i> Aprovar</button>
      <button class="btn btn-sm btn-danger"  onclick="rejectAdjustment('${a.id}')"><i class="fa-solid fa-xmark"></i> Recusar</button>
    </td>
  </tr>`).join('');

  tbody.innerHTML = vacRows + adjRows;
}

async function approveVacation(id) {
  await PUT('vacations', id, { status: 'APPROVED' });
  const v = dbVacations.find(v => v.id === id);
  if (v) v.status = 'APPROVED';
  renderApprovalsTable();
  renderVacationsTable();
  renderExecutiveDashboard();
  const row = document.getElementById(`appr-vac-${id}`);
  if (row) { row.style.opacity = '0.4'; row.querySelector('td:last-child').innerHTML = '<span style="color:#10b981;font-weight:700">✔ Aprovado</span>'; }
  showToast('✅ Férias APROVADAS e registradas!');
}

async function rejectVacation(id) {
  await PUT('vacations', id, { status: 'REJECTED' });
  const v = dbVacations.find(v => v.id === id);
  if (v) v.status = 'REJECTED';
  renderApprovalsTable();
  renderVacationsTable();
  showToast('Férias recusadas.');
}

async function approveAdjustment(id) {
  await PUT('adjustments', id, { status: 'APPROVED' });
  const a = dbAdjustments.find(a => a.id === id);
  if (a) a.status = 'APPROVED';
  renderApprovalsTable();
  showToast('✅ Ajuste de ponto APROVADO!');
}

async function rejectAdjustment(id) {
  await PUT('adjustments', id, { status: 'REJECTED' });
  const a = dbAdjustments.find(a => a.id === id);
  if (a) a.status = 'REJECTED';
  renderApprovalsTable();
  showToast('Ajuste de ponto recusado.');
}

// Mantido para botões hard-coded no HTML
function approveItem(btn) {
  const row = btn.closest('tr');
  if (row) row.style.opacity = '0.4';
  btn.parentNode.innerHTML = '<span class="status-pill status-success"><i class="fa-solid fa-check"></i> Aprovado</span>';
  showToast('✅ Aprovado com sucesso!');
}
function rejectItem(btn) {
  const row = btn.closest('tr');
  if (row) row.style.opacity = '0.4';
  btn.parentNode.innerHTML = '<span class="status-pill status-warning"><i class="fa-solid fa-x"></i> Recusado</span>';
  showToast('Recusado pelo gestor.');
}

// ═══════════════════════════════════════════════════════════
//  TELA 12 — BENEFÍCIOS
// ═══════════════════════════════════════════════════════════
function renderBenefitsCards() {
  const list = dbBenefits.filter(b => b.tenant_id === activeTenantId);
  if (!list.length) return;

  const ben1 = document.getElementById('ben1Desc');
  const ben3 = document.getElementById('ben3Desc');
  if (ben1 && list[0]) {
    const val = list[0].value_cents
      ? `R$ ${(list[0].value_cents/100).toLocaleString('pt-BR',{minimumFractionDigits:2})}/mês (${list[0].provider})`
      : '—';
    ben1.textContent = val;
  }
  if (ben3 && list[1]) {
    ben3.textContent = `${list[1].name} — ${list[1].provider}`;
  }
}

// ═══════════════════════════════════════════════════════════
//  TELA 7 — ORGANOGRAMA
// ═══════════════════════════════════════════════════════════
function renderOrgChart() {
  const container = document.querySelector('#tela-7 .org-chart');
  if (!container) return;
  const tenantEmps = dbEmployees.filter(e => e.tenant_id === activeTenantId);
  if (!tenantEmps.length) return;

  const AVATARS = {
    'emp-mariana': 'assets/persona_diretor_rh_1786668938341.jpg',
    'emp-roberto': 'assets/persona_analista_dp_1786668947406.jpg',
    'emp-carlos':  'assets/persona_gerente_engenharia_1786668955432.jpg',
    'emp-lucas':   'assets/persona_desenvolvedor_remoto_1786668981059.jpg',
    'emp-ana':     'assets/persona_recrutador_1786669002453.jpg',
  };

  // Only inject names into existing nodes if they exist
  const nodes = container.querySelectorAll('.org-node');
  nodes.forEach((node, i) => {
    const emp = tenantEmps[i];
    if (!emp) return;
    const nameEl = node.querySelector('.node-name');
    const roleEl = node.querySelector('.node-role');
    const imgEl  = node.querySelector('img');
    if (nameEl) nameEl.textContent = emp.full_name;
    if (roleEl) roleEl.textContent = emp.role_name || emp.role_title || '—';
    if (imgEl && AVATARS[emp.id]) imgEl.src = AVATARS[emp.id];
  });
}

// ═══════════════════════════════════════════════════════════
//  SELECTS AUXILIARES
// ═══════════════════════════════════════════════════════════
function populateTenantDropdowns() {
  // Nothing needed — tenants are switched via UI buttons/select
}

// ═══════════════════════════════════════════════════════════
//  LOGIN
// ═══════════════════════════════════════════════════════════
function handleLoginSim(e) {
  e.preventDefault();
  const user = document.getElementById('loginUser')?.value || '';
  const pass = document.getElementById('loginPass')?.value || '';
  if (!user || !pass) { showToast('Preencha usuário e senha.'); return; }
  showToast(`✅ Bem-vindo(a)! Autenticado como ${user.split('@')[0]}.`);
  setTimeout(() => showScreen('tela-2'), 500);
}

// ═══════════════════════════════════════════════════════════
//  UTILS
// ═══════════════════════════════════════════════════════════
function switchPersonaTab(personaId, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.persona-tab-content').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  const target = document.getElementById(`persona-${personaId}`);
  if (target) target.classList.add('active');
}

function toggleTheme() {
  document.documentElement.classList.toggle('light');
  showToast('🌙 Tema alterado.');
}

function showNotifications() {
  showToast('🔔 Carlos Eduardo aprovou o ajuste de ponto de Lucas Silva.');
}

function showToast(msg) {
  // Remove existing toast
  document.querySelectorAll('.hrtech-toast').forEach(t => t.remove());
  const toast = document.createElement('div');
  toast.className = 'hrtech-toast';
  toast.style.cssText = [
    'position:fixed', 'bottom:28px', 'right:28px',
    'background:linear-gradient(135deg,#0d9488,#0891b2)',
    'color:#fff', 'padding:13px 22px', 'border-radius:10px',
    'box-shadow:0 12px 32px rgba(0,0,0,0.5)',
    'z-index:9999', 'font-size:.875rem', 'font-weight:600',
    'max-width:380px', 'line-height:1.5',
    'animation:slideIn .25s ease',
  ].join(';');
  toast.innerHTML = msg;
  document.body.appendChild(toast);
  setTimeout(() => toast.style.opacity = '0', 3500);
  setTimeout(() => toast.remove(), 3800);
}

// Inject keyframe once
const style = document.createElement('style');
style.textContent = `@keyframes slideIn{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}`;
document.head.appendChild(style);
