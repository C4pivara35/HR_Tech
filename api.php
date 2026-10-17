<?php
declare(strict_types=1);

// ==============================================================================
// HRTech Core — API REST e Controlador de Persistência Relacional
// ==============================================================================
// Camada de Roteamento HTTP que conecta a Interface Web (app.js) ao Banco
// de Dados Relacional (SQLite / MySQL), orquestrando as regras de negócio
// e os 10 CRUDs distribuídos entre os 5 membros da equipe:
//
// - Fernando Lopes Duarte: CRUD 1 (Tenants) & CRUD 2 (Usuários/Autenticação)
// - Andryus: CRUD 3 (Colaboradores) & CRUD 4 (Departamentos e Cargos)
// - Felipe: CRUD 5 (Ponto Eletrônico Portaria 671) & CRUD 6 (Ajustes de Ponto)
// - Valentin: CRUD 7 (Férias CLT) & CRUD 8 (Benefícios Corporativos)
// - Nicholas: CRUD 9 (EPI & ASO / NR-6 e NR-7) & CRUD 10 (Seguro de Vida FinCorp)
// ==============================================================================

define('BACKEND_BASE', __DIR__ . '/hrtech_backend_patterns');
require_once BACKEND_BASE . '/src/Autoloader.php';

// [Trilha de Execução - Passo 1: Autoloader PSR-4]
// Registra o namespace raiz 'HrTech' mapeando para a pasta 'src/'
use HrTech\Autoloader;
$loader = new Autoloader();
$loader->addNamespace('HrTech', BACKEND_BASE . '/src');
$loader->register();

// [Trilha de Execução - Passo 2: Conexão Singleton com o Banco Relacional]
// Garante o reaproveitamento de uma única instância PDO em todo o ciclo de vida
// da requisição HTTP (padrão de projeto Singleton do DatabaseManager)
use HrTech\Database\DatabaseManager;
$db  = DatabaseManager::getInstance(__DIR__ . '/hrtech_db.sqlite');
$db->migrate();
$pdo = $db->getConnection();

// [Trilha de Execução - Passo 3: Negociação de Conteúdo & Cabeçalhos CORS]
// Permite que o frontend SPA consuma a API via chamadas assíncronas Fetch API
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Trata pre-flight requests do protocolo CORS para requisições cross-origin
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// [Trilha de Execução - Passo 4: Roteamento & Desserialização do Payload]
$method   = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';
$id       = $_GET['id'] ?? null;
$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$now      = date('Y-m-d\TH:i:sP'); // Carimbo de tempo oficial com fuso horário

/**
 * Responde a requisição HTTP com payload JSON formatado e encerra o ciclo.
 *
 * @param mixed $data Estrutura de dados (array, objeto ou escalar) a ser serializada
 * @param int $code Código de status HTTP (padrão 200 OK)
 * @return never
 */
