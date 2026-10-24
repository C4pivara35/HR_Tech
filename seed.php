<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * HRTech Core — Seeder Central do Banco de Dados Relacional (SQLite3)
 * ==============================================================================
 * Disciplina: Medição e Análise de Software / Engenharia de Software (PUCPR)
 * Cobre com fidelidade absoluta as 17 tabelas relacionais do sistema e as
 * 50 Funções Elementares de APF (IFPUG CPM 4.3.1 / NESMA) para os 5 integrantes:
 *
 * 1. Andryus Zolet (53 PFB):
 *    - ALI: employees, departments, roles
 *    - AIE: AIE_RECEITA_FEDERAL_CPF, AIE_ESOCIAL_S2200
 *    - EE: EE_CADASTRAR_COLABORADOR, EE_CRIAR_DEPARTAMENTO_CARGO
 *    - SE: SE_EMITIR_HOLERITE_MENSAL, SE_RECIBO_PROVISAO_13_FERIAS
 *    - CE: CE_BUSCAR_COLABORADORES, CE_CONSULTAR_FICHA_FINANCEIRA
 *
 * 2. Felipe (50 PFB):
 *    - ALI: time_logs, overtime_entries
 *    - AIE: AIE_RELOGIO_REP_671, AIE_NTP_HORA_LEGAL
 *    - EE: EE_BATER_PONTO_671, EE_AJUSTE_ABONO_PONTO
 *    - SE: SE_EXTRATO_ESPELHO_PONTO, SE_RELATORIO_HORAS_EXTRAS
 *    - CE: CE_CONSULTAR_BATIDAS_DIA, CE_CONSULTAR_BANCO_HORAS
 *
 * 3. Valentin (50 PFB):
 *    - ALI: vacation_requests, benefits, employee_benefits
 *    - AIE: AIE_OPERADORA_BENEFICIOS, AIE_OPERADORA_SAUDE_ANS
 *    - EE: EE_SOLICITAR_FERIAS, EE_VINCULAR_BENEFICIO
 *    - SE: SE_CALCULO_RECIBO_FERIAS, SE_DEMONSTRATIVO_BENEFICIOS
 *    - CE: CE_CONSULTAR_SALDO_FERIAS, CE_CONSULTAR_BENEFICIOS_ATIVOS
 *
 * 4. Nicolas (46 PFB):
 *    - ALI: equipment_aso (EPIs e ASOs)
 *    - AIE: AIE_CATALOGO_CAEPI_MTE, AIE_CLINICA_MEDICINA_OCUP
 *    - EE: EE_REGISTRAR_ENTREGA_EPI, EE_REGISTRAR_EXAME_ASO
 *    - SE: SE_PAINEL_ALERTAS_NR6_NR7, SE_BLOQUEIO_CHAO_FABRICA, SE_RELATORIO_PPP_ELETRONICO
 *    - CE: CE_CONSULTAR_ELEGIBILIDADE_FABRIL, CE_CONSULTAR_HISTORICO_CLINICO
 *
 * 5. Fernando Lopes Duarte (53 PFB):
 *    - ALI: audit_logs, tenants, insurance_policies
 *    - AIE: AIE_FINCORP_SEGURADORA, AIE_PIX_BACEN_GATEWAY
 *    - EE: EE_EMITIR_APOLICE_SEGURO, EE_REGISTRAR_EVENTO_AUDITORIA
 *    - SE: SE_RELATORIO_SINISTRALIDADE, SE_EXPORTAR_TRILHA_AUDITORIA
 *    - CE: CE_CONSULTAR_APOLICE_VIGENTE, CE_CONSULTAR_TRILHA_AUDITORIA
 * ==============================================================================
 */

define('BACKEND_BASE', __DIR__ . '/hrtech_backend_patterns');
require_once BACKEND_BASE . '/src/Autoloader.php';

use HrTech\Autoloader;
$loader = new Autoloader();
$loader->addNamespace('HrTech', BACKEND_BASE . '/src');
$loader->addNamespace('App', __DIR__ . '/app');
$loader->register();

use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\AuditLog;
use HrTech\Patterns\Singleton\AuditLogger;

$dbPath = __DIR__ . '/hrtech_db.sqlite';
$dbManager = DatabaseManager::getInstance($dbPath);
$dbManager->migrate();
$pdo = $dbManager->getConnection();

echo "=================================================================\n";
echo "HRTech Core — Iniciando Seeding Completo de 17 Tabelas Relacionais\n";
echo "=================================================================\n";

// ── 0. LIMPEZA TOTAL DE DADOS EXISTENTES (RESET ACID) ──────────
$pdo->exec('PRAGMA foreign_keys = OFF;');
$allTables = [
    'pix_transactions',
    'external_integrations',
    'rep_imports',
    'employee_benefits',
    'overtime_entries',
    'audit_logs',
    'insurance_policies',
    'equipment_aso',
    'benefits',
    'vacation_requests',
    'time_adjustment_requests',
    'time_logs',
    'users',
    'employees',
    'roles',
    'departments',
    'tenants'
];

foreach ($allTables as $table) {
    $pdo->exec("DELETE FROM {$table};");
}
$pdo->exec('PRAGMA foreign_keys = ON;');
echo "✔ Limpeza concluída: 17 tabelas truncadas com integridade referencial.\n";

$now = date('Y-m-d\TH:i:sP');

// ── 1. TENANTS (Fernando Lopes Duarte: ALI_TENANTS_POLICIES) ────
$tenants = [
    ['tenant-tech', '11222333000181', 'TechStart Inovações em Software Ltda', 'TechStart', 'tech'],
    ['tenant-ind',  '22333444000192', 'Metalúrgica Sul Industrial S.A.',       'MetalSul',  'industry'],
    ['tenant-fin',  '33444555000103', 'FinCorp Investimentos & Seguros S.A.',  'FinCorp',   'financial'],
    ['tenant-omni', '44555666000114', 'OmniCorp Saúde & Serviços Gerais Ltda', 'OmniCorp',  'tech'],
];

$stmtTenant = $pdo->prepare(
    "INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)"
);
foreach ($tenants as [$id, $cnpj, $corp, $trad, $seg]) {
    $licenses = json_encode(['time_tracking', 'payroll', 'benefits', 'insurance', 'safety', 'audit_ledger']);
    $stmtTenant->execute([$id, $cnpj, $corp, $trad, $seg, $licenses, $now, $now]);
}
echo "✔ 1/17: tenants (4 registros)\n";

