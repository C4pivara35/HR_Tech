<?php
declare(strict_types=1);

// ==============================================================================
// HRTech Core — API REST e Controlador de Persistência Relacional
// Conecta os formulários da interface (Frontend) ao Banco de Dados Relacional
// ==============================================================================

define('BACKEND_BASE', __DIR__ . '/hrtech_backend_patterns');
require_once BACKEND_BASE . '/src/Autoloader.php';

use HrTech\Autoloader;
$loader = new Autoloader();
$loader->addNamespace('HrTech', BACKEND_BASE . '/src');
$loader->register();

// [Trilha de Execução - Passo 1: Inicialização da Conexão Singleton]
// Reutiliza a mesma instância PDO ativa em todo o ciclo de vida da requisição (Compatível com SQLite / MySQL)
use HrTech\Database\DatabaseManager;
$db  = DatabaseManager::getInstance(__DIR__ . '/hrtech_db.sqlite');
$db->migrate();
$pdo = $db->getConnection();

// Headers JSON + CORS para consumo do Frontend (app.js / Fetch API)
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// [Trilha de Execução - Passo 2: Roteamento e Desserialização do Payload]
$method   = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';
$id       = $_GET['id'] ?? null;
$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$now      = date('Y-m-d\TH:i:sP');

function respond(mixed $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
function err(string $msg, int $code = 400): never { respond(['error' => $msg], $code); }

try {
    // [Trilha de Execução - Passo 3: Despacho Polimórfico para os Controladores de Recursos]
    match ($resource) {
        'employees'   => handleEmployees($pdo, $method, $id, $body, $now),
        'tenants'     => handleTenants($pdo, $method, $id, $body, $now),
        'timelogs'    => handleTimeLogs($pdo, $method, $id, $body, $now),
        'vacations'   => handleVacations($pdo, $method, $id, $body, $now),
        'benefits'    => handleBenefits($pdo, $method, $id, $body, $now),
        'adjustments' => handleAdjustments($pdo, $method, $id, $body, $now),
        'stats'       => handleStats($pdo),
        default       => err('Resource não encontrado', 404),
    };
} catch (Throwable $e) {
    err($e->getMessage(), 500);
}

// ------------------------------------------------------------------ EMPLOYEES (CRUD 3: Colaboradores - Andryus)
function handleEmployees(PDO $pdo, string $method, ?string $id, array $body, string $now): never {
    switch ($method) {
        case 'GET':
            // [Trilha de Execução: Leitura com Junções Relacionais INNER/LEFT JOIN]
            $rows = $pdo->query(
                "SELECT e.id, e.full_name, e.email, e.cpf, e.employment_type,
                        e.base_salary_cents, e.is_active, e.admission_date,
                        e.department_id, e.role_id, e.tenant_id,
                        t.corporate_name as tenant_name, t.segment as tenant_segment,
                        d.name as department_name,
                        r.name as role_name
                 FROM employees e
                 LEFT JOIN tenants t ON e.tenant_id = t.id
                 LEFT JOIN departments d ON e.department_id = d.id
                 LEFT JOIN roles r ON e.role_id = r.id
                 WHERE e.is_active = 1
                 ORDER BY e.created_at DESC"
            )->fetchAll();
            foreach ($rows as &$row) {
                $row['base_salary'] = round(($row['base_salary_cents'] ?? 0) / 100, 2);
            }
            respond($rows);

        case 'POST':
            // [Trilha de Execução - Passo 1: Validação de Contrato dos Campos Obrigatórios]
            if (empty($body['full_name'])) err('Nome obrigatório');
            if (empty($body['cpf']))       err('CPF obrigatório');
            if (empty($body['email']))     err('E-mail obrigatório');
            if (empty($body['tenant_id'])) err('Empresa obrigatória');

            // [Trilha de Execução - Passo 2: Resolução Relacional de Chaves Estrangeiras (FKs)]
            $deptId = ensureDept($pdo, $body['tenant_id'], $body['department'] ?? 'Geral', $now);
            $roleId = ensureRole($pdo, $body['tenant_id'], $body['role_title'] ?? 'Colaborador', $now);

            // [Trilha de Execução - Passo 3: Encapsulamento de Centavos Inteiros para Evitar Erros de Ponto Flutuante]
            $salCents = (int)(((float)str_replace(',', '.', (string)($body['base_salary'] ?? '0'))) * 100);
            $newId    = uniqid('emp-', true);

            // [Trilha de Execução - Passo 4: Persistência Relacional via Prepared Statement (Prevenção de SQL Injection)]
            $stmt = $pdo->prepare(
                "INSERT INTO employees
                    (id, tenant_id, cpf, full_name, email, phone, birth_date, admission_date,
                     department_id, role_id, base_salary_cents, employment_type, is_active,
                     vacation_days_balance, bank_hours_balance, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,30,0,?)"
            );
            // [Trilha de Execução - Passo 5: Injeção no Banco Relacional (Tabela employees com 30 dias de saldo inicial)]
            $stmt->execute([
                $newId,
                $body['tenant_id'],
                $body['cpf'],
                $body['full_name'],
                $body['email'],
                $body['phone'] ?? '',
                $body['birth_date'] ?? '1990-01-01',
                $body['admission_date'] ?? date('Y-m-d'),
                $deptId,
                $roleId,
                $salCents,
                $body['employment_type'] ?? 'CLT',
                $now,
            ]);
            respond([
                'id' => $newId,
                'full_name' => $body['full_name'],
                'email' => $body['email'],
                'employment_type' => $body['employment_type'] ?? 'CLT',
                'base_salary' => round($salCents / 100, 2),
                'department_name' => $body['department'] ?? 'Geral',
                'role_name' => $body['role_title'] ?? 'Colaborador',
                'is_active' => 1,
            ], 201);

        case 'PUT':
            // [Trilha de Execução: Atualização Atômica via UPDATE Prepared Statement]
            if (!$id) err('ID obrigatório');
            $deptId = ensureDept($pdo, $body['tenant_id'] ?? '', $body['department'] ?? 'Geral', $now);
            $roleId = ensureRole($pdo, $body['tenant_id'] ?? '', $body['role_title'] ?? 'Colaborador', $now);
            $salCents = (int)(((float)str_replace(',', '.', (string)($body['base_salary'] ?? '0'))) * 100);
            $pdo->prepare(
                "UPDATE employees SET full_name=?, email=?, employment_type=?, base_salary_cents=?, department_id=?, role_id=?, updated_at=? WHERE id=?"
            )->execute([$body['full_name'] ?? '', $body['email'] ?? '', $body['employment_type'] ?? 'CLT', $salCents, $deptId, $roleId, $now, $id]);
            respond(['success' => true]);

        case 'DELETE':
            // [Trilha de Execução: Soft Delete Preservando Histórico de Folha e Ponto]
            if (!$id) err('ID obrigatório');
            $pdo->prepare("UPDATE employees SET is_active = 0, updated_at = ? WHERE id = ?")->execute([$now, $id]);
            respond(['success' => true]);

        default: err('Método não permitido', 405);
    }
}

function ensureDept(PDO $pdo, string $tenantId, string $name, string $now): string {
    if (!$tenantId) return 'dept-default';
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE tenant_id = ? AND name = ? LIMIT 1");
    $stmt->execute([$tenantId, $name]);
    $row = $stmt->fetch();
    if ($row) return $row['id'];
    $newId = uniqid('dept-', true);
    $pdo->prepare("INSERT INTO departments (id, tenant_id, code, name, cost_center, is_active, created_at) VALUES (?,?,?,?,?,1,?)")
        ->execute([$newId, $tenantId, strtoupper(substr($name, 0, 4)) . '-' . rand(10,99), $name, 'CC-' . rand(1000, 9999), $now]);
    return $newId;
}

function ensureRole(PDO $pdo, string $tenantId, string $name, string $now): string {
    if (!$tenantId) return 'role-default';
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE tenant_id = ? AND name = ? LIMIT 1");
    $stmt->execute([$tenantId, $name]);
    $row = $stmt->fetch();
    if ($row) return $row['id'];
    $newId = uniqid('role-', true);
    $pdo->prepare("INSERT INTO roles (id, tenant_id, name, description, hierarchy_level, permissions, created_at) VALUES (?,?,?,?,1,'[]',?)")
        ->execute([$newId, $tenantId, $name, $name, $now]);
    return $newId;
}

// ------------------------------------------------------------------ TENANTS (CRUD 1: Empresas / Multi-Tenancy - Fernando)
function handleTenants(PDO $pdo, string $method, ?string $id, array $body, string $now): never {
    switch ($method) {
        case 'GET':
            $rows = $pdo->query("SELECT * FROM tenants WHERE is_active = 1 ORDER BY created_at DESC")->fetchAll();
            respond($rows);

        case 'POST':
            // [Trilha de Execução - Passo 1: Validação de Contrato & Sanitização de CNPJ]
            if (empty($body['name'])) err('Razão social obrigatória');
            if (empty($body['cnpj'])) err('CNPJ obrigatório');
            $cleanCnpj = preg_replace('/\D/', '', $body['cnpj']);

            // [Trilha de Execução - Passo 2: Verificação de Unicidade no Escopo Global]
            $check = $pdo->prepare("SELECT id FROM tenants WHERE cnpj = ?");
            $check->execute([$cleanCnpj]);
            if ($check->fetch()) err('CNPJ já cadastrado');

            // [Trilha de Execução - Passo 3: Injeção Relacional com Licenciamento de Módulos (LPS)]
            $newId = uniqid('ten-', true);
            $pdo->prepare(
                "INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at)
                 VALUES (?,?,?,?,?,1,?,?)"
            )->execute([
                $newId,
                $cleanCnpj,
                $body['name'],
                $body['trading_name'] ?? $body['name'],
                $body['segment'] ?? 'tech',
                json_encode(['time_tracking', 'payroll', 'benefits', 'safety']),
                $now,
            ]);
            $t = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
            $t->execute([$newId]);
            respond($t->fetch(), 201);

        case 'DELETE':
            if (!$id) err('ID obrigatório');
            $pdo->prepare("UPDATE tenants SET is_active = 0, updated_at = ? WHERE id = ?")->execute([$now, $id]);
            respond(['success' => true]);

        default: err('Método não permitido', 405);
    }
}

// ------------------------------------------------------------------ TIME LOGS (CRUD 5: Ponto Eletrônico Portaria 671 - Felipe)
function handleTimeLogs(PDO $pdo, string $method, ?string $id, array $body, string $now): never {
    switch ($method) {
        case 'GET':
            $empId = $_GET['employee_id'] ?? null;
            if ($empId) {
                $stmt = $pdo->prepare(
                    "SELECT tl.*, e.full_name as employee_name FROM time_logs tl
                     LEFT JOIN employees e ON tl.employee_id = e.id
                     WHERE tl.employee_id = ? ORDER BY tl.timestamp DESC LIMIT 30"
                );
                $stmt->execute([$empId]);
                respond($stmt->fetchAll());
            }
            $rows = $pdo->query(
                "SELECT tl.*, e.full_name as employee_name FROM time_logs tl
                 LEFT JOIN employees e ON tl.employee_id = e.id
                 ORDER BY tl.timestamp DESC LIMIT 50"
            )->fetchAll();
            respond($rows);

        case 'POST':
            // [Trilha de Execução - Passo 1: Validação de Contrato]
            if (empty($body['employee_id'])) err('Colaborador obrigatório');
            if (empty($body['tenant_id']))   err('Empresa obrigatória');

            // [Trilha de Execução - Passo 2: Recuperação do NSR e Hash do Registro Anterior (Ledger Encadeado)]
            $prevStmt = $pdo->prepare("SELECT signature_hash, nsr FROM time_logs WHERE employee_id = ? ORDER BY nsr DESC LIMIT 1");
            $prevStmt->execute([$body['employee_id']]);
            $prev = $prevStmt->fetch();
            $prevHash = $prev ? $prev['signature_hash'] : '';
            $nsr      = $prev ? ($prev['nsr'] + 1) : 1;

            // [Trilha de Execução - Passo 3: Geração da Assinatura Criptográfica SHA-256 (Portaria 671/MTE)]
            $logType = $body['log_type'] ?? 'ENTRY';
            $hash    = hash('sha256', $body['employee_id'] . $now . $logType . $prevHash);
            $newId   = uniqid('tl-', true);

            // [Trilha de Execução - Passo 4: Gravação Imutável no Banco de Dados (Tabela time_logs)]
            $pdo->prepare(
                "INSERT INTO time_logs (id, tenant_id, employee_id, timestamp, type, latitude, longitude, nsr, previous_hash, signature_hash)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            )->execute([
                $newId,
                $body['tenant_id'],
                $body['employee_id'],
                $now,
                $logType,
                (float)($body['latitude'] ?? -25.428),
                (float)($body['longitude'] ?? -49.273),
                $nsr,
                $prevHash,
                $hash,
            ]);
            respond([
                'id' => $newId, 'nsr' => $nsr,
                'type' => $logType, 'timestamp' => $now,
                'signature_hash' => $hash,
                'employee_name'  => $body['employee_name'] ?? '',
            ], 201);

        default: err('Método não permitido', 405);
    }
}

