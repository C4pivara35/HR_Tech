<?php
declare(strict_types=1);

// Bootstrap
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/src/Autoloader.php';

use HrTech\Autoloader;

$autoloader = new Autoloader();
$autoloader->addNamespace('HrTech', BASE_PATH . '/src');
$autoloader->register();

use HrTech\Database\DatabaseManager;

// Init DB
$db = DatabaseManager::getInstance(BASE_PATH . '/web/hrtech_web.sqlite');
$db->migrate();

// Simple router
$page    = $_GET['page'] ?? 'dashboard';
$action  = $_GET['action'] ?? 'list';
$id      = $_GET['id'] ?? null;
$message = '';
$error   = '';

// --- CRUD Handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postPage = $_POST['page'] ?? $page;
    try {
        match ($postPage) {
            'tenants'    => handleTenants($db, $action),
            'employees'  => handleEmployees($db, $action),
            'timelogs'   => handleTimeLogs($db, $action),
            'vacations'  => handleVacations($db, $action),
            'benefits'   => handleBenefits($db, $action),
            default      => null,
        };
        $message = 'Operação realizada com sucesso!';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    header('Location: ?page=' . $postPage . ($message ? '&msg=1' : '&err=' . urlencode($error)));
    exit;
}

if ($action === 'delete' && $id) {
    try {
        match ($page) {
            'tenants'   => $db->pdo()->exec("DELETE FROM tenants WHERE id = " . (int)$id),
            'employees' => $db->pdo()->exec("DELETE FROM employees WHERE id = " . (int)$id),
            'timelogs'  => $db->pdo()->exec("DELETE FROM time_logs WHERE id = " . (int)$id),
            'vacations' => $db->pdo()->exec("DELETE FROM vacation_requests WHERE id = " . (int)$id),
            'benefits'  => $db->pdo()->exec("DELETE FROM benefits WHERE id = " . (int)$id),
            default     => null,
        };
    } catch (Throwable $e) { }
    header('Location: ?page=' . $page);
    exit;
}

if (isset($_GET['msg'])) $message = 'Operação realizada com sucesso! ✔';
if (isset($_GET['err'])) $error = urldecode($_GET['err']);

// --- CRUD Functions ---
function handleTenants($db, $action): void {
    $pdo = $db->getConnection();
    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO tenants (id, name, trading_name, cnpj, segment, plan, active, created_at) VALUES (?,?,?,?,?,?,1,?)");
        $stmt->execute([
            uniqid('t-'), $_POST['name'], $_POST['trading_name'],
            $_POST['cnpj'], $_POST['segment'], $_POST['plan'],
            date('Y-m-d H:i:s')
        ]);
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE tenants SET name=?, trading_name=?, segment=?, plan=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['trading_name'], $_POST['segment'], $_POST['plan'], $_POST['id']]);
    }
}

function handleEmployees($db, $action): void {
    $pdo = $db->getConnection();
    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO employees (id, tenant_id, full_name, cpf, email, employment_type, base_salary, admission_date, active, created_at) VALUES (?,?,?,?,?,?,?,?,1,?)");
        $stmt->execute([
            uniqid('e-'), $_POST['tenant_id'], $_POST['full_name'],
            $_POST['cpf'], $_POST['email'], $_POST['employment_type'],
            (float)$_POST['base_salary'], $_POST['admission_date'],
            date('Y-m-d H:i:s')
        ]);
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE employees SET full_name=?, email=?, base_salary=?, employment_type=? WHERE id=?");
        $stmt->execute([$_POST['full_name'], $_POST['email'], (float)$_POST['base_salary'], $_POST['employment_type'], $_POST['id']]);
    }
}

function handleTimeLogs($db, $action): void {
    $pdo = $db->getConnection();
    if ($action === 'create') {
        $ts = date('Y-m-d H:i:s');
        $hash = hash('sha256', $_POST['employee_id'] . $ts . $_POST['log_type']);
        $stmt = $pdo->prepare("INSERT INTO time_logs (id, tenant_id, employee_id, log_type, logged_at, ip_address, integrity_hash, nsr, created_at) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            uniqid('tl-'), $_POST['tenant_id'], $_POST['employee_id'],
            $_POST['log_type'], $ts, '127.0.0.1', $hash,
            rand(1, 9999), $ts
        ]);
    }
}

function handleVacations($db, $action): void {
    $pdo = $db->getConnection();
    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO vacation_requests (id, tenant_id, employee_id, start_date, end_date, days_requested, status, advance_thirteenth, created_at) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            uniqid('v-'), $_POST['tenant_id'], $_POST['employee_id'],
            $_POST['start_date'], $_POST['end_date'],
            (int)$_POST['days_requested'], 'pending',
            isset($_POST['advance_thirteenth']) ? 1 : 0,
            date('Y-m-d H:i:s')
        ]);
    } elseif ($action === 'approve') {
        $pdo->prepare("UPDATE vacation_requests SET status='approved' WHERE id=?")->execute([$_POST['id']]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE vacation_requests SET status='rejected' WHERE id=?")->execute([$_POST['id']]);
    }
}

