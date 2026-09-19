<?php
declare(strict_types=1);
define('BACKEND_BASE', __DIR__ . '/hrtech_backend_patterns');
require_once BACKEND_BASE . '/src/Autoloader.php';
use HrTech\Autoloader;
$loader = new Autoloader();
$loader->addNamespace('HrTech', BACKEND_BASE . '/src');
$loader->register();
use HrTech\Database\DatabaseManager;
$db  = DatabaseManager::getInstance(__DIR__ . '/hrtech_db.sqlite');
$db->migrate();
$pdo = $db->getConnection();

$now = date('Y-m-d\TH:i:sP');
echo "Seeding HRTech database...\n";

// Clean slate
$pdo->exec('PRAGMA foreign_keys = OFF;');
foreach (['audit_logs','insurance_policies','equipment_aso','benefits','vacation_requests',
          'time_adjustment_requests','time_logs','users','employees','roles','departments','tenants'] as $t) {
    $pdo->exec("DELETE FROM $t");
}
$pdo->exec('PRAGMA foreign_keys = ON;');

// ── TENANTS ──────────────────────────────────────────────────
$tenants = [
    ['tenant-tech', '11222333000181', 'TechStart Inovações Ltda', 'TechStart', 'tech'],
    ['tenant-ind',  '22333444000192', 'Metalúrgica Sul S.A.',      'MetalSul',  'industry'],
    ['tenant-fin',  '33444555000103', 'FinCorp Seguros e Invest.', 'FinCorp',   'financial'],
];
foreach ($tenants as [$id, $cnpj, $corp, $trad, $seg]) {
    $pdo->prepare("INSERT INTO tenants (id,cnpj,corporate_name,trading_name,segment,is_active,module_licenses,created_at)
        VALUES (?,?,?,?,?,1,?,?)")
        ->execute([$id, $cnpj, $corp, $trad, $seg,
            json_encode(['time_tracking','payroll','benefits','insurance']), $now]);
}
echo "✔ 3 tenants\n";

// ── DEPARTMENTS ───────────────────────────────────────────────
$departments = [
    ['dept-ti',  'tenant-tech', 'TI-01', 'Tecnologia & Engenharia', 'CC-1000'],
    ['dept-rh',  'tenant-tech', 'RH-01', 'Recursos Humanos',         'CC-1100'],
    ['dept-ops', 'tenant-ind',  'OP-01', 'Operações Industriais',    'CC-2000'],
    ['dept-fin', 'tenant-fin',  'FI-01', 'Financeiro & Seguros',     'CC-3000'],
];
foreach ($departments as [$id, $tid, $code, $name, $cc]) {
    $pdo->prepare("INSERT INTO departments (id,tenant_id,code,name,cost_center,is_active,created_at) VALUES (?,?,?,?,?,1,?)")
        ->execute([$id, $tid, $code, $name, $cc, $now]);
}
echo "✔ 4 departments\n";

// ── ROLES ─────────────────────────────────────────────────────
$roles = [
    ['role-dir-rh',  'tenant-tech', 'Diretora de RH & Operations',      90],
    ['role-analista','tenant-tech', 'Analista de Departamento Pessoal',  40],
    ['role-ger-eng', 'tenant-tech', 'Gerente de Engenharia',             70],
    ['role-dev',     'tenant-tech', 'Senior Python/React Developer',      50],
    ['role-rec',     'tenant-tech', 'Recrutadora & Talent Acquisition',   40],
    ['role-lider',   'tenant-ind',  'Líder de Produção Industrial',       60],
    ['role-comp',    'tenant-fin',  'Analista de Compliance',             50],
    ['role-corr',    'tenant-fin',  'Corretora Sênior de Seguros',        55],
];
foreach ($roles as [$id, $tid, $name, $level]) {
    $pdo->prepare("INSERT INTO roles (id,tenant_id,name,description,hierarchy_level,permissions,created_at) VALUES (?,?,?,?,?,'[]',?)")
        ->execute([$id, $tid, $name, $name, $level, $now]);
}
echo "✔ 8 roles\n";

// ── EMPLOYEES ─────────────────────────────────────────────────
$employees = [
    ['emp-mariana', 'tenant-tech', '111.222.333-01', 'Mariana Santos',  'mariana.santos@techstart.com.br',  '(41)99111-1111', '1985-03-15', '2020-01-10', 'dept-rh',  'role-dir-rh',  1850000, 'CLT'],
    ['emp-roberto', 'tenant-tech', '222.333.444-02', 'Roberto Lima',    'roberto.lima@techstart.com.br',    '(41)99222-2222', '1990-07-22', '2021-03-15', 'dept-rh',  'role-analista', 650000, 'CLT'],
    ['emp-carlos',  'tenant-tech', '333.444.555-03', 'Carlos Eduardo',  'carlos.eduardo@techstart.com.br',  '(41)99333-3333', '1988-11-05', '2019-06-01', 'dept-ti',  'role-ger-eng', 1350000, 'CLT'],
    ['emp-lucas',   'tenant-tech', '444.555.666-04', 'Lucas Silva',     'lucas.silva@techstart.com.br',     '(41)99444-4444', '1995-04-18', '2022-02-14', 'dept-ti',  'role-dev',      980000, 'CLT'],
    ['emp-ana',     'tenant-tech', '555.666.777-05', 'Ana Souza',       'ana.souza@techstart.com.br',       '(41)99555-5555', '1993-09-30', '2023-01-09', 'dept-rh',  'role-rec',      720000, 'CLT'],
    ['emp-joao',    'tenant-ind',  '666.777.888-06', 'João Oliveira',   'joao.oliveira@metalsul.com.br',    '(41)99666-6666', '1982-12-20', '2015-08-01', 'dept-ops', 'role-lider',    780000, 'CLT'],
    ['emp-gabriel', 'tenant-fin',  '777.888.999-07', 'Gabriel Torres',  'gabriel.torres@fincorp.com.br',    '(41)99777-7777', '1991-06-11', '2020-09-01', 'dept-fin', 'role-comp',     890000, 'CLT'],
    ['emp-helena',  'tenant-fin',  '888.999.000-08', 'Helena Martins',  'helena.martins@fincorp.com.br',    '(41)99888-8888', '1987-02-28', '2018-04-15', 'dept-fin', 'role-corr',    1120000, 'CLT'],
];
foreach ($employees as [$id,$tid,$cpf,$name,$email,$phone,$bday,$admit,$deptId,$roleId,$sal,$et]) {
    $pdo->prepare("INSERT INTO employees (id,tenant_id,cpf,full_name,email,phone,birth_date,admission_date,department_id,role_id,base_salary_cents,employment_type,is_active,vacation_days_balance,bank_hours_balance,created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,30,840,?)")
        ->execute([$id,$tid,$cpf,$name,$email,$phone,$bday,$admit,$deptId,$roleId,$sal,$et,$now]);
}
echo "✔ 8 employees\n";

// ── TIME LOGS ─────────────────────────────────────────────────
$logEntries = [
    ['emp-lucas',   '2026-09-18T08:03:00-03:00', 'ENTRY'],
    ['emp-lucas',   '2026-09-18T12:01:00-03:00', 'INTERVAL_START'],
    ['emp-lucas',   '2026-09-18T13:05:00-03:00', 'INTERVAL_END'],
    ['emp-lucas',   '2026-09-18T18:02:00-03:00', 'EXIT'],
    ['emp-mariana', '2026-09-18T08:55:00-03:00', 'ENTRY'],
    ['emp-mariana', '2026-09-18T12:10:00-03:00', 'INTERVAL_START'],
    ['emp-mariana', '2026-09-18T13:00:00-03:00', 'INTERVAL_END'],
    ['emp-joao',    '2026-09-18T06:00:00-03:00', 'ENTRY'],
    ['emp-joao',    '2026-09-18T12:00:00-03:00', 'EXIT'],
];
$nsr = [];
$prevHash = [];
foreach ($logEntries as $i => [$empId, $ts, $type]) {
    $nsr[$empId] = ($nsr[$empId] ?? 0) + 1;
    $prev = $prevHash[$empId] ?? '';
    $hash = hash('sha256', $empId.$ts.$type.$prev);
    $logId = 'tl-'.($i+1);
    // get tenant_id
    $tenantRow = $pdo->prepare("SELECT tenant_id FROM employees WHERE id=?");
    $tenantRow->execute([$empId]);
    $tid = $tenantRow->fetchColumn();
    $pdo->prepare("INSERT INTO time_logs (id,tenant_id,employee_id,timestamp,type,latitude,longitude,nsr,previous_hash,signature_hash) VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([$logId,$tid,$empId,$ts,$type,-25.4284,-49.2733,$nsr[$empId],$prev,$hash]);
    $prevHash[$empId] = $hash;
}
echo "✔ ".count($logEntries)." time logs\n";

// ── VACATION REQUESTS ─────────────────────────────────────────
$vacations = [
    ['vac-1', 'tenant-tech', 'emp-lucas',   '2026-10-06', '2026-10-20', 15, 'REQUESTED', 0],
    ['vac-2', 'tenant-tech', 'emp-roberto', '2026-11-01', '2026-11-15', 15, 'APPROVED',  1],
    ['vac-3', 'tenant-ind',  'emp-joao',    '2026-12-15', '2026-12-29', 15, 'REQUESTED', 0],
];
foreach ($vacations as [$id,$tid,$emp,$start,$end,$days,$status,$adv]) {
    $pdo->prepare("INSERT INTO vacation_requests (id,tenant_id,employee_id,start_date,end_date,duration_days,abono_pecuniario,advance_thirteenth_salary,abono_days,status,created_at)
        VALUES (?,?,?,?,?,?,0,?,0,?,?)")
        ->execute([$id,$tid,$emp,$start,$end,$days,$adv,$status,$now]);
}
echo "✔ 3 vacation requests\n";

// ── BENEFITS ──────────────────────────────────────────────────
$benefits = [
    ['ben-1', 'tenant-tech', 'MEAL_VOUCHER', 'Vale Refeição Caju',   'Caju',   95000, 0.0],
    ['ben-2', 'tenant-tech', 'HEALTH_PLAN',  'Plano Saúde Bradesco', 'Bradesco',45000, 20.0],
    ['ben-3', 'tenant-tech', 'TRANSPORT',    'Vale Transporte',      'VT Gov',  18600, 6.0],
    ['ben-4', 'tenant-ind',  'MEAL_VOUCHER', 'Vale Refeição Ticket', 'Ticket',  65000, 0.0],
    ['ben-5', 'tenant-fin',  'HEALTH_PLAN',  'Plano Saúde Amil Top', 'Amil',   120000, 15.0],
];
foreach ($benefits as [$id,$tid,$type,$name,$provider,$val,$pct]) {
    $pdo->prepare("INSERT INTO benefits (id,tenant_id,type,name,provider,value_cents,employee_cost_share_percentage,is_deductible)
        VALUES (?,?,?,?,?,?,?,1)")
        ->execute([$id,$tid,$type,$name,$provider,$val,$pct]);
}
echo "✔ 5 benefits\n";

// ── TIME ADJUSTMENT REQUESTS ──────────────────────────────────
$pdo->prepare("INSERT INTO time_adjustment_requests (id,tenant_id,employee_id,requested_date,original_time,requested_time,reason,status,created_at)
    VALUES ('adj-1','tenant-tech','emp-lucas','2026-09-17','17:00','18:15','Reunião de emergência com cliente prolongou atendimento.','PENDING',?)")->execute([$now]);
$pdo->prepare("INSERT INTO time_adjustment_requests (id,tenant_id,employee_id,requested_date,original_time,requested_time,reason,status,created_at)
    VALUES ('adj-2','tenant-tech','emp-ana','2026-09-16','08:00','09:30','Consulta médica com atestado.','PENDING',?)")->execute([$now]);
echo "✔ 2 adjustment requests\n";

// ── INSURANCE POLICIES ────────────────────────────────────────
$pdo->prepare("INSERT INTO insurance_policies (id,tenant_id,policy_number,broker_code,insurer_name,employee_id,insured_capital_cents,monthly_premium_cents,status,start_date,end_date,coverage_details)
    VALUES ('pol-1','tenant-fin','FINCORP-2026-88991','BRK-001','SulAmérica Seguros','emp-helena',50000000,14000,'ACTIVE','2026-01-01','2026-12-31',?)")
    ->execute([json_encode(['vida'=>true,'acidentes'=>true,'invalidez'=>true])]);
echo "✔ 1 insurance policy\n";

echo "\n✅ Seed completo! Banco populado com dados realistas.\n";