// ── 2. DEPARTMENTS (Andryus Zolet: ALI_DEPARTAMENTOS_CARGOS) ───
$departments = [
    ['dept-ti',  'tenant-tech', 'TI-01',  'Tecnologia & Engenharia',   'CC-1000', null, null],
    ['dept-rh',  'tenant-tech', 'RH-01',  'Recursos Humanos & D.P.',   'CC-1100', null, null],
    ['dept-ops', 'tenant-ind',  'OP-01',  'Operações Industriais',     'CC-2000', null, null],
    ['dept-sst', 'tenant-ind',  'SST-01', 'Saúde e Segurança (SST)',   'CC-2100', null, null],
    ['dept-fin', 'tenant-fin',  'FI-01',  'Controladoria & Finanças',   'CC-3000', null, null],
    ['dept-seg', 'tenant-fin',  'SEG-01', 'Corretagem & Seguros Vida',  'CC-3100', null, null],
];

$stmtDept = $pdo->prepare(
    "INSERT INTO departments (id, tenant_id, code, name, cost_center, manager_id, parent_department_id, is_active, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)"
);
foreach ($departments as [$id, $tid, $code, $name, $cc, $mgr, $parent]) {
    $stmtDept->execute([$id, $tid, $code, $name, $cc, $mgr, $parent, $now, $now]);
}
echo "✔ 2/17: departments (6 registros)\n";

// ── 3. ROLES (Andryus Zolet: ALI_DEPARTAMENTOS_CARGOS) ─────────
$roles = [
    ['role-dir-rh',      'tenant-tech', 'Diretora de RH & People',           90, 'dept-rh'],
    ['role-analista-dp', 'tenant-tech', 'Analista de Departamento Pessoal',  40, 'dept-rh'],
    ['role-rec',         'tenant-tech', 'Recrutadora & Talent Acquisition',  40, 'dept-rh'],
    ['role-ger-eng',     'tenant-tech', 'Gerente de Engenharia de Software', 70, 'dept-ti'],
    ['role-dev-sr',      'tenant-tech', 'Desenvolvedor Senior Fullstack',    50, 'dept-ti'],
    ['role-estag-ti',    'tenant-tech', 'Estagiário de Engenharia de TI',    15, 'dept-ti'],
    ['role-lider-ind',   'tenant-ind',  'Líder de Produção Industrial',      60, 'dept-ops'],
    ['role-op-torno',    'tenant-ind',  'Operador de Torno Mecânico CNC',    35, 'dept-ops'],
    ['role-tec-sst',     'tenant-ind',  'Técnico de Segurança do Trabalho',  45, 'dept-sst'],
    ['role-analista-cmp','tenant-fin',  'Analista Sênior de Compliance LGPD',50, 'dept-fin'],
    ['role-corretor-sr', 'tenant-fin',  'Corretora Sênior de Seguros Vida',  55, 'dept-seg'],
    ['role-consultor-pj','tenant-fin',  'Consultor Atuarial Estratégico PJ', 65, 'dept-seg'],
];

$stmtRole = $pdo->prepare(
    "INSERT INTO roles (id, tenant_id, name, description, hierarchy_level, permissions, department_id, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($roles as [$id, $tid, $name, $level, $deptId]) {
    $perms = json_encode(['access.view', 'report.generate', 'record.edit']);
    $stmtRole->execute([$id, $tid, $name, $name, $level, $perms, $deptId, $now, $now]);
}
echo "✔ 3/17: roles (12 registros)\n";

// ── 4. EMPLOYEES (Andryus Zolet: ALI_COLABORADORES) ────────────
// Todos os CPFs abaixo são matematicamente válidos perante o Módulo 11 da Receita Federal
$employees = [
    // [id, tenant, matricula, cpf, name, email, phone, birth, admit, dept, role, sal_cents, type, vac_days, bank_min]
    ['emp-mariana', 'tenant-tech', 'MAT-1001', '011.111.111-03', 'Mariana Santos',       'mariana.santos@techstart.com.br', '(41)99111-1001', '1985-03-15', '2020-01-10', 'dept-rh',  'role-dir-rh',      1850000, 'CLT', 30, 480],
    ['emp-roberto', 'tenant-tech', 'MAT-1002', '022.222.222-06', 'Roberto Lima',         'roberto.lima@techstart.com.br',   '(41)99222-1002', '1990-07-22', '2021-03-15', 'dept-rh',  'role-analista-dp',  650000, 'CLT', 25, 240],
    ['emp-ana',     'tenant-tech', 'MAT-1003', '033.333.333-09', 'Ana Souza',            'ana.souza@techstart.com.br',      '(41)99333-1003', '1993-09-30', '2023-01-09', 'dept-rh',  'role-rec',          720000, 'CLT', 30, 120],
    ['emp-carlos',  'tenant-tech', 'MAT-1004', '044.444.444-01', 'Carlos Eduardo Silva', 'carlos.eduardo@techstart.com.br', '(41)99444-1004', '1988-11-05', '2019-06-01', 'dept-ti',  'role-ger-eng',     1350000, 'CLT', 20, 600],
    ['emp-lucas',   'tenant-tech', 'MAT-1005', '055.555.555-04', 'Lucas Ferreira Silva', 'lucas.silva@techstart.com.br',    '(41)99555-1005', '1995-04-18', '2022-02-14', 'dept-ti',  'role-dev-sr',       980000, 'CLT', 15, 840],
    ['emp-felipe',  'tenant-tech', 'MAT-1006', '066.666.666-07', 'Felipe Ramos',         'felipe.ramos@techstart.com.br',   '(41)99666-1006', '2004-05-12', '2024-02-01', 'dept-ti',  'role-estag-ti',     180000, 'ESTAGIO', 30, 0],
    ['emp-joao',    'tenant-ind',  'MAT-2001', '077.777.777-00', 'João Oliveira Santos', 'joao.oliveira@metalsul.com.br',   '(41)99777-2001', '1982-12-20', '2015-08-01', 'dept-ops', 'role-lider-ind',   780000, 'CLT', 30, 420],
    ['emp-marcos',  'tenant-ind',  'MAT-2002', '088.888.888-02', 'Marcos Paulo Lima',    'marcos.paulo@metalsul.com.br',    '(41)99888-2002', '1994-08-14', '2022-11-01', 'dept-ops', 'role-op-torno',    420000, 'CLT', 30, -180],
    ['emp-valeria', 'tenant-ind',  'MAT-2003', '099.999.999-05', 'Valéria Cristina Dias','valeria.dias@metalsul.com.br',    '(41)99999-2003', '1989-02-18', '2020-04-15', 'dept-sst', 'role-tec-sst',     690000, 'CLT', 30, 150],
    ['emp-gabriel', 'tenant-fin',  'MAT-3001', '111.111.110-30', 'Gabriel Torres Lima',  'gabriel.torres@fincorp.com.br',   '(41)99111-3001', '1991-06-11', '2020-09-01', 'dept-fin', 'role-analista-cmp', 890000, 'CLT', 22, 300],
    ['emp-helena',  'tenant-fin',  'MAT-3002', '122.222.221-33', 'Helena Martins Dias',  'helena.martins@fincorp.com.br',   '(41)99222-3002', '1987-02-28', '2018-04-15', 'dept-seg', 'role-corretor-sr', 1120000, 'CLT', 30, 600],
    ['emp-arthur',  'tenant-fin',  'MAT-3003', '133.333.332-36', 'Arthur Castro Nogueira','arthur.castro@fincorp.com.br',   '(41)99333-3003', '1980-10-05', '2021-07-01', 'dept-seg', 'role-consultor-pj',1600000, 'PJ', 30, 0],
];

