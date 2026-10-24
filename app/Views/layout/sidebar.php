<?php
use HrTech\Lps\FeatureToggleManager;

$activeRole   = $_SESSION['active_role'] ?? 'tenant-tech';
$currentRoute = $_GET['route'] ?? 'dashboard';
$isAdmin      = ($activeRole === 'admin');

$ftManager = FeatureToggleManager::getInstance();
$activeTenantId = $isAdmin ? 'tenant-tech' : $activeRole;

// Determine active tenant segment
$tenantSegment = 'tech';
if ($activeRole === 'tenant-tech') {
    $tenantSegment = 'tech';
} elseif ($activeRole === 'tenant-ind') {
    $tenantSegment = 'industria';
} elseif ($activeRole === 'tenant-fin') {
    $tenantSegment = 'financeiro';
} elseif ($activeRole === 'tenant-all') {
    $tenantSegment = 'all';
} elseif (!$isAdmin) {
    if (isset($pdo)) {
        $segStmt = $pdo->prepare("SELECT segment FROM tenants WHERE id = ?");
        $segStmt->execute([$activeRole]);
        $seg = $segStmt->fetchColumn();
        if ($seg) {
            $tenantSegment = in_array($seg, ['industry', 'industria']) ? 'industria' : (in_array($seg, ['financial', 'financeiro']) ? 'financeiro' : ($seg === 'all' ? 'all' : 'tech'));
        }
    }
}

$isTech = ($tenantSegment === 'tech');
$isInd  = ($tenantSegment === 'industria');
$isFin  = ($tenantSegment === 'financeiro');
$isAll  = ($activeRole === 'tenant-all' || $tenantSegment === 'all');