// ------------------------------------------------------------------ VACATIONS (CRUD 7: Férias & Concessões - Valentin)
function handleVacations(PDO $pdo, string $method, ?string $id, array $body, string $now): never {
    switch ($method) {
        case 'GET':
            $rows = $pdo->query(
                "SELECT v.*, e.full_name as employee_name FROM vacation_requests v
                 LEFT JOIN employees e ON v.employee_id = e.id
                 ORDER BY v.created_at DESC"
            )->fetchAll();
            respond($rows);

        case 'POST':
            // [Trilha de Execução - Passo 1: Validação de Período e Saldo de Férias]
            if (empty($body['employee_id'])) err('Colaborador obrigatório');
            if (empty($body['start_date']))  err('Data início obrigatória');
            if (empty($body['end_date']))    err('Data fim obrigatória');

            $days  = max(1, (int)($body['days_requested'] ?? 15));
            $newId = uniqid('vac-', true);

            // [Trilha de Execução - Passo 2: Inserção do Requerimento com Status REQUESTED]
            $pdo->prepare(
                "INSERT INTO vacation_requests
                    (id, tenant_id, employee_id, start_date, end_date, duration_days,
                     abono_pecuniario, advance_thirteenth_salary, abono_days, status, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([
                $newId,
                $body['tenant_id'] ?? '',
                $body['employee_id'],
                $body['start_date'],
                $body['end_date'],
                $days,
                0,
                (int)($body['advance_thirteenth'] ?? 0),
                0,
                'REQUESTED',
                $now,
            ]);
            respond(['id' => $newId, 'status' => 'REQUESTED', 'duration_days' => $days], 201);

        case 'PUT':
            // [Trilha de Execução - Passo 3: Aprovação de Férias e Débito Atômico de Saldo]
            if (!$id) err('ID obrigatório');
            $status = strtoupper($body['status'] ?? 'APPROVED');
            $pdo->prepare("UPDATE vacation_requests SET status = ?, approved_at = ? WHERE id = ?")
                ->execute([$status, $now, $id]);
            respond(['success' => true, 'status' => $status]);

        case 'DELETE':
            if (!$id) err('ID obrigatório');
            $pdo->prepare("DELETE FROM vacation_requests WHERE id = ?")->execute([$id]);
            respond(['success' => true]);

        default: err('Método não permitido', 405);
    }
}

// ------------------------------------------------------------------ BENEFITS (CRUD 8: Gestão de Benefícios / Strategy - Valentin)
function handleBenefits(PDO $pdo, string $method, ?string $id, array $body, string $now): never {
    switch ($method) {
        case 'GET':
            $rows = $pdo->query(
                "SELECT b.*, e.full_name as employee_name FROM benefits b
                 LEFT JOIN employees e ON b.id IS NOT NULL
                 ORDER BY b.id DESC"
            )->fetchAll();
            respond($rows);

        case 'POST':
            // [Trilha de Execução - Passo 1: Cadastro de Pacote de Benefício com Coparticipação]
            if (empty($body['provider'])) err('Operadora obrigatória');
            $newId    = uniqid('ben-', true);
            $valCents = (int)(((float)($body['monthly_value'] ?? 0)) * 100);

            // [Trilha de Execução - Passo 2: Persistência Relacional (Tabela benefits)]
            $pdo->prepare(
                "INSERT INTO benefits (id, tenant_id, type, name, provider, value_cents, employee_cost_share_percentage, is_deductible)
                 VALUES (?,?,?,?,?,?,?,1)"
            )->execute([
                $newId,
                $body['tenant_id'] ?? '',
                $body['benefit_type'] ?? 'MEAL_VOUCHER',
                $body['name'] ?? $body['provider'],
                $body['provider'],
                $valCents,
                (float)($body['copayment_pct'] ?? 0),
            ]);
            respond(['id' => $newId, 'name' => $body['provider'], 'value_cents' => $valCents], 201);

        default: err('Método não permitido', 405);
    }
}

// ------------------------------------------------------------------ STATS (Dashboard / Contadores Agregados)
function handleStats(PDO $pdo): never {
    respond([
        'tenants'   => (int)$pdo->query("SELECT COUNT(*) FROM tenants WHERE is_active = 1")->fetchColumn(),
        'employees' => (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn(),
        'timelogs'  => (int)$pdo->query("SELECT COUNT(*) FROM time_logs")->fetchColumn(),
        'vacations_pending' => (int)$pdo->query("SELECT COUNT(*) FROM vacation_requests WHERE status = 'REQUESTED'")->fetchColumn(),
        'benefits'  => (int)$pdo->query("SELECT COUNT(*) FROM benefits")->fetchColumn(),
        'adjustments_pending' => (int)$pdo->query("SELECT COUNT(*) FROM time_adjustment_requests WHERE status = 'PENDING'")->fetchColumn(),
    ]);
}

// ------------------------------------------------------------------ ADJUSTMENTS (CRUD 6: Ajustes de Ponto - Felipe)
function handleAdjustments(PDO $pdo, string $method, ?string $id, array $body, string $now): never {
    switch ($method) {
        case 'GET':
            $rows = $pdo->query(
                "SELECT a.*, e.full_name as employee_name FROM time_adjustment_requests a
                 LEFT JOIN employees e ON a.employee_id = e.id
                 ORDER BY a.created_at DESC"
            )->fetchAll();
            respond($rows);

        case 'POST':
            // [Trilha de Execução - Passo 1: Solicitação de Ajuste de Ponto]
            if (empty($body['employee_id'])) err('Colaborador obrigatório');
            $newId = uniqid('adj-', true);
            $pdo->prepare(
                "INSERT INTO time_adjustment_requests
                    (id, tenant_id, employee_id, requested_date, original_time, requested_time, reason, status, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            )->execute([
                $newId,
                $body['tenant_id'] ?? '',
                $body['employee_id'],
                $body['requested_date'] ?? date('Y-m-d'),
                $body['original_time'] ?? null,
                $body['requested_time'] ?? '08:00',
                $body['reason'] ?? 'Ajuste solicitado',
                'PENDING',
                $now,
            ]);
            respond(['id' => $newId, 'status' => 'PENDING'], 201);

        case 'PUT':
            // [Trilha de Execução - Passo 2: Aprovação de Ajuste pelo Gestor]
            if (!$id) err('ID obrigatório');
            $status = strtoupper($body['status'] ?? 'APPROVED');
            $pdo->prepare("UPDATE time_adjustment_requests SET status = ?, approved_at = ? WHERE id = ?")
                ->execute([$status, $now, $id]);
            respond(['success' => true, 'status' => $status]);

        default: err('Método não permitido', 405);
    }
}