$stmtEmp = $pdo->prepare(
    "INSERT INTO employees 
     (id, tenant_id, matricula, cpf, full_name, email, phone, birth_date, admission_date, department_id, role_id, base_salary_cents, employment_type, is_active, vacation_days_balance, bank_hours_balance, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)"
);
foreach ($employees as [$id, $tid, $mat, $cpf, $name, $email, $phone, $birth, $admit, $dept, $role, $sal, $type, $vac, $bank]) {
    $stmtEmp->execute([$id, $tid, $mat, $cpf, $name, $email, $phone, $birth, $admit, $dept, $role, $sal, $type, $vac, $bank, $now, $now]);
}
echo "✔ 4/17: employees (12 registros ativos)\n";

// ── 5. USERS (Autenticação e Auditoria Multi-Tenant) ───────────
$users = [
    ['usr-admin',   'tenant-tech', 'admin',          'admin@hrtech.com.br',          'admin',      null],
    ['usr-mariana', 'tenant-tech', 'mariana.santos', 'mariana.santos@techstart.com.br', 'manager', 'emp-mariana'],
    ['usr-lucas',   'tenant-tech', 'lucas.silva',    'lucas.silva@techstart.com.br',    'employee','emp-lucas'],
    ['usr-joao',    'tenant-ind',  'joao.oliveira',  'joao.oliveira@metalsul.com.br',   'supervisor','emp-joao'],
    ['usr-gabriel', 'tenant-fin',  'gabriel.torres', 'gabriel.torres@fincorp.com.br',   'auditor', 'emp-gabriel'],
    ['usr-helena',  'tenant-fin',  'helena.martins', 'helena.martins@fincorp.com.br',   'broker',  'emp-helena'],
];

$pwdHash = password_hash('HRTech@2026', PASSWORD_BCRYPT);
$stmtUser = $pdo->prepare(
    "INSERT INTO users (id, tenant_id, username, email, password_hash, role, is_active, mfa_enabled, employee_id, created_at)
     VALUES (?, ?, ?, ?, ?, ?, 1, 0, ?, ?)"
);
foreach ($users as [$uid, $tid, $uname, $uemail, $urole, $empId]) {
    $stmtUser->execute([$uid, $tid, $uname, $uemail, $pwdHash, $urole, $empId, $now]);
}
echo "✔ 5/17: users (6 contas configuradas)\n";

// ── 6. TIME_LOGS (Felipe: ALI_ESPELHO_PONTO - Portaria 671 MTE) 
$logBatidas = [
    // Lucas Silva: jornada padrão diurna (08:00 às 18:00 com 1h intervalo)
    ['emp-lucas', '2026-09-18T08:02:15-03:00', 'ENTRY'],
    ['emp-lucas', '2026-09-18T12:01:40-03:00', 'INTERVAL_START'],
    ['emp-lucas', '2026-09-18T13:03:10-03:00', 'INTERVAL_END'],
    ['emp-lucas', '2026-09-18T18:05:22-03:00', 'EXIT'],
    ['emp-lucas', '2026-09-19T07:58:10-03:00', 'ENTRY'],
    ['emp-lucas', '2026-09-19T12:02:30-03:00', 'INTERVAL_START'],
    ['emp-lucas', '2026-09-19T13:00:15-03:00', 'INTERVAL_END'],
    ['emp-lucas', '2026-09-19T18:35:40-03:00', 'EXIT'], // 30 min HE 50%
    // Mariana Santos: jornada padrão
    ['emp-mariana', '2026-09-18T08:55:00-03:00', 'ENTRY'],
    ['emp-mariana', '2026-09-18T12:10:00-03:00', 'INTERVAL_START'],
    ['emp-mariana', '2026-09-18T13:05:00-03:00', 'INTERVAL_END'],
    ['emp-mariana', '2026-09-18T18:00:00-03:00', 'EXIT'],
    // João Oliveira: turno fabril matutino
    ['emp-joao', '2026-09-18T06:00:10-03:00', 'ENTRY'],
    ['emp-joao', '2026-09-18T11:30:00-03:00', 'INTERVAL_START'],
    ['emp-joao', '2026-09-18T12:30:00-03:00', 'INTERVAL_END'],
    ['emp-joao', '2026-09-18T15:20:00-03:00', 'EXIT'],
    // Marcos Paulo: turno NOTURNO para testar a redução de 52m30s e adicional 20%
    ['emp-marcos', '2026-09-18T22:00:00-03:00', 'ENTRY'],
    ['emp-marcos', '2026-09-19T01:30:00-03:00', 'INTERVAL_START'],
    ['emp-marcos', '2026-09-19T02:30:00-03:00', 'INTERVAL_END'],
    ['emp-marcos', '2026-09-19T05:00:00-03:00', 'EXIT'],
    // Gabriel Torres: compliance fin
    ['emp-gabriel', '2026-09-18T09:00:00-03:00', 'ENTRY'],
    ['emp-gabriel', '2026-09-18T12:00:00-03:00', 'INTERVAL_START'],
    ['emp-gabriel', '2026-09-18T13:00:00-03:00', 'INTERVAL_END'],
    ['emp-gabriel', '2026-09-18T18:00:00-03:00', 'EXIT'],
];