// Conditional visibility per LPS segment rules:
// Tech: OKRs & Desempenho, Benefícios Flexíveis
// Indústria: EPIs & Exames ASO (NR-6 / NR-7)
// Financeiro: Apólices FinCorp (Seguro de Vida / D&O), Compliance & Auditoria LGPD
// Admin & OmniCorp (tenant-all): Vê todos os módulos com todas as flags ativas
$showOkrs       = $isAdmin || $isAll || ($isTech && $ftManager->isFeatureEnabled('okr_evaluation', null, null, $activeTenantId));
$showBenefits   = $isAdmin || $isAll || ($isTech && $ftManager->isFeatureEnabled('flexible_benefits', null, null, $activeTenantId));
$showSafety     = $isAdmin || $isAll || ($isInd && $ftManager->isFeatureEnabled('risk_ppe_required', null, null, $activeTenantId));
$showInsurance  = $isAdmin || $isAll || ($isFin && ($ftManager->isFeatureEnabled('fincorp_life_policy', null, null, $activeTenantId) || $ftManager->isFeatureEnabled('d_and_o_insurance', null, null, $activeTenantId)));
$showCompliance = $isAdmin || $isAll || ($isFin && $ftManager->isFeatureEnabled('strict_lgpd_audit', null, null, $activeTenantId));
?>
<aside style="width: 270px; background: var(--sidebar-bg); border-right: 1px solid var(--border-color); padding: 24px 16px; display: flex; flex-direction: column; gap: 8px;">
  <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-left: 8px;">
    <div style="background: linear-gradient(135deg, #6366f1, #a855f7); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px;">
      <i class="fa-solid fa-layer-group"></i>
    </div>
    <div>
      <h1 style="font-size: 16px; font-weight: 800; color: #fff;">HRTech Core</h1>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Aplicação MVC Funcional</span>
    </div>
  </div>

  <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 6px 0 4px 8px;">
    Painel de Gestão
  </div>

  <!-- Admin Link -->
  <a href="index.php?route=admin" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 700; transition: all 0.2s; <?= $currentRoute === 'admin' ? 'background: #ec4899; color: #fff;' : 'color: #f472b6; background: rgba(236,72,153,0.12); border: 1px solid rgba(236,72,153,0.3);' ?>">
    <div style="display: flex; align-items: center; gap: 10px;">
      <i class="fa-solid fa-building-circle-check" style="width: 18px;"></i>
      <span>Empresas &amp; LPS (Admin)</span>
    </div>
    <span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.2); font-weight: 800;">CRUD</span>
  </a>

  <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 12px 0 4px 8px;">
    Módulos do Sistema
  </div>

  <a href="index.php?route=dashboard" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'dashboard' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-gauge-high" style="width: 20px;"></i>
    <span>Dashboard</span>
  </a>

  <a href="index.php?route=employees" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'employees' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-users" style="width: 20px;"></i>
    <span>Colaboradores</span>
  </a>

  <a href="index.php?route=organogram" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'organogram' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-sitemap" style="width: 20px; color: #a78bfa;"></i>
    <span>Organograma</span>
  </a>

  <a href="index.php?route=timelogs" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'timelogs' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-clock-rotate-left" style="width: 20px;"></i>
    <span>Ponto & Portaria 671</span>
  </a>

  <a href="index.php?route=vacations" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'vacations' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-umbrella-beach" style="width: 20px;"></i>
    <span>Solicitações de Férias</span>
  </a>

  <a href="index.php?route=approvals" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'approvals' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-clipboard-check" style="width: 20px; color: #34d399;"></i>
    <span>Aprovações Gestor</span>
  </a>

  <!-- Tech Modules -->
  <?php if ($showBenefits): ?>
    <a href="index.php?route=benefits" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'benefits' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-gift" style="width: 20px;"></i>
        <span>Catálogo de Benefícios</span>
      </div>
      <?php if ($isAdmin || $isAll): ?>
        <span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: rgba(59,130,246,0.25); color: #93c5fd; font-weight: 700;"><?= $isAdmin ? 'TECH' : 'FULL' ?></span>
      <?php endif; ?>
    </a>
  <?php endif; ?>

  <?php if ($showOkrs): ?>
    <a href="index.php?route=okrs" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'okrs' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-bullseye" style="width: 20px; color: #38bdf8;"></i>
        <span>Desempenho &amp; OKRs</span>
      </div>
      <span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: rgba(56,189,248,0.2); color: #38bdf8; font-weight: 700;">
        <?= $isAdmin ? 'TECH' : ($isAll ? 'FULL' : 'EXCLUSIVO') ?>
      </span>
    </a>
  <?php endif; ?>

  <!-- Indústria Modules -->
  <?php if ($showSafety): ?>
    <a href="index.php?route=safety" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'safety' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-hard-hat" style="width: 20px; color: #fbbf24;"></i>
        <span>EPIs &amp; Exames ASO</span>
      </div>
      <span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: rgba(245,158,11,0.2); color: #fbbf24; font-weight: 700;">
        <?= $isAdmin ? 'INDÚSTRIA' : ($isAll ? 'FULL' : 'EXCLUSIVO') ?>
      </span>
    </a>
  <?php endif; ?>

  <!-- Financeiro Modules -->
  <?php if ($showInsurance): ?>
    <a href="index.php?route=insurance" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'insurance' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-shield-halved" style="width: 20px; color: #34d399;"></i>
        <span>Apólices FinCorp</span>
      </div>
      <span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: rgba(16,185,129,0.2); color: #34d399; font-weight: 700;">
        <?= $isAdmin ? 'FINANCEIRO' : ($isAll ? 'FULL' : 'EXCLUSIVO') ?>
      </span>
    </a>

    <a href="index.php?route=pix" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'pix' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-brands fa-pix" style="width: 20px; color: #06b6d4;"></i>
        <span>PIX BACEN Gateway</span>
      </div>
      <span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: rgba(6,182,212,0.2); color: #06b6d4; font-weight: 700;">
        <?= $isAdmin ? 'FINANCEIRO' : ($isAll ? 'FULL' : 'EXCLUSIVO') ?>
      </span>
    </a>
  <?php endif; ?>

  <?php if ($showCompliance): ?>
    <a href="index.php?route=compliance" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'compliance' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-user-shield" style="width: 20px; color: #34d399;"></i>
        <span>Compliance &amp; Auditoria</span>
      </div>
      <span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: rgba(16,185,129,0.2); color: #34d399; font-weight: 700;">
        <?= $isAdmin ? 'FINANCEIRO' : ($isAll ? 'FULL' : 'EXCLUSIVO') ?>
      </span>
    </a>
  <?php endif; ?>

  <!-- Standard Modules -->
  <a href="index.php?route=payroll" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'payroll' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-calculator" style="width: 20px;"></i>
    <span>Folha de Pagamento</span>
  </a>

  <a href="index.php?route=reports" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'reports' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-file-invoice" style="width: 20px;"></i>
    <span>Central de Relatórios</span>
  </a>

  <a href="index.php?route=external_simulators" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'external_simulators' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-network-wired" style="width: 20px; color: #ec4899;"></i>
    <span>Simuladores AIE (10 APIs)</span>
  </a>

  <!-- LPS Status Footer -->
  <div style="margin-top: auto; padding: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 10px; font-size: 12px;">
    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">Status da LPS</div>
    <div style="color: var(--text-muted);">Segmento: <span style="color: #38bdf8; font-weight: 700;"><?= strtoupper($isAdmin ? 'ADMIN GLOBAL' : ($isAll ? 'FULL FEATURES (TODAS)' : $tenantSegment)) ?></span></div>
    <div style="color: var(--text-muted); margin-top: 2px;">Perfil Ativo: <span style="color: #34d399; font-weight: 700;"><?= strtoupper(str_replace('tenant-', '', $activeRole)) ?></span></div>
  </div>
</aside>