function handleBenefits($db, $action): void {
    $pdo = $db->getConnection();
    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO benefits (id, tenant_id, employee_id, benefit_type, provider, monthly_value, copayment_pct, active, created_at) VALUES (?,?,?,?,?,?,?,1,?)");
        $stmt->execute([
            uniqid('b-'), $_POST['tenant_id'], $_POST['employee_id'],
            $_POST['benefit_type'], $_POST['provider'],
            (float)$_POST['monthly_value'], (float)$_POST['copayment_pct'],
            date('Y-m-d H:i:s')
        ]);
    }
}

// Fetch data for display
$pdo = $db->getConnection();

$tenants   = $pdo->query("SELECT * FROM tenants ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$employees = $pdo->query("SELECT e.*, t.name as tenant_name FROM employees e LEFT JOIN tenants t ON e.tenant_id = t.id ORDER BY e.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$timelogs  = $pdo->query("SELECT tl.*, e.full_name as employee_name FROM time_logs tl LEFT JOIN employees e ON tl.employee_id = e.id ORDER BY tl.logged_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$vacations = $pdo->query("SELECT v.*, e.full_name as employee_name FROM vacation_requests v LEFT JOIN employees e ON v.employee_id = e.id ORDER BY v.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$benefits  = $pdo->query("SELECT b.*, e.full_name as employee_name FROM benefits b LEFT JOIN employees e ON b.employee_id = e.id ORDER BY b.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$editItem = null;
if ($action === 'edit' && $id) {
    match ($page) {
        'tenants'   => $editItem = $pdo->prepare("SELECT * FROM tenants WHERE id=?")->execute([$id]) ? $pdo->query("SELECT * FROM tenants WHERE id='$id'")->fetch(PDO::FETCH_ASSOC) : null,
        'employees' => $editItem = $pdo->query("SELECT * FROM employees WHERE id='$id'")->fetch(PDO::FETCH_ASSOC),
        'vacations' => $editItem = $pdo->query("SELECT * FROM vacation_requests WHERE id='$id'")->fetch(PDO::FETCH_ASSOC),
        default => null
    };
}

$stats = [
    'tenants'   => $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn(),
    'employees' => $pdo->query("SELECT COUNT(*) FROM employees WHERE active=1")->fetchColumn(),
    'timelogs'  => $pdo->query("SELECT COUNT(*) FROM time_logs")->fetchColumn(),
    'vacations' => $pdo->query("SELECT COUNT(*) FROM vacation_requests WHERE status='pending'")->fetchColumn(),
    'benefits'  => $pdo->query("SELECT COUNT(*) FROM benefits WHERE active=1")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HRTech Core — Painel CRUD</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

  :root {
    --bg: #0f1117;
    --bg2: #161b27;
    --bg3: #1e2536;
    --border: #2a3347;
    --accent: #6366f1;
    --accent2: #8b5cf6;
    --green: #10b981;
    --yellow: #f59e0b;
    --red: #ef4444;
    --blue: #3b82f6;
    --text: #e2e8f0;
    --muted: #64748b;
    --card: #1a2035;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

  /* Sidebar */
  .sidebar {
    width: 240px; min-height: 100vh; background: var(--bg2);
    border-right: 1px solid var(--border); padding: 0; position: fixed; top: 0; left: 0;
    display: flex; flex-direction: column;
  }
  .sidebar-logo {
    padding: 24px 20px 20px; border-bottom: 1px solid var(--border);
  }
  .sidebar-logo h1 { font-size: 18px; font-weight: 700; color: var(--text); }
  .sidebar-logo h1 span { color: var(--accent); }
  .sidebar-logo p { font-size: 11px; color: var(--muted); margin-top: 2px; }

  .sidebar-nav { padding: 16px 12px; flex: 1; }
  .nav-section { font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase;
    letter-spacing: .08em; padding: 8px 8px 6px; }
  .nav-link {
    display: flex; align-items: center; gap: 10px; padding: 9px 12px;
    border-radius: 8px; text-decoration: none; color: var(--muted); font-size: 13.5px;
    font-weight: 500; transition: all .15s; margin-bottom: 2px;
  }
  .nav-link:hover { background: var(--bg3); color: var(--text); }
  .nav-link.active { background: rgba(99,102,241,.18); color: var(--accent); }
  .nav-link .icon { font-size: 16px; width: 20px; text-align: center; }
  .badge { margin-left: auto; background: var(--accent); color: #fff; font-size: 10px;
    font-weight: 700; padding: 2px 6px; border-radius: 10px; min-width: 18px; text-align: center; }
  .badge.yellow { background: var(--yellow); }
  .badge.green { background: var(--green); }

  .sidebar-footer { padding: 16px 20px; border-top: 1px solid var(--border); }
  .sidebar-footer p { font-size: 11px; color: var(--muted); }

  /* Main content */
  .main { margin-left: 240px; flex: 1; min-height: 100vh; }
  .topbar {
    background: var(--bg2); border-bottom: 1px solid var(--border);
    padding: 16px 32px; display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 10;
  }
  .topbar h2 { font-size: 18px; font-weight: 600; }
  .topbar .breadcrumb { font-size: 12px; color: var(--muted); margin-top: 2px; }
  .topbar-actions { display: flex; gap: 10px; }

  .content { padding: 28px 32px; }

  /* Alert */
  .alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px; }
  .alert.success { background: rgba(16,185,129,.15); border: 1px solid rgba(16,185,129,.3); color: var(--green); }
  .alert.danger  { background: rgba(239, 68,68,.15);  border: 1px solid rgba(239,68,68,.3);  color: var(--red); }

  /* Stats grid */
  .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 28px; }
  .stat-card {
    background: var(--card); border: 1px solid var(--border); border-radius: 12px;
    padding: 18px 20px; position: relative; overflow: hidden;
  }
  .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
  .stat-card.purple::before { background: linear-gradient(90deg, var(--accent), var(--accent2)); }
  .stat-card.green::before  { background: var(--green); }
  .stat-card.blue::before   { background: var(--blue); }
  .stat-card.yellow::before { background: var(--yellow); }
  .stat-card.red::before    { background: var(--red); }
  .stat-label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
  .stat-value { font-size: 32px; font-weight: 700; margin-top: 4px; }
  .stat-sub   { font-size: 11px; color: var(--muted); margin-top: 2px; }

  /* Table card */
  .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
  .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between; }
  .card-title { font-size: 15px; font-weight: 600; }
  .card-sub { font-size: 12px; color: var(--muted); margin-top: 1px; }

  table { width: 100%; border-collapse: collapse; }
  th { padding: 11px 16px; text-align: left; font-size: 11px; font-weight: 600;
    color: var(--muted); text-transform: uppercase; letter-spacing: .06em;
    border-bottom: 1px solid var(--border); background: rgba(255,255,255,.02); }
  td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid rgba(42,51,71,.5); vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: rgba(255,255,255,.03); }

  .pill { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; }
  .pill.green  { background: rgba(16,185,129,.15); color: var(--green); }
  .pill.yellow { background: rgba(245,158,11,.15); color: var(--yellow); }
  .pill.red    { background: rgba(239,68,68,.15);  color: var(--red); }
  .pill.blue   { background: rgba(59,130,246,.15); color: var(--blue); }
  .pill.purple { background: rgba(99,102,241,.15); color: var(--accent); }
  .pill.gray   { background: rgba(100,116,139,.15); color: var(--muted); }

  .hash { font-family: monospace; font-size: 11px; color: var(--muted); }

  /* Buttons */
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
    border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;
    border: none; text-decoration: none; transition: all .15s; }
  .btn-primary { background: var(--accent); color: #fff; }
  .btn-primary:hover { background: #5254cc; }
  .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 6px; }
  .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--text); }
  .btn-ghost:hover { background: var(--bg3); }
  .btn-danger { background: rgba(239,68,68,.15); color: var(--red); border: 1px solid rgba(239,68,68,.2); }
  .btn-danger:hover { background: rgba(239,68,68,.25); }
  .btn-success { background: rgba(16,185,129,.15); color: var(--green); border: 1px solid rgba(16,185,129,.2); }

  /* Modal / Form */
  .modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7);
    z-index: 100; align-items: center; justify-content: center;
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: var(--bg2); border: 1px solid var(--border); border-radius: 16px;
    width: 100%; max-width: 540px; max-height: 85vh; overflow-y: auto;
  }
  .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between; }
  .modal-title { font-size: 16px; font-weight: 600; }
  .modal-body { padding: 24px; }
  .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border);
    display: flex; justify-content: flex-end; gap: 10px; }

  .form-group { margin-bottom: 16px; }
  .form-label { display: block; font-size: 12px; font-weight: 600; color: var(--muted);
    text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
  .form-control {
    width: 100%; background: var(--bg3); border: 1px solid var(--border);
    border-radius: 8px; padding: 9px 12px; color: var(--text); font-size: 13.5px;
    outline: none; transition: border-color .15s; font-family: inherit;
  }
  .form-control:focus { border-color: var(--accent); }
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  select.form-control option { background: var(--bg2); }

  .close-btn { background: none; border: none; color: var(--muted); font-size: 20px;
    cursor: pointer; padding: 4px; border-radius: 4px; transition: color .15s; }
  .close-btn:hover { color: var(--text); }

  /* Empty state */
  .empty { text-align: center; padding: 48px 24px; }
  .empty-icon { font-size: 40px; margin-bottom: 12px; opacity: .4; }
  .empty p { color: var(--muted); font-size: 14px; }

  /* LPS badge */
  .lps-segment { display: inline-flex; align-items: center; gap: 6px; }
  .lps-dot { width: 8px; height: 8px; border-radius: 50%; }

  .member-tag { display: inline-flex; align-items: center; gap: 4px; font-size: 11px;
    font-weight: 600; color: var(--muted); background: var(--bg3); padding: 2px 8px; border-radius: 4px; }
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <h1>HR<span>Tech</span> Core</h1>
    <p>PUCPR · Eng. de Software · 2026</p>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Dashboard</div>
    <a href="?page=dashboard" class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>">
      <span class="icon">📊</span> Visão Geral
    </a>

    <div class="nav-section" style="margin-top:12px">Item 02 — CRUDs</div>
    <a href="?page=tenants" class="nav-link <?= $page === 'tenants' ? 'active' : '' ?>">
      <span class="icon">🏢</span> Tenants
      <span class="badge"><?= $stats['tenants'] ?></span>
    </a>
    <a href="?page=employees" class="nav-link <?= $page === 'employees' ? 'active' : '' ?>">
      <span class="icon">👤</span> Colaboradores
      <span class="badge green"><?= $stats['employees'] ?></span>
    </a>
    <a href="?page=timelogs" class="nav-link <?= $page === 'timelogs' ? 'active' : '' ?>">
      <span class="icon">🕐</span> Ponto Eletrônico
      <span class="badge"><?= $stats['timelogs'] ?></span>
    </a>
    <a href="?page=vacations" class="nav-link <?= $page === 'vacations' ? 'active' : '' ?>">
      <span class="icon">🏖️</span> Férias
      <?php if ($stats['vacations'] > 0): ?>
      <span class="badge yellow"><?= $stats['vacations'] ?></span>
      <?php endif; ?>
    </a>
    <a href="?page=benefits" class="nav-link <?= $page === 'benefits' ? 'active' : '' ?>">
      <span class="icon">💳</span> Benefícios
      <span class="badge green"><?= $stats['benefits'] ?></span>
    </a>
  </nav>
  <div class="sidebar-footer">
    <p>PHP <?= PHP_VERSION ?> · SQLite PDO</p>
    <p style="margin-top:4px">868 testes ✔ 100% PASS</p>
  </div>
</aside>

<!-- Main -->
<main class="main">

<?php if ($message): ?>
<div style="position:fixed;top:16px;right:16px;z-index:200">
  <div class="alert success" style="min-width:280px;box-shadow:0 8px 24px rgba(0,0,0,.4)">
    ✔ <?= htmlspecialchars($message) ?>
  </div>
</div>
<script>setTimeout(() => document.querySelector('.alert')?.remove(), 3000)</script>
<?php endif; ?>

<?php if ($error): ?>
<div style="position:fixed;top:16px;right:16px;z-index:200">
  <div class="alert danger" style="min-width:280px;box-shadow:0 8px 24px rgba(0,0,0,.4)">
    ✖ <?= htmlspecialchars($error) ?>
  </div>
</div>
<script>setTimeout(() => document.querySelector('.alert')?.remove(), 5000)</script>
<?php endif; ?>

<!-- ============================================================ DASHBOARD -->
<?php if ($page === 'dashboard'): ?>
<div class="topbar">
  <div>
    <h2>Visão Geral — HRTech Core</h2>
    <div class="breadcrumb">Painel de CRUDs · PHP 8.3 · SQLite · Laravel 11 Clean Architecture</div>
  </div>
</div>
<div class="content">

  <div class="stats-grid">
    <a href="?page=tenants" style="text-decoration:none">
      <div class="stat-card purple">
        <div class="stat-label">Tenants</div>
        <div class="stat-value" style="color:var(--accent)"><?= $stats['tenants'] ?></div>
        <div class="stat-sub">Fernando · CRUD 1 e 2</div>
      </div>
    </a>
    <a href="?page=employees" style="text-decoration:none">
      <div class="stat-card green">
        <div class="stat-label">Colaboradores</div>
        <div class="stat-value" style="color:var(--green)"><?= $stats['employees'] ?></div>
        <div class="stat-sub">Andryus · CRUD 3 e 4</div>
      </div>
    </a>
    <a href="?page=timelogs" style="text-decoration:none">
      <div class="stat-card blue">
        <div class="stat-label">Batidas de Ponto</div>
        <div class="stat-value" style="color:var(--blue)"><?= $stats['timelogs'] ?></div>
        <div class="stat-sub">Felipe · CRUD 5 e 6</div>
      </div>
    </a>
    <a href="?page=vacations" style="text-decoration:none">
      <div class="stat-card yellow">
        <div class="stat-label">Férias Pendentes</div>
        <div class="stat-value" style="color:var(--yellow)"><?= $stats['vacations'] ?></div>
        <div class="stat-sub">Valentin · CRUD 7 e 8</div>
      </div>
    </a>
    <a href="?page=benefits" style="text-decoration:none">
      <div class="stat-card red">
        <div class="stat-label">Benefícios Ativos</div>
        <div class="stat-value" style="color:var(--red)"><?= $stats['benefits'] ?></div>
        <div class="stat-sub">Nicholas · CRUD 9 e 10</div>
      </div>
    </a>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Equipe de Engenharia — 5 Integrantes</div>
        <div class="card-sub">Divisão oficial dos 10 CRUDs</div>
      </div>
    </div>
    <table>
      <thead><tr><th>#</th><th>Integrante</th><th>Módulo</th><th>CRUD 1</th><th>CRUD 2</th><th>Stack</th></tr></thead>
      <tbody>
        <tr><td><b>1</b></td><td><b>Fernando Lopes Duarte</b></td><td>Core & Auth</td><td>Gestão de Tenants</td><td>Gestão de Usuários & RBAC</td><td><span class="pill purple">TenantRepository</span></td></tr>
        <tr><td><b>2</b></td><td><b>Andryus</b></td><td>RH & Estrutura</td><td>Cadastro de Colaboradores</td><td>Cargos e Departamentos</td><td><span class="pill green">EmployeeRepository</span></td></tr>
        <tr><td><b>3</b></td><td><b>Felipe</b></td><td>Ponto & Frequência</td><td>Registro de Ponto (SHA-256)</td><td>Ajustes de Marcação</td><td><span class="pill blue">TimeLogRepository</span></td></tr>
        <tr><td><b>4</b></td><td><b>Valentin</b></td><td>Benefícios & Férias</td><td>Gestão de Férias CLT</td><td>Catálogo de Benefícios</td><td><span class="pill yellow">VacationRepository</span></td></tr>
        <tr><td><b>5</b></td><td><b>Nicholas</b></td><td>Compliance & Seguros</td><td>EPIs & Exames ASO</td><td>Apólices Corretora FinCorp</td><td><span class="pill red">EquipmentASORepository</span></td></tr>
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">8 Padrões de Projeto GoF implementados (Item 01 do Slide)</div>
    </div>
    <table>
      <thead><tr><th>Padrão</th><th>Classe</th><th>Finalidade</th></tr></thead>
      <tbody>
        <tr><td><span class="pill purple">Singleton</span></td><td><code>TenantContextManager</code></td><td>Contexto global do tenant ativo (sessão isolada)</td></tr>
        <tr><td><span class="pill purple">Singleton</span></td><td><code>AuditLogger</code></td><td>Trilha de auditoria imutável com hash SHA-256 encadeado</td></tr>
        <tr><td><span class="pill blue">Template Method</span></td><td><code>PayrollCalculatorTemplate</code></td><td>Cálculo de folha CLT / PJ / Estágio</td></tr>
        <tr><td><span class="pill blue">Template Method</span></td><td><code>TimeLogImporterTemplate</code></td><td>Importação de ponto CSV / JSON / API biométrica</td></tr>
        <tr><td><span class="pill blue">Template Method</span></td><td><code>ReportGeneratorTemplate</code></td><td>Geração de relatórios PDF / Excel / JSON</td></tr>
        <tr><td><span class="pill green">Strategy</span></td><td><code>OvertimeStrategy</code></td><td>Horas extras: 50% / 100% Domingo / Banco de Horas</td></tr>
        <tr><td><span class="pill green">Strategy</span></td><td><code>BenefitDiscountStrategy</code></td><td>Desconto de benefícios: VT / Saúde / VR-VA (PAT)</td></tr>
        <tr><td><span class="pill green">Strategy</span></td><td><code>PerformanceBonusStrategy</code></td><td>Avaliação de desempenho: OKR / 360° / KPI</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ============================================================ TENANTS -->
<?php elseif ($page === 'tenants'): ?>
<div class="topbar">
  <div>
    <h2>🏢 Gestão de Tenants (Empresas)</h2>
    <div class="breadcrumb">Fernando Lopes Duarte · CRUD 1 — TenantRepository · tabela: tenants</div>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openModal('modal-tenant')">+ Novo Tenant</button>
  </div>
</div>
<div class="content">
  <?php if (empty($tenants)): ?>
    <div class="card"><div class="empty"><div class="empty-icon">🏢</div><p>Nenhum tenant cadastrado. Clique em "+ Novo Tenant" para começar.</p></div></div>
  <?php else: ?>
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Tenants cadastrados</div><div class="card-sub"><?= count($tenants) ?> empresa(s) no banco de dados</div></div>
    </div>
    <table>
      <thead><tr><th>Nome</th><th>CNPJ</th><th>Segmento LPS</th><th>Plano</th><th>Status</th><th>Cadastro</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($tenants as $t): ?>
        <tr>
          <td><b><?= htmlspecialchars($t['name']) ?></b><br><small style="color:var(--muted)"><?= htmlspecialchars($t['trading_name'] ?? '') ?></small></td>
          <td><span class="hash"><?= htmlspecialchars($t['cnpj'] ?? '—') ?></span></td>
          <td>
            <?php
              $seg = $t['segment'] ?? 'tech';
              $segColor = match($seg) { 'industry' => 'yellow', 'financial' => 'blue', default => 'purple' };
              $segLabel = match($seg) { 'industry' => 'Indústria', 'financial' => 'Financeiro', default => 'Tech' };
            ?>
            <span class="pill <?= $segColor ?>"><?= $segLabel ?></span>
          </td>
          <td><?= htmlspecialchars($t['plan'] ?? '—') ?></td>
          <td><span class="pill <?= $t['active'] ? 'green' : 'red' ?>"><?= $t['active'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td style="color:var(--muted);font-size:12px"><?= substr($t['created_at'] ?? '', 0, 16) ?></td>
          <td>
            <a href="?page=tenants&action=edit&id=<?= urlencode($t['id']) ?>" class="btn btn-ghost btn-sm">✏️</a>
            <a href="?page=tenants&action=delete&id=<?= urlencode($t['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Excluir tenant?')">🗑️</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Create Tenant -->
<div class="modal-overlay <?= $action === 'edit' && $editItem ? '' : '' ?>" id="modal-tenant">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Novo Tenant / Empresa</div>
      <button class="close-btn" onclick="closeModal('modal-tenant')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="page" value="tenants">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Razão Social *</label>
            <input name="name" class="form-control" required placeholder="Empresa LTDA">
          </div>
          <div class="form-group">
            <label class="form-label">Nome Fantasia</label>
            <input name="trading_name" class="form-control" placeholder="Nome fantasia">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">CNPJ *</label>
            <input name="cnpj" class="form-control" required placeholder="00.000.000/0001-00">
          </div>
          <div class="form-group">
            <label class="form-label">Segmento LPS *</label>
            <select name="segment" class="form-control">
              <option value="tech">🚀 Tech / SaaS</option>
              <option value="industry">🏭 Indústria</option>
              <option value="financial">🏦 Financeiro</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Plano</label>
          <select name="plan" class="form-control">
            <option value="starter">Starter</option>
            <option value="professional">Professional</option>
            <option value="enterprise">Enterprise</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-tenant')">Cancelar</button>
        <button type="submit" class="btn btn-primary">💾 Salvar Tenant</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Tenant -->
<?php if ($action === 'edit' && $editItem && $page === 'tenants'): ?>
<div class="modal-overlay open" id="modal-edit-tenant">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Editar Tenant</div>
      <a href="?page=tenants" class="close-btn">×</a>
    </div>
    <form method="POST">
      <input type="hidden" name="page" value="tenants">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= htmlspecialchars($editItem['id']) ?>">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Razão Social</label>
            <input name="name" class="form-control" value="<?= htmlspecialchars($editItem['name']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Nome Fantasia</label>
            <input name="trading_name" class="form-control" value="<?= htmlspecialchars($editItem['trading_name'] ?? '') ?>">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Segmento LPS</label>
            <select name="segment" class="form-control">
              <option value="tech" <?= ($editItem['segment'] ?? '') === 'tech' ? 'selected' : '' ?>>🚀 Tech / SaaS</option>
              <option value="industry" <?= ($editItem['segment'] ?? '') === 'industry' ? 'selected' : '' ?>>🏭 Indústria</option>
              <option value="financial" <?= ($editItem['segment'] ?? '') === 'financial' ? 'selected' : '' ?>>🏦 Financeiro</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Plano</label>
            <select name="plan" class="form-control">
              <option value="starter" <?= ($editItem['plan'] ?? '') === 'starter' ? 'selected' : '' ?>>Starter</option>
              <option value="professional" <?= ($editItem['plan'] ?? '') === 'professional' ? 'selected' : '' ?>>Professional</option>
              <option value="enterprise" <?= ($editItem['plan'] ?? '') === 'enterprise' ? 'selected' : '' ?>>Enterprise</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="?page=tenants" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">💾 Atualizar</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ============================================================ EMPLOYEES -->
<?php elseif ($page === 'employees'): ?>
<div class="topbar">
  <div>
    <h2>👤 Gestão de Colaboradores</h2>
    <div class="breadcrumb">Andryus · CRUD 3 — EmployeeRepository · tabela: employees</div>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openModal('modal-employee')">+ Novo Colaborador</button>
  </div>
</div>
<div class="content">
  <?php if (empty($employees)): ?>
    <div class="card"><div class="empty"><div class="empty-icon">👤</div><p>Nenhum colaborador cadastrado ainda.</p></div></div>
  <?php else: ?>
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Colaboradores ativos</div><div class="card-sub"><?= count($employees) ?> colaborador(es) no banco</div></div>
    </div>
    <table>
      <thead><tr><th>Nome</th><th>CPF</th><th>Email</th><th>Regime</th><th>Salário</th><th>Empresa</th><th>Admissão</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($employees as $e): ?>
        <tr>
          <td><b><?= htmlspecialchars($e['full_name']) ?></b></td>
          <td><span class="hash"><?= htmlspecialchars($e['cpf'] ?? '—') ?></span></td>
          <td><?= htmlspecialchars($e['email'] ?? '—') ?></td>
          <td>
            <?php
              $et = $e['employment_type'] ?? 'CLT';
              $etColor = match(strtoupper($et)) { 'PJ' => 'blue', 'INTERN' => 'yellow', default => 'green' };
            ?>
            <span class="pill <?= $etColor ?>"><?= htmlspecialchars($et) ?></span>
          </td>
          <td>R$ <?= number_format((float)($e['base_salary'] ?? 0), 2, ',', '.') ?></td>
          <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($e['tenant_name'] ?? '—') ?></td>
          <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($e['admission_date'] ?? '—') ?></td>
          <td>
            <a href="?page=employees&action=delete&id=<?= urlencode($e['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Excluir colaborador?')">🗑️</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="modal-employee">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Novo Colaborador</div>
      <button class="close-btn" onclick="closeModal('modal-employee')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="page" value="employees">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Empresa (Tenant) *</label>
          <select name="tenant_id" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach ($tenants as $t): ?>
            <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Nome Completo *</label>
            <input name="full_name" class="form-control" required placeholder="João da Silva">
          </div>
          <div class="form-group">
            <label class="form-label">CPF *</label>
            <input name="cpf" class="form-control" required placeholder="000.000.000-00">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input name="email" type="email" class="form-control" required placeholder="email@empresa.com">
          </div>
          <div class="form-group">
            <label class="form-label">Regime *</label>
            <select name="employment_type" class="form-control">
              <option value="CLT">CLT</option>
              <option value="PJ">PJ</option>
              <option value="INTERN">Estagiário</option>
            </select>
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Salário Base (R$) *</label>
            <input name="base_salary" type="number" step="0.01" class="form-control" required placeholder="5000.00">
          </div>
          <div class="form-group">
            <label class="form-label">Data de Admissão *</label>
            <input name="admission_date" type="date" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-employee')">Cancelar</button>
        <button type="submit" class="btn btn-primary">💾 Contratar</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ TIMELOGS -->
<?php elseif ($page === 'timelogs'): ?>
<div class="topbar">
  <div>
    <h2>🕐 Registro de Ponto (Portaria 671)</h2>
    <div class="breadcrumb">Felipe · CRUD 5 — TimeLogRepository · hash SHA-256 encadeado · tabela: time_logs</div>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openModal('modal-timelog')">+ Nova Batida</button>
  </div>
</div>
<div class="content">
  <?php if (empty($timelogs)): ?>
    <div class="card"><div class="empty"><div class="empty-icon">🕐</div><p>Nenhuma batida registrada.</p></div></div>
  <?php else: ?>
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Batidas de ponto registradas</div><div class="card-sub"><?= count($timelogs) ?> registro(s) · Encadeamento SHA-256 ativo</div></div>
    </div>
    <table>
      <thead><tr><th>NSR</th><th>Colaborador</th><th>Tipo</th><th>Horário</th><th>Hash SHA-256</th><th>IP</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($timelogs as $tl): ?>
        <tr>
          <td><b><?= htmlspecialchars((string)($tl['nsr'] ?? '—')) ?></b></td>
          <td><?= htmlspecialchars($tl['employee_name'] ?? '—') ?></td>
          <td>
            <?php
              $lt = $tl['log_type'] ?? 'ENTRY';
              $ltColor = match($lt) { 'EXIT' => 'red', 'INTERVAL_START' => 'yellow', 'INTERVAL_END' => 'green', default => 'blue' };
            ?>
            <span class="pill <?= $ltColor ?>"><?= htmlspecialchars($lt) ?></span>
          </td>
          <td style="font-size:12px"><?= htmlspecialchars(substr($tl['logged_at'] ?? '', 0, 16)) ?></td>
          <td><span class="hash" title="<?= htmlspecialchars($tl['integrity_hash'] ?? '') ?>"><?= substr($tl['integrity_hash'] ?? '', 0, 16) ?>…</span></td>
          <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($tl['ip_address'] ?? '—') ?></td>
          <td>
            <span class="pill gray btn-sm" title="Imutável por Portaria 671">🔒 Imutável</span>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="modal-timelog">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Registrar Batida de Ponto</div>
      <button class="close-btn" onclick="closeModal('modal-timelog')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="page" value="timelogs">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Empresa (Tenant) *</label>
          <select name="tenant_id" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach ($tenants as $t): ?>
            <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Colaborador *</label>
          <select name="employee_id" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach ($employees as $e): ?>
            <option value="<?= htmlspecialchars($e['id']) ?>"><?= htmlspecialchars($e['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Tipo de Batida *</label>
          <select name="log_type" class="form-control">
            <option value="ENTRY">ENTRY — Entrada</option>
            <option value="INTERVAL_START">INTERVAL_START — Início do Intervalo</option>
            <option value="INTERVAL_END">INTERVAL_END — Fim do Intervalo</option>
            <option value="EXIT">EXIT — Saída</option>
          </select>
        </div>
        <div class="alert success" style="margin-top:8px">
          🔒 Hash SHA-256 será gerado automaticamente no momento do registro (Portaria 671/2021)
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-timelog')">Cancelar</button>
        <button type="submit" class="btn btn-primary">⏱️ Registrar Batida</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ VACATIONS -->
<?php elseif ($page === 'vacations'): ?>
<div class="topbar">
  <div>
    <h2>🏖️ Gestão de Férias</h2>
    <div class="breadcrumb">Valentin · CRUD 7 — VacationRepository · tabela: vacation_requests</div>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openModal('modal-vacation')">+ Solicitar Férias</button>
  </div>
</div>
<div class="content">
  <?php if (empty($vacations)): ?>
    <div class="card"><div class="empty"><div class="empty-icon">🏖️</div><p>Nenhuma solicitação de férias.</p></div></div>
  <?php else: ?>
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Solicitações de Férias</div><div class="card-sub"><?= count($vacations) ?> solicitação(ões)</div></div>
    </div>
    <table>
      <thead><tr><th>Colaborador</th><th>Período</th><th>Dias</th><th>Adto 13º</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($vacations as $v): ?>
        <tr>
          <td><b><?= htmlspecialchars($v['employee_name'] ?? '—') ?></b></td>
          <td style="font-size:12px"><?= htmlspecialchars($v['start_date'] ?? '') ?> → <?= htmlspecialchars($v['end_date'] ?? '') ?></td>
          <td><b><?= htmlspecialchars((string)($v['days_requested'] ?? '—')) ?></b> dias</td>
          <td><?= $v['advance_thirteenth'] ? '<span class="pill green">Sim</span>' : '<span class="pill gray">Não</span>' ?></td>
          <td>
            <?php
              $vs = $v['status'] ?? 'pending';
              $vsColor = match($vs) { 'approved' => 'green', 'rejected' => 'red', default => 'yellow' };
            ?>
            <span class="pill <?= $vsColor ?>"><?= ucfirst(htmlspecialchars($vs)) ?></span>
          </td>
          <td>
            <?php if ($vs === 'pending'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="page" value="vacations">
              <input type="hidden" name="action" value="approve">
              <input type="hidden" name="id" value="<?= htmlspecialchars($v['id']) ?>">
              <button type="submit" class="btn btn-success btn-sm">✔ Aprovar</button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="page" value="vacations">
              <input type="hidden" name="action" value="reject">
              <input type="hidden" name="id" value="<?= htmlspecialchars($v['id']) ?>">
              <button type="submit" class="btn btn-danger btn-sm">✖ Rejeitar</button>
            </form>
            <?php endif; ?>
            <a href="?page=vacations&action=delete&id=<?= urlencode($v['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Excluir solicitação?')">🗑️</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="modal-vacation">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Solicitar Férias</div>
      <button class="close-btn" onclick="closeModal('modal-vacation')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="page" value="vacations">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Empresa (Tenant) *</label>
          <select name="tenant_id" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach ($tenants as $t): ?>
            <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Colaborador *</label>
          <select name="employee_id" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach ($employees as $e): ?>
            <option value="<?= htmlspecialchars($e['id']) ?>"><?= htmlspecialchars($e['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Data Início *</label>
            <input name="start_date" type="date" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Data Fim *</label>
            <input name="end_date" type="date" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Dias Solicitados *</label>
          <input name="days_requested" type="number" min="5" max="30" class="form-control" required placeholder="15">
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
            <input name="advance_thirteenth" type="checkbox" style="width:16px;height:16px">
            Solicitar adiantamento do 13º salário
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-vacation')">Cancelar</button>
        <button type="submit" class="btn btn-primary">🏖️ Solicitar Férias</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ BENEFITS -->
<?php elseif ($page === 'benefits'): ?>
<div class="topbar">
  <div>
    <h2>💳 Gestão de Benefícios</h2>
    <div class="breadcrumb">Valentin · CRUD 8 — BenefitRepository · tabela: benefits</div>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openModal('modal-benefit')">+ Novo Benefício</button>
  </div>
</div>
<div class="content">
  <?php if (empty($benefits)): ?>
    <div class="card"><div class="empty"><div class="empty-icon">💳</div><p>Nenhum benefício cadastrado.</p></div></div>
  <?php else: ?>
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Benefícios concedidos</div><div class="card-sub"><?= count($benefits) ?> benefício(s) ativo(s)</div></div>
    </div>
    <table>
      <thead><tr><th>Colaborador</th><th>Tipo</th><th>Operadora</th><th>Valor Mensal</th><th>Coparticipação</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($benefits as $b): ?>
        <tr>
          <td><b><?= htmlspecialchars($b['employee_name'] ?? '—') ?></b></td>
          <td>
            <?php
              $bt = $b['benefit_type'] ?? '';
              $btLabel = match($bt) {
                'MEAL_VOUCHER' => '🍽️ Vale Refeição', 'TRANSPORT' => '🚌 Vale Transporte',
                'HEALTH_PLAN' => '🏥 Plano de Saúde', 'DENTAL' => '🦷 Plano Odontológico', default => $bt
              };
            ?>
            <span><?= $btLabel ?></span>
          </td>
          <td><?= htmlspecialchars($b['provider'] ?? '—') ?></td>
          <td>R$ <?= number_format((float)($b['monthly_value'] ?? 0), 2, ',', '.') ?></td>
          <td><?= number_format((float)($b['copayment_pct'] ?? 0), 1) ?>%</td>
          <td><span class="pill <?= $b['active'] ? 'green' : 'red' ?>"><?= $b['active'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td>
            <a href="?page=benefits&action=delete&id=<?= urlencode($b['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Excluir benefício?')">🗑️</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="modal-benefit">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Conceder Benefício</div>
      <button class="close-btn" onclick="closeModal('modal-benefit')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="page" value="benefits">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Empresa (Tenant) *</label>
          <select name="tenant_id" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach ($tenants as $t): ?>
            <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Colaborador *</label>
          <select name="employee_id" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach ($employees as $e): ?>
            <option value="<?= htmlspecialchars($e['id']) ?>"><?= htmlspecialchars($e['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Tipo de Benefício *</label>
          <select name="benefit_type" class="form-control">
            <option value="MEAL_VOUCHER">🍽️ Vale Refeição</option>
            <option value="TRANSPORT">🚌 Vale Transporte</option>
            <option value="HEALTH_PLAN">🏥 Plano de Saúde</option>
            <option value="DENTAL">🦷 Plano Odontológico</option>
          </select>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Operadora *</label>
            <input name="provider" class="form-control" required placeholder="Sodexo, Unimed...">
          </div>
          <div class="form-group">
            <label class="form-label">Valor Mensal (R$) *</label>
            <input name="monthly_value" type="number" step="0.01" class="form-control" required placeholder="800.00">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Coparticipação do Colaborador (%)</label>
          <input name="copayment_pct" type="number" step="0.1" min="0" max="100" class="form-control" placeholder="15.0" value="0">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-benefit')">Cancelar</button>
        <button type="submit" class="btn btn-primary">💳 Conceder Benefício</button>
      </div>
    </form>
  </div>
</div>

<?php endif; ?>

</main>

<script>
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
}
function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
}
// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});
// Close on ESC
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});
</script>
</body>
</html>