$stmtPunch = $pdo->prepare(
    "INSERT INTO time_logs (id, tenant_id, employee_id, timestamp, type, latitude, longitude, accuracy, nsr, previous_hash, signature_hash)
     VALUES (?, ?, ?, ?, ?, ?, ?, 10.0, ?, ?, ?)"
);

$nsrCounter = 0;
$prevHashGlobal = str_repeat('0', 64);

foreach ($logBatidas as $idx => [$empId, $ts, $type]) {
    $nsrCounter++;
    $tidStmt = $pdo->prepare("SELECT tenant_id, cpf FROM employees WHERE id = ?");
    $tidStmt->execute([$empId]);
    $empRow = $tidStmt->fetch();
    $tid = $empRow['tenant_id'];
    $cpf = $empRow['cpf'];

    $hashPayload = $prevHashGlobal . '|' . $nsrCounter . '|' . $cpf . '|' . $ts . '|' . $type;
    $currentHash = hash('sha256', $hashPayload);
    $logId = 'tl-' . str_pad((string)$nsrCounter, 4, '0', STR_PAD_LEFT);

    $stmtPunch->execute([
        $logId,
        $tid,
        $empId,
        $ts,
        $type,
        -25.4284,
        -49.2733,
        $nsrCounter,
        $prevHashGlobal,
        $currentHash
    ]);

    $prevHashGlobal = $currentHash;
}
echo "✔ 6/17: time_logs (" . count($logBatidas) . " batidas encadeadas SHA-256 / Portaria 671)\n";

// ── 7. TIME_ADJUSTMENT_REQUESTS (Felipe: EE_AJUSTE_ABONO_PONTO) 
$adjustments = [
    ['adj-01', 'tenant-tech', 'emp-lucas',  '2026-09-17', '18:00', '18:45', 'Reunião extraordinária de alinhamento com cliente', 'APPROVED', 'usr-mariana'],
    ['adj-02', 'tenant-tech', 'emp-ana',    '2026-09-16', '08:00', '09:30', 'Atraso decorrente de consulta médica odontológica', 'APPROVED', 'usr-mariana'],
    ['adj-03', 'tenant-ind',  'emp-marcos', '2026-09-15', '22:00', '22:30', 'Problema mecânico no transporte público municipal', 'PENDING',  null],
    ['adj-04', 'tenant-fin',  'emp-gabriel','2026-09-14', '09:00', '10:00', 'Audiência de conciliação trabalhista presencial',   'REJECTED', 'usr-admin'],
];

$stmtAdj = $pdo->prepare(
    "INSERT INTO time_adjustment_requests (id, tenant_id, employee_id, requested_date, original_time, requested_time, reason, status, approver_id, approved_at, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($adjustments as [$aid, $tid, $empId, $reqDate, $origTime, $reqTime, $reason, $st, $appr]) {
    $approvedAt = ($st === 'APPROVED') ? $now : null;
    $stmtAdj->execute([$aid, $tid, $empId, $reqDate, $origTime, $reqTime, $reason, $st, $appr, $approvedAt, $now]);
}
echo "✔ 7/17: time_adjustment_requests (4 solicitações)\n";

// ── 8. VACATION_REQUESTS (Valentin: ALI_FERIAS_CONCESSOES) ─────
$vacations = [
    // [id, tenant, emp, start, end, days, abono, 13o, abono_days, acq_ini, acq_fim, status]
    ['vac-01', 'tenant-tech', 'emp-lucas',   '2026-10-06', '2026-10-25', 20, 1, 1, 10, '2025-02-14', '2026-02-13', 'APPROVED'],
    ['vac-02', 'tenant-tech', 'emp-roberto', '2026-11-01', '2026-11-30', 30, 0, 0, 0,  '2024-03-15', '2025-03-14', 'APPROVED'],
    ['vac-03', 'tenant-ind',  'emp-joao',    '2026-12-15', '2026-12-29', 15, 0, 1, 0,  '2024-08-01', '2025-07-31', 'REQUESTED'],
    ['vac-04', 'tenant-ind',  'emp-marcos',  '2027-01-10', '2027-01-29', 20, 1, 0, 10, '2024-11-01', '2025-10-31', 'REQUESTED'],
    ['vac-05', 'tenant-fin',  'emp-helena',  '2026-07-01', '2026-07-15', 15, 0, 0, 0,  '2024-04-15', '2025-04-14', 'COMPLETED'],
];

$stmtVac = $pdo->prepare(
    "INSERT INTO vacation_requests (id, tenant_id, employee_id, start_date, end_date, duration_days, abono_pecuniario, advance_thirteenth_salary, abono_days, periodo_aquisitivo_inicio, periodo_aquisitivo_fim, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($vacations as [$vid, $tid, $empId, $start, $end, $days, $abono, $adv, $abonoDays, $acqIni, $acqFim, $st]) {
    $stmtVac->execute([$vid, $tid, $empId, $start, $end, $days, $abono, $adv, $abonoDays, $acqIni, $acqFim, $st, $now]);
}
echo "✔ 8/17: vacation_requests (5 concessões de férias)\n";

