<?php
$activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
$tenantName = match($activeRole) {
    'admin' => '👑 Painel Administrativo Global (Todas as Empresas)',
    'tenant-tech' => '💻 TechStart Inovações Ltda (Tecnologia)',
    'tenant-ind' => '🏭 Metalúrgica Sul S.A. (Indústria)',
    'tenant-fin' => '🏦 FinCorp Seguros e Investimentos (Financeiro)',
    default => 'HRTech Core'
};
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HRTech Core — Aplicação Funcional MVC</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg-dark: #0f172a;
      --card-bg: #1e293b;
      --sidebar-bg: #0f172a;
      --border-color: #334155;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --primary: #6366f1;
      --primary-hover: #4f46e5;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --info: #06b6d4;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--bg-dark);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .app-layout {
      display: flex;
      flex: 1;
      min-height: calc(100vh - 50px);
    }

    .main-content {
      flex: 1;
      padding: 24px 32px;
      overflow-y: auto;
      background: radial-gradient(circle at top right, rgba(99,102,241,0.05) 0%, transparent 40%);
    }

    .card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 12px;
    }

    .card-title {
      font-size: 18px;
      font-weight: 700;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn {
      background: var(--primary);
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
    }
    .btn:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-success { background: var(--success); }
    .btn-warning { background: var(--warning); color: #000; }
    .btn-danger { background: var(--danger); }
    .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-muted); }
    .btn-outline:hover { background: rgba(255,255,255,0.05); color: #fff; }

    .table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 14px;
    }
    .table th {
      background: rgba(15, 23, 42, 0.6);
      color: var(--text-muted);
      text-align: left;
      padding: 12px 16px;
      font-weight: 600;
      border-bottom: 1px solid var(--border-color);
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.5px;
    }
    .table td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--border-color);
      color: var(--text-main);
    }
    .table tr:hover { background: rgba(255,255,255,0.02); }

    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .badge-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .badge-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .badge-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .badge-info { background: rgba(6, 182, 212, 0.15); color: #38bdf8; border: 1px solid rgba(6, 182, 212, 0.3); }

    .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .stat-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-muted); }
    .form-control {
      width: 100%;
      background: #0f172a;
      border: 1px solid var(--border-color);
      color: #fff;
      padding: 10px 14px;
      border-radius: 8px;
      font-family: inherit;
      font-size: 14px;
    }
    .form-control:focus { outline: none; border-color: var(--primary); }

    /* Modal Styling */
    .modal-backdrop {
      position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
      background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
      display: none; align-items: center; justify-content: center; z-index: 99999;
    }
    .modal-backdrop.active { display: flex; }
    .modal-box {
      background: var(--card-bg); border: 1px solid var(--border-color);
      border-radius: 16px; width: 90%; max-width: 650px; padding: 28px;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);
    }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/demo_bar.php'; ?>

<div class="app-layout">
<?php require_once __DIR__ . '/sidebar.php'; ?>

<main class="main-content">
  <!-- Top Context Header -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px 24px;">
    <div>
      <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px;">CONTEXTO DE EMPRESA ATIVO</span>
      <h2 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 2px; display: flex; align-items: center; gap: 10px;">
        <?= htmlspecialchars($tenantName) ?>
      </h2>
    </div>

    <div style="display: flex; align-items: center; gap: 12px;">
      <span class="badge badge-success" style="padding: 6px 14px; font-size: 12px;">
        <i class="fa-solid fa-signal"></i> SQLite Online (WAL)
      </span>
      <span style="font-size: 13px; color: var(--text-muted);">
        <i class="fa-solid fa-clock"></i> <?= date('d/m/Y H:i') ?>
      </span>
    </div>
  </div>

  <?php if (isset($_SESSION['flash_message'])): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; font-weight: 600;">
      <span><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['flash_message']) ?></span>
      <?php unset($_SESSION['flash_message']); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['flash_error'])): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; font-weight: 600;">
      <span><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?></span>
      <?php unset($_SESSION['flash_error']); ?>
    </div>
  <?php endif; ?>