function respond(mixed $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Responde com mensagem de erro padronizada em formato JSON.
 *
 * @param string $msg Descrição detalhada do erro ou violação de contrato
 * @param int $code Código de status HTTP de erro (padrão 400 Bad Request)
 * @return never
 */
function err(string $msg, int $code = 400): never {
    respond(['error' => $msg], $code);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function logApiQuery(string $sql, array $params = [], string $pattern = '', string $pathway = ''): void {
    if (!isset($_SESSION['sql_logs']) || !is_array($_SESSION['sql_logs'])) {
        $_SESSION['sql_logs'] = [];
    }
    $_SESSION['sql_logs'][] = [
        'time' => date('H:i:s'),
        'sql' => $sql,
        'params' => $params,
        'pattern' => $pattern,
        'pathway' => $pathway
    ];
    if (count($_SESSION['sql_logs']) > 50) {
        array_shift($_SESSION['sql_logs']);
    }
}

try {
    // Registra a trilha de execução da chamada REST no Console ao Vivo
    logApiQuery(
        "API REST: $method /api.php?resource=$resource" . ($id ? "&id=$id" : ""), 
        !empty($body) ? array_keys($body) : [], 
        "API REST (app.js ➔ api.php)", 
        "Frontend SPA (app.js / Fetch API) ➔ API REST (api.php?resource=$resource) ➔ Prepared Statement PDO ➔ SQLite: hrtech_db.sqlite"
    );

    // [Trilha de Execução - Passo 5: Despacho Polimórfico para os Controladores de Recursos]
    match ($resource) {
        'employees'   => handleEmployees($pdo, $method, $id, $body, $now),
        'tenants'     => handleTenants($pdo, $method, $id, $body, $now),
        'timelogs'    => handleTimeLogs($pdo, $method, $id, $body, $now),
        'vacations'   => handleVacations($pdo, $method, $id, $body, $now),
        'benefits'    => handleBenefits($pdo, $method, $id, $body, $now),
        'policies'    => handlePolicies($pdo, $method, $id, $body, $now),
        'adjustments' => handleAdjustments($pdo, $method, $id, $body, $now),
        'stats'       => handleStats($pdo),
        default       => err('Resource não encontrado na API', 404),
    };
} catch (Throwable $e) {
    err($e->getMessage(), 500);
}

// -----------------------------------------------------------------------------
// CONTROLADOR: COLABORADORES (CRUD 3 & CRUD 4) — Responsável: Andryus
// -----------------------------------------------------------------------------
/**
 * Gerencia o ciclo de vida dos colaboradores: Admissão, Consulta, Alteração Salarial e Desligamento.
 *
 * @param PDO $pdo Instância da conexão relacional ativa
 * @param string $method Verbo HTTP (GET, POST, PUT, DELETE)
 * @param string|null $id Identificador único do colaborador na rota
 * @param array $body Dados da requisição em formato JSON
 * @param string $now Data/hora do servidor em formato ISO 8601
 * @return never
 */
function handleEmployees(PDO $pdo, string $method, ?string $id, array $body, string $now): never {
    switch ($method) {
        case 'GET':
            // [Trilha de Execução - Leitura Relacional com Junções INNER/LEFT JOIN]
            // Conecta a tabela de colaboradores com Empresas (Tenants), Departamentos e Cargos
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

            // Converte centavos inteiros (armazenamento seguro) em Reais para exibição na UI
            foreach ($rows as &$row) {
                $row['base_salary'] = round(($row['base_salary_cents'] ?? 0) / 100, 2);
            }
            respond($rows);

        case 'POST':
            // [Trilha de Execução - Passo 1: Validação de Contrato dos Campos Obrigatórios]
            if (empty($body['full_name'])) err('Nome do colaborador é obrigatório');
            if (empty($body['cpf']))       err('CPF é obrigatório para cadastro');
            if (empty($body['email']))     err('E-mail institucional é obrigatório');
            if (empty($body['tenant_id'])) err('Identificador da empresa (tenant_id) é obrigatório');

            // [Trilha de Execução - Passo 2: Resolução Relacional de Chaves Estrangeiras (FKs)]
            // Garante a existência do Departamento e do Cargo, vinculando-os ao tenant
            $deptId = ensureDept($pdo, $body['tenant_id'], $body['department'] ?? 'Geral', $now);
            $roleId = ensureRole($pdo, $body['tenant_id'], $body['role_title'] ?? 'Colaborador', $now);

            // [Trilha de Execução - Passo 3: Encapsulamento Monetário em Centavos Inteiros]
            // Evita imprecisão de ponto flutuante binário (IEEE 754) em cálculos financeiros
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

            // [Trilha de Execução - Passo 5: Injeção Segura dos Parâmetros no Banco (30 dias de saldo inicial de férias)]
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
            // [Trilha de Execução - Atualização Atômica via UPDATE Prepared Statement]
            if (!$id) err('ID do colaborador é obrigatório para alteração');
            $deptId   = ensureDept($pdo, $body['tenant_id'] ?? '', $body['department'] ?? 'Geral', $now);
            $roleId   = ensureRole($pdo, $body['tenant_id'] ?? '', $body['role_title'] ?? 'Colaborador', $now);
            $salCents = (int)(((float)str_replace(',', '.', (string)($body['base_salary'] ?? '0'))) * 100);

            $pdo->prepare(
                "UPDATE employees 
                 SET full_name=?, email=?, employment_type=?, base_salary_cents=?, department_id=?, role_id=?, updated_at=? 
                 WHERE id=?"
            )->execute([
                $body['full_name'] ?? '',
                $body['email'] ?? '',
                $body['employment_type'] ?? 'CLT',
                $salCents,
                $deptId,
                $roleId,
                $now,
                $id
            ]);
            respond(['success' => true]);

        case 'DELETE':
            // [Trilha de Execução - Exclusão Lógica (Soft Delete) Preservando Histórico de Folha e Ponto]
            if (!$id) err('ID do colaborador é obrigatório para inativação');
            $pdo->prepare("UPDATE employees SET is_active = 0, updated_at = ? WHERE id = ?")->execute([$now, $id]);
            respond(['success' => true]);

        default:
            err('Método HTTP não permitido para este recurso', 405);
    }
}

/**
 * Localiza ou cria o departamento no escopo do tenant para garantir integridade referencial (FK).
 */
function ensureDept(PDO $pdo, string $tenantId, string $name, string $now): string {
    if (!$tenantId) return 'dept-default';
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE tenant_id = ? AND name = ? LIMIT 1");
    $stmt->execute([$tenantId, $name]);
    $row = $stmt->fetch();
    if ($row) return $row['id'];
    $newId = uniqid('dept-', true);
    $pdo->prepare("INSERT INTO departments (id, tenant_id, code, name, cost_center, is_active, created_at) VALUES (?,?,?,?,?,1,?)")
        ->execute([$newId, $tenantId, strtoupper(substr($name, 0, 4)) . '-' . rand(10, 99), $name, 'CC-' . rand(1000, 9999), $now]);
    return $newId;
}

/**
 * Localiza ou cria o cargo no escopo do tenant, definindo nível hierárquico padrão (CRUD 4).
 */
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

// -----------------------------------------------------------------------------
// CONTROLADOR: EMPRESAS E MULTI-TENANCY (CRUD 1 & CRUD 2) — Responsável: Fernando
// -----------------------------------------------------------------------------
/**
 * Gerencia as organizações clientes (Tenants), isolamento de dados e licenciamento da LPS.
 *
 * @param PDO $pdo Instância da conexão relacional ativa
 * @param string $method Verbo HTTP (GET, POST, DELETE)
 * @param string|null $id Identificador único do tenant
 * @param array $body Dados da requisição
 * @param string $now Data/hora em ISO 8601
 * @return never
 */
function handleTenants(PDO $pdo, string $method, ?string $id, array $body, string $now): never {
    switch ($method) {
        case 'GET':
            $rows = $pdo->query("SELECT * FROM tenants WHERE is_active = 1 ORDER BY created_at DESC")->fetchAll();
            respond($rows);

        case 'POST':
            // [Trilha de Execução - Passo 1: Validação de Contrato & Sanitização de CNPJ]
            if (empty($body['name'])) err('Razão social é obrigatória');
            if (empty($body['cnpj'])) err('CNPJ é obrigatório');
            $cleanCnpj = preg_replace('/\D/', '', $body['cnpj']);

            // [Trilha de Execução - Passo 2: Verificação de Unicidade no Escopo Global do Sistema]
            $check = $pdo->prepare("SELECT id FROM tenants WHERE cnpj = ?");
            $check->execute([$cleanCnpj]);
            if ($check->fetch()) err('CNPJ já cadastrado no sistema');

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
            if (!$id) err('ID da empresa é obrigatório para inativação');
            $pdo->prepare("UPDATE tenants SET is_active = 0, updated_at = ? WHERE id = ?")->execute([$now, $id]);
            respond(['success' => true]);

        default:
            err('Método HTTP não permitido para este recurso', 405);
    }
}

// -----------------------------------------------------------------------------
// CONTROLADOR: PONTO ELETRÔNICO PORTARIA 671 (CRUD 5) — Responsável: Felipe
// -----------------------------------------------------------------------------
/**
 * Registra batidas de ponto eletrônico com NSR sequencial e encadeamento criptográfico SHA-256.
 *
 * @param PDO $pdo Instância da conexão relacional ativa
 * @param string $method Verbo HTTP (GET, POST)
 * @param string|null $id Identificador da batida
 * @param array $body Dados da marcação
 * @param string $now Data/hora oficial
 * @return never
 */
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
            if (empty($body['employee_id'])) err('Identificador do colaborador é obrigatório');
            if (empty($body['tenant_id']))   err('Identificador da empresa é obrigatório');

            // [Trilha de Execução - Passo 2: Recuperação do NSR e Hash do Registro Anterior (Ledger Criptográfico)]
            $prevStmt = $pdo->prepare("SELECT signature_hash, nsr FROM time_logs WHERE employee_id = ? ORDER BY nsr DESC LIMIT 1");
            $prevStmt->execute([$body['employee_id']]);
            $prev = $prevStmt->fetch();
            $prevHash = $prev ? $prev['signature_hash'] : '';
            $nsr      = $prev ? ($prev['nsr'] + 1) : 1;

            // [Trilha de Execução - Passo 3: Geração da Assinatura Criptográfica SHA-256 (Portaria 671/MTE)]
            // O hash combina: ID do colaborador + Carimbo ISO + Tipo da batida + Hash anterior
            $logType = $body['log_type'] ?? 'ENTRY';
            $hash    = hash('sha256', $body['employee_id'] . $now . $logType . $prevHash);
            $newId   = uniqid('tl-', true);

            // [Trilha de Execução - Passo 4: Gravação Imutável no Banco de Dados (Tabela time_logs)]
            // Sob as normas da Portaria 671, registros de ponto nunca podem sofrer UPDATE ou DELETE
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
                'id' => $newId,
                'nsr' => $nsr,
                'type' => $logType,
                'timestamp' => $now,
                'signature_hash' => $hash,
                'employee_name'  => $body['employee_name'] ?? '',
            ], 201);

        default:
            err('Método HTTP não permitido para este recurso', 405);
    }
}