// ── 9. BENEFITS (Valentin: ALI_BENEFICIOS_CONTRATOS) ───────────
$benefitsCatalog = [
    ['ben-01', 'tenant-tech', 'MEAL_VOUCHER',    'Vale Refeição Flex Caju',         'Caju Benefícios',       95000, 0.0, 1],
    ['ben-02', 'tenant-tech', 'HEALTH_PLAN',     'Plano de Saúde Bradesco Top',     'Bradesco Saúde',        52000, 20.0, 1],
    ['ben-03', 'tenant-tech', 'TRANSPORT',       'Vale Transporte Urbano URBS',     'URBS / Curitiba',       22000, 6.0, 1],
    ['ben-04', 'tenant-ind',  'MEAL_VOUCHER',    'Vale Refeição & Alimentação Ticket','Ticket Edenred',       75000, 5.0, 1],
    ['ben-05', 'tenant-ind',  'HEALTH_PLAN',     'Plano Médico Hospitalar Unimed',  'Unimed Curitiba',       38000, 15.0, 1],
    ['ben-06', 'tenant-fin',  'HEALTH_PLAN',     'Seguro Saúde Executivo SulAmérica','SulAmérica Seguros',   125000, 10.0, 1],
    ['ben-07', 'tenant-fin',  'LIFE_INSURANCE',  'Previdência e Seguro Vida Grupo', 'FinCorp Seguros',       25000, 0.0, 1],
];

$stmtBen = $pdo->prepare(
    "INSERT INTO benefits (id, tenant_id, type, name, provider, value_cents, employee_cost_share_percentage, is_deductible)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($benefitsCatalog as [$bid, $tid, $btype, $bname, $bprov, $bval, $bshare, $bded]) {
    $stmtBen->execute([$bid, $tid, $btype, $bname, $bprov, $bval, $bshare, $bded]);
}
echo "✔ 9/17: benefits (7 pacotes no catálogo)\n";

// ── 10. EMPLOYEE_BENEFITS (Valentin: EE_VINCULAR_BENEFICIO) ────
$employeeBenefits = [
    ['eb-01', 'tenant-tech', 'emp-lucas',   'ben-01', '2022-02-14', 'ACTIVE', 0.0],
    ['eb-02', 'tenant-tech', 'emp-lucas',   'ben-02', '2022-02-14', 'ACTIVE', 20.0],
    ['eb-03', 'tenant-tech', 'emp-lucas',   'ben-03', '2022-02-14', 'ACTIVE', 6.0],
    ['eb-04', 'tenant-tech', 'emp-mariana', 'ben-01', '2020-01-10', 'ACTIVE', 0.0],
    ['eb-05', 'tenant-tech', 'emp-mariana', 'ben-02', '2020-01-10', 'ACTIVE', 10.0],
    ['eb-06', 'tenant-tech', 'emp-roberto', 'ben-01', '2021-03-15', 'ACTIVE', 0.0],
    ['eb-07', 'tenant-tech', 'emp-roberto', 'ben-03', '2021-03-15', 'ACTIVE', 6.0],
    ['eb-08', 'tenant-ind',  'emp-joao',    'ben-04', '2015-08-01', 'ACTIVE', 5.0],
    ['eb-09', 'tenant-ind',  'emp-joao',    'ben-05', '2015-08-01', 'ACTIVE', 15.0],
    ['eb-10', 'tenant-ind',  'emp-marcos',  'ben-04', '2022-11-01', 'ACTIVE', 5.0],
    ['eb-11', 'tenant-fin',  'emp-helena',  'ben-06', '2018-04-15', 'ACTIVE', 10.0],
    ['eb-12', 'tenant-fin',  'emp-gabriel', 'ben-06', '2020-09-01', 'ACTIVE', 10.0],
];

$stmtEb = $pdo->prepare(
    "INSERT INTO employee_benefits (id, tenant_id, employee_id, benefit_id, enrolled_at, status, custom_cost_share, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($employeeBenefits as [$ebid, $tid, $empId, $benId, $enrolled, $st, $share]) {
    $stmtEb->execute([$ebid, $tid, $empId, $benId, $enrolled, $st, $share, $now]);
}
echo "✔ 10/17: employee_benefits (12 vínculos individuais)\n";

// ── 11. EQUIPMENT_ASO (Nicolas: ALI_EPI_HISTORICO & ALI_ASO_HISTORICO)
$safetyRecords = [
    // EPIs entregues:
    ['epi-01', 'tenant-ind', 'emp-joao', 'Capacete de Segurança Tipo Aba Frontal Classe B', '31469', '2028-11-20', '2026-01-10', null, 'PERIODIC', null, '2028-11-20', null, null, 1],
    ['epi-02', 'tenant-ind', 'emp-joao', 'Calçado de Segurança Tipo Botina com Bico de Composite', '28511', '2027-06-15', '2026-01-10', null, 'PERIODIC', null, '2027-06-15', null, null, 1],
    ['epi-03', 'tenant-ind', 'emp-joao', 'Óculos de Segurança Modelo Spectra Antirrisco', '11268', '2029-01-30', '2026-01-10', null, 'PERIODIC', null, '2029-01-30', null, null, 1],
    ['epi-04', 'tenant-ind', 'emp-marcos','Capacete de Segurança Tipo Aba Frontal Classe B', '31469', '2028-11-20', '2026-02-15', null, 'PERIODIC', null, '2028-11-20', null, null, 1],
    // EPI VENCIDO para teste de barreira de acesso à fábrica (SE_BLOQUEIO_CHAO_FABRICA):
    ['epi-05', 'tenant-ind', 'emp-marcos','Protetor Auditivo de Inserção de Silicone (Plug)', '15624', '2024-03-10', '2024-01-10', null, 'PERIODIC', null, '2024-03-10', null, null, 0],
    
    // ASOs Clínicos Ocupacionais:
    ['aso-01', 'tenant-ind', 'emp-joao', 'Atestado de Saúde Ocupacional Periódico NR-07', 'N/A', null, null, null, 'PERIODIC', '2026-01-15', '2027-01-15', 'Dr. Carlos Eduardo Mendonça', 'CRM-PR 28.941', 1],
    // ASO VENCIDO E INAPTO para emp-marcos (Trava de acesso dura):
    ['aso-02', 'tenant-ind', 'emp-marcos','Atestado de Saúde Ocupacional Periódico NR-07', 'N/A', null, null, null, 'PERIODIC', '2025-08-01', '2026-08-01', 'Dr. Carlos Eduardo Mendonça', 'CRM-PR 28.941', 0],
    ['aso-03', 'tenant-ind', 'emp-valeria','Atestado de Saúde Ocupacional Admissional NR-07','N/A', null, null, null, 'ADMISSIONAL', '2026-03-01', '2027-03-01', 'Dra. Patrícia Alencar', 'CRM-PR 35.120', 1],
    ['aso-04', 'tenant-tech','emp-lucas',  'Atestado Ocupacional Home Office / Ergonômico', 'N/A', null, null, null, 'PERIODIC', '2026-02-10', '2027-02-10', 'Dr. Roberto Meireles', 'CRM-PR 21.450', 1],
];

