<div style="margin-bottom: 24px;">
  <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Dashboard Corporativo</h1>
  <p style="color: var(--text-muted); font-size: 14px;">Visão consolidada do tenant ativo e indicadores operacionais em tempo real.</p>
</div>

<!-- Stat Cards -->
<div class="grid-4">
  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(99,102,241,0.15); color: #818cf8;">
      <i class="fa-solid fa-users"></i>
    </div>
    <div>
      <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">COLABORADORES ATIVOS</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 2px;"><?= $employeeCount ?></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: #34d399;">
      <i class="fa-solid fa-clock"></i>
    </div>
    <div>
      <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">BATIDAS DE PONTO (671)</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 2px;"><?= $timeLogCount ?></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #fbbf24;">
      <i class="fa-solid fa-umbrella-beach"></i>
    </div>
    <div>
      <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">FÉRIAS PENDENTES</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 2px;"><?= $pendingVacations ?></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(236,72,153,0.15); color: #f472b6;">
      <i class="fa-solid fa-gift"></i>
    </div>
    <div>
      <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">BENEFÍCIOS ATIVOS</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 2px;"><?= $benefitCount ?></h3>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
  
  <!-- Quick Actions Card -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fa-solid fa-bolt" style="color: var(--warning);"></i> Ações Rápidas de Operação</h3>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
      <a href="index.php?route=employees" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-user-plus" style="font-size: 18px; color: var(--primary);"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Cadastrar Colaborador</div>
          <div style="font-size: 11px; color: var(--text-muted);">Novo registro de funcionário no SQLite</div>
        </div>
      </a>

      <a href="index.php?route=timelogs" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-fingerprint" style="font-size: 18px; color: var(--success);"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Bater Ponto Eletrônico</div>
          <div style="font-size: 11px; color: var(--text-muted);">Registro SHA-256 (Portaria 671 MTE)</div>
        </div>
      </a>

      <a href="index.php?route=vacations" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-calendar-check" style="font-size: 18px; color: var(--warning);"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Solicitar Férias CLT</div>
          <div style="font-size: 11px; color: var(--text-muted);">Controle de período aquisitivo</div>
        </div>
      </a>

      <a href="index.php?route=payroll" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-calculator" style="font-size: 18px; color: var(--info);"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Calcular Folha</div>
          <div style="font-size: 11px; color: var(--text-muted);">Template Method & Strategies</div>
        </div>
      </a>
    </div>
  </div>

  <!-- Software Product Line (LPS) Summary -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fa-solid fa-layer-group" style="color: var(--primary);"></i> Variabilidade da LPS</h3>
    </div>
    <div style="font-size: 13px; color: var(--text-muted); display: flex; flex-direction: column; gap: 12px;">
      <p>O <strong>Motor de Variabilidade (`LpsVariabilityEngine`)</strong> adapta esta interface em tempo real dependendo do perfil da empresa ativa.</p>
      
      <div style="background: #0f172a; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
        <strong style="color: #fff;">Tecnologia:</strong> Banco de Horas, OKRs, Benefícios Flex.
      </div>

      <div style="background: #0f172a; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
        <strong style="color: #fff;">Indústria:</strong> Horas Extras 50%/100%, Fretado, Trava por EPI/ASO vencido.
      </div>

      <div style="background: #0f172a; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
        <strong style="color: #fff;">Financeiro:</strong> Biometria 671, Plano Executivo, Seguro FinCorp.
      </div>
    </div>
  </div>

</div>