// -----------------------------------------------------------------------------
// CONTROLADOR: FÉRIAS E CONCESSÕES CLT (CRUD 7) — Responsável: Valentin
// -----------------------------------------------------------------------------
/**
 * Gerencia requerimentos de férias, aprovação gerencial e débito atômico de saldo.
 *
 * @param PDO $pdo Instância da conexão relacional ativa
 * @param string $method Verbo HTTP (GET, POST, PUT, DELETE)
 * @param string|null $id Identificador da solicitação
 * @param array $body Dados da solicitação
 * @param string $now Data/hora oficial
 * @return never
 */
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
            if (empty($body['employee_id'])) err('Colaborador é obrigatório');
            if (empty($body['start_date']))  err('Data de início é obrigatória');
            if (empty($body['end_date']))    err('Data de término é obrigatória');

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
            if (!$id) err('ID da solicitação de férias é obrigatório');
            $status = strtoupper($body['status'] ?? 'APPROVED');
            $pdo->prepare("UPDATE vacation_requests SET status = ?, approved_at = ? WHERE id = ?")
                ->execute([$status, $now, $id]);
            respond(['success' => true, 'status' => $status]);

        case 'DELETE':
            if (!$id) err('ID da solicitação de férias é obrigatório');
            $pdo->prepare("DELETE FROM vacation_requests WHERE id = ?")->execute([$id]);
            respond(['success' => true]);

        default:
            err('Método HTTP não permitido para este recurso', 405);
    }
}

