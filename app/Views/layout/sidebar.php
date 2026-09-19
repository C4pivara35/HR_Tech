<?php
use HrTech\Lps\FeatureToggleManager;

$activeRole  = $_SESSION['active_role'] ?? 'tenant-tech';
$currentRoute = $_GET['route'] ?? 'dashboard';

$ftManager = FeatureToggleManager::getInstance();
$activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

$hasSafety    = ($activeRole === 'admin') || $ftManager->isFeatureEnabled('risk_ppe_required', null, null, $activeTenantId);
$hasInsurance = ($activeRole === 'admin') || $ftManager->isFeatureEnabled('d_and_o_insurance', null, null, $activeTenantId) || $ftManager->isFeatureEnabled('fincorp_life_policy', null, null, $activeTenantId);
$hasBenefits  = ($activeRole === 'admin') || $ftManager->isFeatureEnabled('flexible_benefits', null, null, $activeTenantId);
?>
<aside style="width: 260px; background: var(--sidebar-bg); border-right: 1px solid var(--border-color); padding: 24px 16px; display: flex; flex-direction: column; gap: 8px;">
  <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-left: 8px;">
    <div style="background: linear-gradient(135deg, #6366f1, #a855f7); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px;">
      <i class="fa-solid fa-layer-group"></i>
    </div>
    <div>
      <h1 style="font-size: 16px; font-weight: 800; color: #fff;">HRTech Core</h1>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 600;">Aplicação MVC Funcional</span>
    </div>
  </div>

  <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 12px 0 6px 8px;">
    Navegação Principal
  </div>

  <?php if ($activeRole === 'admin'): ?>
    <a href="index.php?route=admin" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'admin' ? 'background: #ec4899; color: #fff;' : 'color: var(--text-muted); hover:background: rgba(255,255,255,0.05);' ?>">
      <i class="fa-solid fa-user-shield" style="width: 20px;"></i>
      <span>Matriz Feature Toggles</span>
    </a>
  <?php endif; ?>

  <a href="index.php?route=dashboard" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'dashboard' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-gauge-high" style="width: 20px;"></i>
    <span>Dashboard</span>
  </a>

  <a href="index.php?route=employees" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'employees' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-users" style="width: 20px;"></i>
    <span>Colaboradores</span>
  </a>

  <a href="index.php?route=timelogs" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'timelogs' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-clock-rotate-left" style="width: 20px;"></i>
    <span>Ponto & Portaria 671</span>
  </a>

  <a href="index.php?route=vacations" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'vacations' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-umbrella-beach" style="width: 20px;"></i>
    <span>Solicitações de Férias</span>
  </a>

  <?php if ($hasBenefits): ?>
    <a href="index.php?route=benefits" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'benefits' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
      <i class="fa-solid fa-gift" style="width: 20px;"></i>
      <span>Catálogo de Benefícios</span>
    </a>
  <?php endif; ?>

  <?php if ($hasSafety): ?>
    <a href="index.php?route=safety" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'safety' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
      <i class="fa-solid fa-hard-hat" style="width: 20px;"></i>
      <span>EPIs & Exames ASO</span>
    </a>
  <?php endif; ?>

  <?php if ($hasInsurance): ?>
    <a href="index.php?route=insurance" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'insurance' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
      <i class="fa-solid fa-shield-halved" style="width: 20px;"></i>
      <span>Apólices FinCorp</span>
    </a>
  <?php endif; ?>

  <a href="index.php?route=payroll" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'payroll' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-calculator" style="width: 20px;"></i>
    <span>Folha de Pagamento</span>
  </a>

  <a href="index.php?route=reports" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; <?= $currentRoute === 'reports' ? 'background: var(--primary); color: #fff;' : 'color: var(--text-muted);' ?>">
    <i class="fa-solid fa-file-invoice" style="width: 20px;"></i>
    <span>Central de Relatórios</span>
  </a>

  <div style="margin-top: auto; padding: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 10px; font-size: 12px;">
    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">Status da LPS</div>
    <div style="color: var(--text-muted);">Perfil: <span style="color: #38bdf8; font-weight: 700;"><?= strtoupper(str_replace('tenant-', '', $activeTenantId)) ?></span></div>
    <div style="color: var(--text-muted); margin-top: 2px;">Recursos: <span style="color: #34d399; font-weight: 700;">Variabilidade Ativa</span></div>
  </div>
</aside>
