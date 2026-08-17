/**
 * HRTech Core - Single Page Interactive Application Logic
 */

// Initial State Data
let currentTenant = 'tech'; // 'tech' or 'industry'
let activeScreen = 'publico-alvo';

// Sample CRUD Data for Collaborators
let collaborators = [
  { id: 1, name: 'Mariana Santos', role: 'Diretora de RH & Operations', dept: 'Recursos Humanos', mode: 'Híbrido (Flexível)', status: 'Ativo', avatar: 'assets/persona_diretor_rh_1786668938341.jpg' },
  { id: 2, name: 'Roberto Lima', role: 'Analista de Departamento Pessoal', dept: 'Recursos Humanos', mode: 'Presencial (Turnos Fixo)', status: 'Ativo', avatar: 'assets/persona_analista_dp_1786668947406.jpg' },
  { id: 3, name: 'Carlos Eduardo', role: 'Gerente de Engenharia', dept: 'Tecnologia & Engenharia', mode: 'Híbrido (Flexível)', status: 'Ativo', avatar: 'assets/persona_gerente_engenharia_1786668955432.jpg' },
  { id: 4, name: 'Lucas Silva', role: 'Senior Python/React Developer', dept: 'Tecnologia & Engenharia', mode: 'Remoto (Banco Flexível)', status: 'Ativo', avatar: 'assets/persona_desenvolvedor_remoto_1786668981059.jpg' },
  { id: 5, name: 'João Oliveira', role: 'Líder de Produção Industrial', dept: 'Operações Industriais', mode: 'Presencial (Escala 12x36)', status: 'Ativo', avatar: 'assets/persona_operador_fabrica_1786668991478.jpg' },
  { id: 6, name: 'Ana Souza', role: 'Recrutadora & Talent Acquisition', dept: 'Recursos Humanos', mode: 'Remoto (Banco Flexível)', status: 'Ativo', avatar: 'assets/persona_recrutador_1786669002453.jpg' }
];

const screenTitles = {
  'publico-alvo': 'Público-Alvo & Linha de Produção de Software (LPS)',
  'personas': '6 Personas Mapeadas & Mapas de Empatia',
  'storyboard': 'Storyboard da Solicitação de Férias (14 Interações)',
  'tela-1': 'Tela 01: Login & Autenticação',
  'tela-2': 'Tela 02: Dashboard do Colaborador (Resumo)',
  'tela-3': 'Tela 03: Dashboard Executivo do RH & Indicadores',
  'tela-4': 'Tela 04: Lista de Colaboradores (CRUD)',
  'tela-5': 'Tela 05: Form de Cadastro / Edição',
  'tela-6': 'Tela 06: Detalhes do Colaborador (Perfil)',
  'tela-7': 'Tela 07: Organograma Interativo (Ativo AR02)',
  'tela-8': 'Tela 08: Espelho de Ponto & Banco de Horas (Ativo AR03)',
  'tela-9': 'Tela 09: Solicitação de Ajuste de Ponto',
  'tela-10': 'Tela 10: Gestão de Férias & Calendário',
  'tela-11': 'Tela 11: Central de Aprovações do Gestor',
  'tela-12': 'Tela 12: Gestão de Benefícios (Flexíveis vs Fixos)',
  'tela-13': 'Tela 13: Desempenho & OKRs (Avaliação 360°)',
  'tela-14': 'Tela 14: Módulo Industrial (EPIs & ASO)',
  'tela-15': 'Tela 15: Configuração da Empresa & Variabilidades'
};

// Initialize Application
document.addEventListener('DOMContentLoaded', () => {
  renderCrudTable();
  updateTenantUI();
});