// -----------------------------------------------------------------------------
// CONTROLADOR: BENEFÍCIOS CORPORATIVOS & STRATEGY (CRUD 8) — Responsável: Valentin
// -----------------------------------------------------------------------------
/**
 * Gerencia o catálogo de benefícios e a atribuição de pacotes aos colaboradores.
 *
 * @param PDO $pdo Instância da conexão relacional ativa
 * @param string $method Verbo HTTP (GET, POST)
 * @param string|null $id Identificador do benefício
 * @param array $body Dados do benefício
 * @param string $now Data/hora oficial
 * @return never
 */
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
            // [Trilha de Execução - Passo 1: Validação e Conversão Monetária do Pacote]
            if (empty($body['provider'])) err('Operadora/Fornecedora do benefício é obrigatória');
            $newId    = uniqid('ben-', true);
            $valCents = (int)(((float)($body['monthly_value'] ?? 0)) * 100);

            // [Trilha de Execução - Passo 2: Persistência Relacional com Percentual de Coparticipação]
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

        case 'PUT':
            if (!$id) err('ID do benefício é obrigatório');
            $valCents = (int)(((float)($body['monthly_value'] ?? 0)) * 100);
            $pdo->prepare("UPDATE benefits SET name = ?, provider = ?, value_cents = ?, employee_cost_share_percentage = ? WHERE id = ?")
                ->execute([
                    $body['name'] ?? $body['provider'],
                    $body['provider'] ?? '',
                    $valCents,
                    (float)($body['copayment_pct'] ?? 0),
                    $id
                ]);
            respond(['success' => true]);

        case 'DELETE':
            if (!$id) err('ID do benefício é obrigatório');
            $pdo->prepare("DELETE FROM benefits WHERE id = ?")->execute([$id]);
            respond(['success' => true]);

        default:
            err('Método HTTP não permitido para este recurso', 405);
    }
}