$stmtSafety = $pdo->prepare(
    "INSERT INTO equipment_aso 
     (id, tenant_id, employee_id, equipment_name, ca_number, ca_expiration_date, delivery_date, return_date, exam_type, exam_date, expiration_date, physician_name, physician_crm, is_fit, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($safetyRecords as [$sid, $tid, $empId, $item, $ca, $caExp, $deliv, $ret, $etype, $edate, $expDate, $docName, $crm, $fit]) {
    $stmtSafety->execute([$sid, $tid, $empId, $item, $ca, $caExp, $deliv, $ret, $etype, $edate, $expDate, $docName, $crm, $fit, $now]);
}
echo "✔ 11/17: equipment_aso (9 registros de SST: EPIs válidos/vencidos e ASOs aptos/inaptos)\n";

// ── 12. INSURANCE_POLICIES (Fernando: ALI_TENANTS_POLICIES & Portal FinCorp)
$policies = [
    ['pol-01', 'tenant-fin', 'FINCORP-2026-88991', 'BRK-001', 'FinCorp Seguradora S.A.', 'emp-helena',  50000000, 14000, 'ACTIVE',  '2026-01-01', '2026-12-31'],
    ['pol-02', 'tenant-fin', 'FINCORP-2026-88992', 'BRK-001', 'FinCorp Seguradora S.A.', 'emp-gabriel', 80000000, 22400, 'ACTIVE',  '2026-01-01', '2026-12-31'],
    ['pol-03', 'tenant-fin', 'FINCORP-2026-88993', 'BRK-002', 'FinCorp Seguradora S.A.', 'emp-arthur', 100000000, 35000, 'PENDING', '2026-06-01', '2027-05-31'],
    ['pol-04', 'tenant-tech','FINCORP-2026-77101', 'BRK-003', 'FinCorp Seguradora S.A.', 'emp-carlos',  60000000, 18000, 'ACTIVE',  '2026-01-01', '2026-12-31'],
];

$stmtPol = $pdo->prepare(
    "INSERT INTO insurance_policies (id, tenant_id, policy_number, broker_code, insurer_name, employee_id, insured_capital_cents, monthly_premium_cents, status, start_date, end_date, coverage_details)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($policies as [$pid, $tid, $pnum, $bcode, $iname, $empId, $cap, $prem, $st, $start, $end]) {
    $cov = json_encode(['morte' => true, 'invalidez_acidente' => true, 'd_and_o' => ($tid === 'tenant-fin')]);
    $stmtPol->execute([$pid, $tid, $pnum, $bcode, $iname, $empId, $cap, $prem, $st, $start, $end, $cov]);
}
echo "✔ 12/17: insurance_policies (4 apólices FinCorp emitidas)\n";

// ── 13. PIX_TRANSACTIONS (Fernando: AIE_PIX_BACEN_GATEWAY) ─────
$pixTransactions = [
    ['pix-01', 'tenant-fin', 'pol-01', 'TX202610240001FINCORP88991', 'E00038166202610240815A1B2C3D4E5', 14000, '00020126580014br.gov.bcb.pix0129pix-financeiro@fincorp.com.br5204000053039865405140.005802BR5914FINCORP SEGUROS6008CURITIBA62310527TX202610240001FINCORP889916304E8A1', 'PAID', '2026-10-24T08:15:22-03:00'],
    ['pix-02', 'tenant-fin', 'pol-02', 'TX202610240002FINCORP88992', 'E00038166202610240930F6G7H8I9J0', 22400, '00020126580014br.gov.bcb.pix0129pix-financeiro@fincorp.com.br5204000053039865405224.005802BR5914FINCORP SEGUROS6008CURITIBA62310527TX202610240002FINCORP889926304C9B4', 'PAID', '2026-10-24T09:30:11-03:00'],
    ['pix-03', 'tenant-fin', 'pol-03', 'TX202610240003FINCORP88993', null, 35000, '00020126580014br.gov.bcb.pix0129pix-financeiro@fincorp.com.br5204000053039865405350.005802BR5914FINCORP SEGUROS6008CURITIBA62310527TX202610240003FINCORP88993630489DF', 'PENDING', null],
    ['pix-04', 'tenant-tech','pol-04', 'TX202610240004TECHSTART77', 'E00038166202610241010K1L2M3N4O5', 18000, '00020126580014br.gov.bcb.pix0129pix-financeiro@fincorp.com.br5204000053039865405180.005802BR5914FINCORP SEGUROS6008CURITIBA62310527TX202610240004TECHSTART77630441B2', 'PAID', '2026-10-24T10:10:45-03:00'],
];

$stmtPix = $pdo->prepare(
    "INSERT INTO pix_transactions (id, tenant_id, policy_id, txid, e2eid, amount_cents, qr_code, status, created_at, paid_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($pixTransactions as [$pxid, $tid, $polId, $txid, $e2e, $cents, $qr, $st, $paidAt]) {
    $stmtPix->execute([$pxid, $tid, $polId, $txid, $e2e, $cents, $qr, $st, $now, $paidAt]);
}
echo "✔ 13/17: pix_transactions (4 cobranças PIX EMV e liquidação instantânea BACEN)\n";

// ── 14. REP_IMPORTS (Felipe: AIE_RELOGIO_REP_671) ──────────────
$repImports = [
    ['rep-01', 'tenant-tech', 'AFD_REP_TECHSTART_001_202609.txt', 48, 'COMPLETED', json_encode(['rep_serial' => '00014002880001001', 'standard' => 'PORTARIA_671_MTE'])],
    ['rep-02', 'tenant-ind',  'AFD_REP_METALSUL_002_202609.txt',  120, 'COMPLETED', json_encode(['rep_serial' => '00014002880002002', 'standard' => 'PORTARIA_671_MTE'])],
    ['rep-03', 'tenant-fin',  'AFD_REP_FINCORP_003_202609.txt',   32, 'COMPLETED', json_encode(['rep_serial' => '00014002880003003', 'standard' => 'PORTARIA_671_MTE'])],
];

$stmtRep = $pdo->prepare(
    "INSERT INTO rep_imports (id, tenant_id, filename, records_count, imported_at, status, log_details)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
foreach ($repImports as [$repid, $tid, $fname, $rcount, $st, $details]) {
    $stmtRep->execute([$repid, $tid, $fname, $rcount, $now, $st, $details]);
}
echo "✔ 14/17: rep_imports (3 lotes fiscais AFD importados)\n";

// ── 15. OVERTIME_ENTRIES (Felipe: ALI_BANCO_HORAS_NOTURNO) ─────
$overtimes = [
    ['ot-01', 'tenant-tech', 'emp-lucas',  '2026-09-10', 4.0, 'OVERTIME_50',  66.82, 400.92, 'Standard50Strategy', 'Homologação de release em ambiente produtivo', '2027-03-10', 'PENDING'],
    ['ot-02', 'tenant-tech', 'emp-lucas',  '2026-09-13', 6.0, 'OVERTIME_100', 66.82, 801.84, 'Sunday100Strategy',  'Plantão dominical de suporte a infraestrutura crítica', '2027-03-13', 'PENDING'],
    ['ot-03', 'tenant-tech', 'emp-lucas',  '2026-09-15', 3.5, 'BANK_HOURS',   66.82, 0.0,    'BankHoursStrategy',  'Crédito em banco de horas semestral', '2027-03-15', 'PENDING'],
    ['ot-04', 'tenant-ind',  'emp-marcos', '2026-09-18', 5.0, 'OVERTIME_50',  28.64, 214.80, 'Standard50Strategy', 'Manutenção corretiva de torno CNC fora do turno', '2027-03-18', 'PENDING'],
    ['ot-05', 'tenant-ind',  'emp-marcos', '2026-09-20', 8.0, 'OVERTIME_100', 28.64, 458.24, 'Sunday100Strategy',  'Operação em feriado municipal de emergência', '2027-03-20', 'PENDING'],
    ['ot-06', 'tenant-fin',  'emp-gabriel','2026-09-22', 2.0, 'BANK_HOURS',   60.68, 0.0,    'BankHoursStrategy',  'Auditoria interna de compliance BACEN', '2027-03-22', 'PENDING'],
];

$stmtOt = $pdo->prepare(
    "INSERT INTO overtime_entries (id, tenant_id, employee_id, date, hours, type, hourly_rate, amount, strategy_used, description, expiration_date, status_fechamento, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($overtimes as [$otid, $tid, $empId, $dt, $hrs, $type, $rate, $amt, $strat, $desc, $exp, $stFech]) {
    $stmtOt->execute([$otid, $tid, $empId, $dt, $hrs, $type, $rate, $amt, $strat, $desc, $exp, $stFech, $now]);
}
echo "✔ 15/17: overtime_entries (6 lançamentos com Strategy 50%, 100% e Banco de Horas)\n";

// ── 16. EXTERNAL_INTEGRATIONS (Suporte a todos os 10 AIEs) ──────
$integrations = [
    // 1. AIE Receita Federal CPF (Andryus)
    ['ext-01', 'tenant-tech', 'AIE_RECEITA_FEDERAL_CPF', 'https://api.receitafederal.gov.br/v1/cpf/validate', 
     ['cpf' => '055.555.555-04', 'action' => 'VALIDATE_REGISTRATION'], 
     ['status' => 'REGULAR', 'situation_code' => '00', 'protocol' => 'RF-2026-CPF-05555555504', 'consulted_at' => date('Y-m-d H:i:s')], 'SUCCESS', 38],
    
    // 2. AIE eSocial S-2200 (Andryus)
    ['ext-02', 'tenant-tech', 'AIE_ESOCIAL_S2200', 'https://webservices.producaorestrita.esocial.gov.br/servicos/empregador/enviarloteeventos/WsEnviarLoteEventos.svc',
     ['event' => 'S-2200', 'cpf' => '055.555.555-04', 'admission' => '2022-02-14'],
     ['status' => 'TRANSMITTED', 'code' => 201, 'receipt_number' => '1.2.202610.0000000001', 'protocol' => 'ESOCIAL-PROT-8849102'], 'SUCCESS', 95],
    
    // 3. AIE Relógio REP Portaria 671 (Felipe)
    ['ext-03', 'tenant-tech', 'AIE_RELOGIO_REP_671', 'https://rep.relogioponto.mte.gov.br/api/afd/stream',
     ['filename' => 'AFD_REP_TECHSTART_001_202609.txt', 'device' => 'CONTROLID_671'],
     ['status' => 'COMPLETED', 'records_parsed' => 48, 'rep_serial' => '00014002880001001'], 'SUCCESS', 52],
    
    // 4. AIE NTP.br Hora Legal Brasileira (Felipe)
    ['ext-04', 'tenant-tech', 'AIE_NTP_HORA_LEGAL', 'udp://a.st1.ntp.br:123',
     ['server_pool' => 'a.st1.ntp.br', 'stratum_requested' => 1],
     ['status' => 'SYNCHRONIZED', 'stratum' => 1, 'legal_time_brasilia' => date('c'), 'root_delay_ms' => 1.85], 'SUCCESS', 12],
    
    // 5. AIE Operadora Benefícios Flexíveis (Valentin)
    ['ext-05', 'tenant-tech', 'AIE_OPERADORA_BENEFICIOS', 'https://api.caju.com.br/v2/cards/recharge',
     ['employee_id' => 'emp-lucas', 'category' => 'MEAL', 'amount_cents' => 95000],
     ['status' => 'APPROVED', 'authorization_code' => 'CAJU-AUTH-2026-994812', 'new_balance_cents' => 107450], 'SUCCESS', 64],
    
    // 6. AIE Operadora Saúde TISS ANS (Valentin)
    ['ext-06', 'tenant-tech', 'AIE_OPERADORA_SAUDE_ANS', 'https://tiss.ans.gov.br/services/v4_01_00/autorizacaoProcedimento',
     ['beneficiary_id' => 'emp-lucas', 'procedure_tuss' => '10101012', 'operator' => 'Bradesco Saúde (ANS 005711)'],
     ['status' => 'ELIGIBLE_AUTHORIZED', 'tiss_guide' => 'TISS-2026-889412', 'copay_cents' => 3000], 'SUCCESS', 78],
    
    // 7. AIE Catálogo CAEPI MTE (Nicolas)
    ['ext-07', 'tenant-ind',  'AIE_CATALOGO_CAEPI_MTE', 'https://caepi.mte.gov.br/api/v1/certificados/consulta',
     ['ca_number' => '31469', 'equipment' => 'Capacete MSA V-Gard'],
     ['status' => 'VALIDO', 'manufacturer' => 'MSA do Brasil', 'valid_until' => '2028-11-20', 'is_valid_for_delivery' => true], 'SUCCESS', 41],
    
    // 8. AIE Clínica Médica Medicina Ocupacional SOC (Nicolas)
    ['ext-08', 'tenant-ind',  'AIE_CLINICA_MEDICINA_OCUP', 'https://api.soc.com.br/v1/aso/consult',
     ['employee_id' => 'emp-joao', 'exam_type' => 'PERIODICO', 'pcmso' => 'NR-07'],
     ['status' => 'ISSUED', 'protocol' => 'SOC-ASO-2026-99120', 'is_fit' => true, 'physician_crm' => 'CRM-PR 28.941'], 'SUCCESS', 86],
    
    // 9. AIE Seguradora FinCorp Underwriting (Fernando)
    ['ext-09', 'tenant-fin',  'AIE_FINCORP_SEGURADORA', 'https://underwriting.fincorp.com.br/api/v3/policies/quote',
     ['employee_id' => 'emp-helena', 'capital_cents' => 50000000, 'type' => 'LIFE_GROUP'],
     ['status' => 'APPROVED_UNDERWRITING', 'proposal' => 'FIN-PROP-2026-98124', 'monthly_premium_cents' => 14000], 'SUCCESS', 92],
    
    // 10. AIE PIX Gateway BACEN SPI (Fernando)
    ['ext-10', 'tenant-fin',  'AIE_PIX_BACEN_GATEWAY', 'https://spi.bcb.gov.br/api/v2/cobv',
     ['txid' => 'TX202610240001FINCORP88991', 'amount_cents' => 14000],
     ['status' => 'PENDING', 'pix_copia_e_cola' => '00020126580014br.gov.bcb.pix...', 'bacen_spi_protocol' => 'SPI-2026-BACEN-99124'], 'SUCCESS', 35],
];

$stmtInt = $pdo->prepare(
    "INSERT INTO external_integrations (id, tenant_id, service_name, endpoint_url, request_payload, response_payload, status, duration_ms, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
foreach ($integrations as [$extId, $tid, $sname, $url, $req, $res, $st, $dur]) {
    $stmtInt->execute([
        $extId,
        $tid,
        $sname,
        $url,
        json_encode($req, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $st,
        $dur,
        $now
    ]);
}
echo "✔ 16/17: external_integrations (10 registros cobrindo 100% dos 10 AIEs)\n";

// ── 17. AUDIT_LOGS (Fernando: ALI_AUDIT_LOG_LEDGER - SHA-256) ──
$logger = AuditLogger::getInstance();
$logger->clearLogs();

$auditEvents = [
    ['tenant-tech', 'usr-admin',   'TENANT_INITIALIZED',      'Tenant',         'tenant-tech', ['status' => 'ACTIVE', 'plan' => 'ENTERPRISE']],
    ['tenant-tech', 'usr-admin',   'EMPLOYEE_HIRED',          'Employee',       'emp-lucas',   ['name' => 'Lucas Silva', 'salary_cents' => 980000]],
    ['tenant-tech', 'usr-lucas',   'TIMELOG_PUNCHED',         'TimeLog',        'tl-0001',     ['type' => 'ENTRY', 'nsr' => 1]],
    ['tenant-tech', 'usr-mariana', 'VACATION_APPROVED',       'VacationRequest','vac-01',      ['employee_id' => 'emp-lucas', 'days' => 20]],
    ['tenant-ind',  'usr-joao',    'EPI_DELIVERED',           'EquipmentASO',   'epi-01',      ['item' => 'Capacete Classe B', 'ca' => '31469']],
    ['tenant-ind',  'usr-joao',    'TURNSTILE_ACCESS_CHECK',  'FactoryGate',    'emp-marcos',  ['verdict' => 'BLOCKED', 'reason' => 'CA_EXPIRED_15624']],
    ['tenant-fin',  'usr-helena',  'INSURANCE_POLICY_ISSUED', 'InsurancePolicy','pol-01',      ['capital_cents' => 50000000, 'insured' => 'emp-helena']],
    ['tenant-fin',  'usr-gabriel', 'PIX_BILLING_SETTLED',     'PixTransaction', 'pix-01',      ['amount_cents' => 14000, 'status' => 'PAID']],
];

$stmtAudit = $pdo->prepare(
    "INSERT INTO audit_logs (id, tenant_id, actor_user_id, action, entity_type, entity_id, previous_state, new_state, ip_address, user_agent, timestamp, previous_hash, integrity_hash)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

foreach ($auditEvents as [$tid, $actor, $action, $etype, $eid, $state]) {
    $logObj = $logger->log($tid, $action, $etype, $eid, $state, $actor);
    $stmtAudit->execute([
        $logObj->id,
        $logObj->tenantId,
        $logObj->actorUserId,
        $logObj->action,
        $logObj->entityType,
        $logObj->entityId,
        json_encode($logObj->previousState),
        json_encode($logObj->newState),
        $logObj->ipAddress,
        $logObj->userAgent,
        $logObj->timestamp->format('Y-m-d\TH:i:s.u\Z'),
        $logObj->previousHash,
        $logObj->integrityHash
    ]);
}

$isChainValid = $logger->verifyChainIntegrity();
echo "✔ 17/17: audit_logs (" . $logger->count() . " blocos criptográficos SHA-256 encadeados. Integridade: " . ($isChainValid ? "100% VÁLIDA" : "FALHA") . ")\n";

echo "=================================================================\n";
echo "✅ SEED COMPLETO COM SUCESSO! Todas as 17 tabelas estão povoadas.\n";
echo "=================================================================\n";