// Screen Navigation Handler
function showScreen(screenId, event) {
  if (event) event.preventDefault();

  // Deactivate all screens and sidebar links
  document.querySelectorAll('.screen-view').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

  // Activate selected screen
  const targetScreen = document.getElementById(screenId);
  if (targetScreen) {
    targetScreen.classList.add('active');
    activeScreen = screenId;

    // Update Title in Topbar
    const title = screenTitles[screenId] || 'HRTech Core';
    document.getElementById('currentScreenTitle').textContent = title;

    // Highlight nav item
    const activeLink = document.querySelector(`.nav-item[href="#${screenId}"]`);
    if (activeLink) activeLink.classList.add('active');

    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

// Switch Tenant Profile (Tech vs Industry Variability)
function switchTenant(tenantKey) {
  currentTenant = tenantKey;
  document.getElementById('tenantSelect').value = tenantKey;
  updateTenantUI();
  showToast(`Cliente alterado para: ${tenantKey === 'tech' ? 'Empresa de Tecnologia (Remoto/OKRs)' : 'Indústria Metalúrgica (Presencial/EPIs)'}`);
}

function updateTenantUI() {
  const tenantTag = document.getElementById('tenantTag');
  const loginLabel = document.getElementById('loginTenantLabel');
  const indFields = document.getElementById('industryFields');
  const pontoRegraLabel = document.getElementById('pontoRegraLabel');
  const bancoHorasLabel = document.getElementById('bancoHorasLabel');
  const benTenantType = document.getElementById('benTenantType');
  const perfTenantType = document.getElementById('perfTenantType');

  if (currentTenant === 'tech') {
    tenantTag.className = 'tenant-tag tech-tag';
    tenantTag.innerHTML = '<i class="fa-solid fa-microchip"></i> Configuração: Empresa de Tecnologia';
    if (loginLabel) loginLabel.textContent = 'Cliente A - Tech Innovators';
    if (indFields) indFields.style.display = 'none';
    if (pontoRegraLabel) pontoRegraLabel.textContent = 'Banco de Horas Flexível (Tech)';
    if (bancoHorasLabel) bancoHorasLabel.textContent = 'Saldo Banco de Horas (Flexível)';
    if (benTenantType) benTenantType.textContent = 'Flexível - Caju/Flash (Cliente A - Tecnologia)';
    if (perfTenantType) perfTenantType.textContent = 'Avaliação 360° + OKRs (Tecnologia)';

    document.getElementById('modBancoFlex').checked = true;
    document.getElementById('modIndustrial').checked = false;
    document.getElementById('modOKRs').checked = true;
  } else {
    tenantTag.className = 'tenant-tag ind-tag';
    tenantTag.innerHTML = '<i class="fa-solid fa-industry"></i> Configuração: Indústria Metalúrgica';
    if (loginLabel) loginLabel.textContent = 'Cliente B - Metalúrgica Sul';
    if (indFields) indFields.style.display = 'block';
    if (pontoRegraLabel) pontoRegraLabel.textContent = 'Escala de Turno 12x36 (Indústria)';
    if (bancoHorasLabel) bancoHorasLabel.textContent = 'Horas Extras em Turno (12x36)';
    if (benTenantType) benTenantType.textContent = 'Fixos Categoriados por Convenção Coletiva (Cliente B)';
    if (perfTenantType) perfTenantType.textContent = 'Avaliação por Função / Conformidade (Indústria)';

    document.getElementById('modBancoFlex').checked = false;
    document.getElementById('modIndustrial').checked = true;
    document.getElementById('modOKRs').checked = false;
  }
}

// Persona Tab Switcher
function switchPersonaTab(personaId, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.persona-tab-content').forEach(c => c.classList.remove('active'));

  btn.classList.add('active');
  const target = document.getElementById(`persona-${personaId}`);
  if (target) target.classList.add('active');
}

// Render CRUD Table
function renderCrudTable() {
  const tbody = document.getElementById('crudTableBody');
  if (!tbody) return;

  tbody.innerHTML = '';
  collaborators.forEach(c => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <div style="display:flex; align-items:center; gap:10px;">
          <img src="${c.avatar}" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
          <strong>${c.name}</strong>
        </div>
      </td>
      <td>${c.role}</td>
      <td>${c.dept}</td>
      <td><span class="badge ${c.mode.includes('Remoto') ? 'tech-badge' : 'ind-badge'}">${c.mode}</span></td>
      <td><span class="status-pill status-active">${c.status}</span></td>
      <td>
        <button class="btn btn-sm btn-outline" onclick="viewColabDetails(${c.id})" title="Ver Detalhes"><i class="fa-solid fa-eye"></i></button>
        <button class="btn btn-sm btn-danger" onclick="deleteColab(${c.id})" title="Excluir"><i class="fa-solid fa-trash"></i></button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function filterCrudTable() {
  const query = document.getElementById('crudSearch').value.toLowerCase();
  const rows = document.querySelectorAll('#crudTableBody tr');
  rows.forEach(r => {
    const text = r.textContent.toLowerCase();
    r.style.display = text.includes(query) ? '' : 'none';
  });
}

function viewColabDetails(id) {
  showScreen('tela-6');
}

function deleteColab(id) {
  if (confirm('Deseja realmente inativar/remover este colaborador?')) {
    collaborators = collaborators.filter(c => c.id !== id);
    renderCrudTable();
    showToast('Colaborador removido com sucesso!');
  }
}

// Form Handlers
function handleSaveCollaborator(event) {
  event.preventDefault();
  const nome = document.getElementById('colabNome').value;
  const cargo = document.getElementById('colabCargo').value;
  const dept = document.getElementById('colabDepartamento').value;
  const modelo = document.getElementById('colabModelo').value;

  const newColab = {
    id: Date.now(),
    name: nome,
    role: cargo,
    dept: dept,
    mode: modelo,
    status: 'Ativo',
    avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100&auto=format&fit=crop&q=80'
  };

  collaborators.push(newColab);
  renderCrudTable();
  showToast(`Colaborador ${nome} cadastrado com sucesso!`);
  showScreen('tela-4');
}

function handleLoginSim(e) {
  e.preventDefault();
  showToast('Autenticação realizada com sucesso! Bem-vindo.');
  showScreen('tela-2');
}

function handleAdjustmentSubmit(e) {
  e.preventDefault();
  showToast('Solicitação de ajuste de ponto enviada para aprovação do gestor.');
  showScreen('tela-8');
}

function handleVacationSubmit(e) {
  e.preventDefault();
  showToast('Solicitação de férias enviada com sucesso! Notificação encaminhada ao gestor.');
  showScreen('tela-11');
}

function approveItem(btn) {
  const row = btn.closest('tr');
  row.style.opacity = '0.4';
  btn.parentNode.innerHTML = '<span class="status-pill status-success"><i class="fa-solid fa-check"></i> Aprovado</span>';
  showToast('Solicitação APROVADA com sucesso!');
}

function rejectItem(btn) {
  const row = btn.closest('tr');
  row.style.opacity = '0.4';
  btn.parentNode.innerHTML = '<span class="status-pill status-warning"><i class="fa-solid fa-x"></i> Recusado</span>';
  showToast('Solicitação RECUSADA pelo gestor.');
}

function toggleModuleConfig() {
  showToast('Configuração de módulos LPS atualizada.');
}

function toggleTheme() {
  document.documentElement.classList.toggle('light');
  showToast('Tema alterado.');
}

function showNotifications() {
  showToast('Notificação: Carlos Eduardo aprovou seu ajuste de ponto.');
}

// Toast Helper
function showToast(msg) {
  const toast = document.createElement('div');
  toast.style.position = 'fixed';
  toast.style.bottom = '25px';
  toast.style.right = '25px';
  toast.style.backgroundColor = '#0d9488';
  toast.style.color = '#ffffff';
  toast.style.padding = '12px 20px';
  toast.style.borderRadius = '8px';
  toast.style.boxShadow = '0 10px 25px rgba(0,0,0,0.5)';
  toast.style.zIndex = '9999';
  toast.style.fontSize = '0.88rem';
  toast.style.fontWeight = '600';
  toast.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${msg}`;

  document.body.appendChild(toast);
  setTimeout(() => {
    toast.remove();
  }, 3500);
}