// -----------------------------------------------------------------------------
// CONTROLADOR: APÓLICES DE SEGURO FINCORP (CRUD 10) — Responsável: Nicholas
// -----------------------------------------------------------------------------
/**
 * Gerencia a emissão, consulta, cancelamento e exclusão de apólices corporativas do Portal do Corretor.
 *
 * @param PDO $pdo Instância da conexão relacional ativa
 * @param string $method Verbo HTTP (GET, POST, PUT, DELETE)
 * @param string|null $id Identificador da apólice
 * @param array $body Dados da apólice
 * @param string $now Data/hora oficial
 * @return never
 */
function handlePolicies(PDO $pdo, string $method, ?string $id, array $body, string $now): never {
    switch ($method) {
        case 'GET':
            $rows = $pdo->query(
                "SELECT p.*, e.full_name as employee_name, t.corporate_name as tenant_name
                 FROM insurance_policies p
                 LEFT JOIN employees e ON p.employee_id = e.id
                 LEFT JOIN tenants t ON p.tenant_id = t.id
                 ORDER BY p.start_date DESC"
            )->fetchAll();
            foreach ($rows as &$row) {
                $row['insured_capital'] = round(($row['insured_capital_cents'] ?? 0) / 100, 2);
                $row['monthly_premium'] = round(($row['monthly_premium_cents'] ?? 0) / 100, 2);
            }
            respond($rows);

        case 'POST':
            if (empty($body['employee_id'])) err('Colaborador é obrigatório');
            if (empty($body['policy_number'])) err('Número da apólice é obrigatório');
            $newId = uniqid('pol-', true);
            $capitalCents = (int)(((float)($body['insured_capital'] ?? 100000)) * 100);
            $premiumCents = (int)(((float)($body['monthly_premium'] ?? 100)) * 100);

            $pdo->prepare(
                "INSERT INTO insurance_policies
                    (id, tenant_id, policy_number, broker_code, insurer_name, employee_id,
                     insured_capital_cents, monthly_premium_cents, status, start_date, end_date, coverage_details)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([
                $newId,
                $body['tenant_id'] ?? 'tenant-fin',
                $body['policy_number'],
                $body['broker_code'] ?? 'CORR-FINCORP-01',
                $body['insurer_name'] ?? 'FinCorp Seguros S.A.',
                $body['employee_id'],
                $capitalCents,
                $premiumCents,
                $body['status'] ?? 'ACTIVE',
                $body['start_date'] ?? date('Y-m-d'),
                $body['end_date'] ?? date('Y-m-d', strtotime('+1 year')),
                json_encode($body['coverage_details'] ?? ['morte_acidental' => true, 'invalidez' => true])
            ]);
            respond(['id' => $newId, 'status' => 'ACTIVE', 'policy_number' => $body['policy_number']], 201);

        case 'PUT':
            if (!$id) err('ID da apólice é obrigatório');
            $status = strtoupper($body['status'] ?? 'ACTIVE');
            $reason = $body['cancellation_reason'] ?? 'Cancelamento administrativo';
            $pdo->prepare("UPDATE insurance_policies SET status = ?, cancellation_reason = ?, cancelled_at = ? WHERE id = ?")
                ->execute([$status, $reason, $status === 'CANCELLED' ? $now : null, $id]);
            respond(['success' => true, 'status' => $status]);

        case 'DELETE':
            if (!$id) err('ID da apólice é obrigatório');
            $pdo->prepare("DELETE FROM insurance_policies WHERE id = ?")->execute([$id]);
            respond(['success' => true]);

        default:
            err('Método HTTP não permitido para este recurso', 405);
    }
}

// -----------------------------------------------------------------------------
// CONTROLADOR: AJUSTES DE PONTO ELETRÔNICO (CRUD 6) — Responsável: Felipe
// -----------------------------------------------------------------------------
/**
 * Registra pedidos de abono ou correção de batida de ponto para aprovação da chefia.
 *
 * @param PDO $pdo Instância da conexão relacional ativa
 * @param string $method Verbo HTTP (GET, POST, PUT)
 * @param string|null $id Identificador do ajuste
 * @param array $body Dados da solicitação de ajuste
 * @param string $now Data/hora oficial
 * @return never
 */
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
            if (empty($body['employee_id'])) err('Identificador do colaborador é obrigatório');
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
                $body['reason'] ?? 'Ajuste solicitado pelo colaborador',
                'PENDING',
                $now,
            ]);
            respond(['id' => $newId, 'status' => 'PENDING'], 201);

        case 'PUT':
            // [Trilha de Execução - Passo 2: Aprovação do Ajuste pelo Supervisor]
            if (!$id) err('ID do ajuste é obrigatório');
            $status = strtoupper($body['status'] ?? 'APPROVED');
            $pdo->prepare("UPDATE time_adjustment_requests SET status = ?, approved_at = ? WHERE id = ?")
                ->execute([$status, $now, $id]);
            respond(['success' => true, 'status' => $status]);

        default:
            err('Método HTTP não permitido para este recurso', 405);
    }
}

// -----------------------------------------------------------------------------
// CONTROLADOR: MÉTRICAS CONSOLIDADAS DO DASHBOARD (Indicadores Operacionais)
// -----------------------------------------------------------------------------
/**
 * Agrega contadores de colaboradores ativos, batidas, férias e solicitações pendentes.
 *
 * @param PDO $pdo Instância da conexão relacional ativa
 * @return never
 */
function handleStats(PDO $pdo): never {
    respond([
        'tenants'             => (int)$pdo->query("SELECT COUNT(*) FROM tenants WHERE is_active = 1")->fetchColumn(),
        'employees'           => (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn(),
        'timelogs'            => (int)$pdo->query("SELECT COUNT(*) FROM time_logs")->fetchColumn(),
        'vacations_pending'   => (int)$pdo->query("SELECT COUNT(*) FROM vacation_requests WHERE status = 'REQUESTED'")->fetchColumn(),
        'benefits'            => (int)$pdo->query("SELECT COUNT(*) FROM benefits")->fetchColumn(),
        'adjustments_pending' => (int)$pdo->query("SELECT COUNT(*) FROM time_adjustment_requests WHERE status = 'PENDING'")->fetchColumn(),
    ]);
}
