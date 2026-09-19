<?php
$activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
?>
<!-- Professor Academic Demo Bar -->
<div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border-bottom: 2px solid #6366f1; padding: 10px 24px; color: #ffffff; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center; justify-content: space-between; gap: 15px; position: sticky; top: 0; z-index: 10000; box-shadow: 0 4px 20px rgba(0,0,0,0.4);">
  
  <div style="display: flex; align-items: center; gap: 12px;">
    <span style="background: #4f46e5; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; display: inline-flex; align-items: center; gap: 6px;">
      <i class="fa-solid fa-graduation-cap"></i> PAINEL DO PROFESSOR
    </span>
    <span style="font-size: 13px; color: #c7d2fe; font-weight: 500;">
      Alternância de Papel / Tenant:
    </span>
  </div>

  <!-- Quick Switcher Buttons -->
  <div style="display: flex; align-items: center; gap: 8px;">
    <a href="index.php?route=switch_tenant&role=admin" style="text-decoration: none; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; <?= $activeRole === 'admin' ? 'background: #ec4899; color: #fff; box-shadow: 0 0 10px rgba(236,72,153,0.5);' : 'background: rgba(255,255,255,0.1); color: #e0e7ff;' ?>">
      <i class="fa-solid fa-user-shield"></i> 👑 Admin (Feature Toggles)
    </a>

    <a href="index.php?route=switch_tenant&role=tenant-tech" style="text-decoration: none; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; <?= $activeRole === 'tenant-tech' ? 'background: #3b82f6; color: #fff; box-shadow: 0 0 10px rgba(59,130,246,0.5);' : 'background: rgba(255,255,255,0.1); color: #e0e7ff;' ?>">
      <i class="fa-solid fa-laptop-code"></i> 💻 Tech Corp (Tecnologia)
    </a>

    <a href="index.php?route=switch_tenant&role=tenant-ind" style="text-decoration: none; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; <?= $activeRole === 'tenant-ind' ? 'background: #f59e0b; color: #fff; box-shadow: 0 0 10px rgba(245,158,11,0.5);' : 'background: rgba(255,255,255,0.1); color: #e0e7ff;' ?>">
      <i class="fa-solid fa-industry"></i> 🏭 Indústria S/A (Manufatura)
    </a>

    <a href="index.php?route=switch_tenant&role=tenant-fin" style="text-decoration: none; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; <?= $activeRole === 'tenant-fin' ? 'background: #10b981; color: #fff; box-shadow: 0 0 10px rgba(16,185,129,0.5);' : 'background: rgba(255,255,255,0.1); color: #e0e7ff;' ?>">
      <i class="fa-solid fa-piggy-bank"></i> 🏦 FinCorp (Financeiro)
    </a>
  </div>

  <!-- Academic Tools -->
  <div style="display: flex; align-items: center; gap: 8px;">
    <button onclick="togglePatternModal()" style="background: rgba(99,102,241,0.2); border: 1px solid #818cf8; color: #a5b4fc; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
      <i class="fa-solid fa-microscope"></i> 🔬 Padrões GoF da Tela
    </button>
    <a href="../index.html" style="background: rgba(255,255,255,0.1); color: #94a3b8; text-decoration: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
      <i class="fa-solid fa-arrow-left"></i> Voltar ao Protótipo HTML
    </a>
  </div>

</div>
