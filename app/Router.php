<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * ==============================================================================
 * HRTech Core — Roteador Central HTTP & Orquestrador MVC
 * ==============================================================================
 * Disciplina: Medição e Análise de Software / Engenharia de Software (PUCPR)
 * Projeto: HRTech Core — Padrões de Reuso (GoF), 10 CRUDs e Linha de Produção de Software (LPS)
 *
 * Mapeamento de Autoria dos 10 CRUDs e Padrões GoF entre os 5 Membros da Equipe:
 *
 * 1. Fernando Lopes Duarte:
 *    - CRUD 1 (Tenants): Rota 'admin' (?action=create_tenant, delete_tenant)
 *    - CRUD 2 (Usuários/Autenticação): Rota 'switch_tenant', 'login'
 *    - Padrão GoF: Singleton (TenantContextManager, DatabaseManager, AuditLogger)
 *
 * 2. Andryus:
 *    - CRUD 3 (Colaboradores): Rota 'employees' (?action=store, delete)
 *    - CRUD 4 (Departamentos & Cargos): Alçada hierárquica Role::$hierarchyLevel
 *    - Padrões/Conceitos: Value Objects DDD (Money em centavos, Cpf com máscara LGPD)
 *
 * 3. Felipe:
 *    - CRUD 5 (Ponto Eletrônico Portaria 671 MTE): Rota 'timelogs' (?action=punch)
 *    - CRUD 6 (Ajustes de Ponto): Rota 'timelogs' (?action=request_adjustment, approve, reject)
 *    - Padrão GoF: Template Method de Importação (TimeLogImporterTemplate: CSV, JSON, API)
 *
 * 4. Valentin:
 *    - CRUD 7 (Férias CLT Art. 129-145): Rota 'vacations' (?action=request, approve, delete)
 *    - CRUD 8 (Benefícios Corporativos): Rota 'benefits' (?action=store, delete)
 *    - Padrões GoF: Template Method de Folha (PayrollCalculatorTemplate: CLT, PJ, Estágio)
 *      e Strategy de Benefícios (TransportationVoucherStrategy teto 6%, Meal, Health)
 *
 * 5. Nicholas:
 *    - CRUD 9 (SST NR-06/07 - EPI & ASO): Rota 'safety' (?action=deliver_equipment, record_exam)
 *    - CRUD 10 (FinCorp Seguros de Vida): Rota 'insurance' (?action=issue, delete)
 *    - Padrões GoF / LPS: Linha de Produção de Software (LpsVariabilityEngine, FeatureToggleManager),
 *      Strategy de Performance (OkrStrategy, 360, KPI) e Strategy de Horas Extras (50%, 100%, Banco).
 * ==============================================================================
 */
class Router {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function dispatch(): void {
        $route  = $_GET['route'] ?? 'dashboard';
        $action = $_GET['action'] ?? 'index';

        // Load Controllers
        require_once __DIR__ . '/Controllers/AuthController.php';
        require_once __DIR__ . '/Controllers/AdminController.php';
        require_once __DIR__ . '/Controllers/EmployeeController.php';
        require_once __DIR__ . '/Controllers/OrganogramController.php';
        require_once __DIR__ . '/Controllers/TimeLogController.php';
        require_once __DIR__ . '/Controllers/ApprovalController.php';
        require_once __DIR__ . '/Controllers/VacationController.php';
        require_once __DIR__ . '/Controllers/BenefitController.php';
        require_once __DIR__ . '/Controllers/SafetyController.php';
        require_once __DIR__ . '/Controllers/InsuranceController.php';
        require_once __DIR__ . '/Controllers/PixController.php';
        require_once __DIR__ . '/Controllers/PayrollController.php';
        require_once __DIR__ . '/Controllers/ReportController.php';
        require_once __DIR__ . '/Controllers/OkrController.php';
        require_once __DIR__ . '/Controllers/ComplianceController.php';
        require_once __DIR__ . '/Controllers/ExternalSimulatorsController.php';

        switch ($route) {
            case 'clear_console':
                $_SESSION['sql_logs'] = [];
                $GLOBALS['SQL_LOGS'] = [];
                header('Content-Type: application/json');
                echo json_encode(['status' => 'cleared']);
                exit;

            case 'ping_console':
                $now = date('H:i:s');
                logQuery("PRAGMA integrity_check; -- Verificação de integridade relacional SQLite", ['time' => $now], "DatabaseManager HealthCheck");
                header('Content-Type: application/json');
                echo json_encode(['status' => 'ok', 'logs' => $_SESSION['sql_logs'] ?? []]);
                exit;

            case 'poll_logs':
                header('Content-Type: application/json');
                echo json_encode([
                    'logs' => $_SESSION['sql_logs'] ?? [],
                    'count' => count($_SESSION['sql_logs'] ?? [])
                ]);
                exit;

            case 'switch_tenant':
                (new Controllers\AuthController($this->pdo))->switchTenant();
                break;

            case 'admin':
                $controller = new Controllers\AdminController($this->pdo);
                if ($action === 'toggle_feature') {
                    $controller->toggleFeature();
                } elseif ($action === 'create_tenant') {
                    $controller->createTenant();
                } elseif ($action === 'delete_tenant') {
                    $controller->deleteTenant();
                } else {
                    $controller->index();
                }
                break;

            case 'employees':
                $controller = new Controllers\EmployeeController($this->pdo);
                if ($action === 'store') {
                    $controller->store();
                } elseif ($action === 'delete') {
                    $controller->delete();
                } elseif ($action === 'new') {
                    $controller->newEmployee();
                } elseif ($action === 'detail' || $action === 'xml_s2200') {
                    $controller->detail();
                } elseif ($action === 'validate_cpf') {
                    $controller->validateCpf();
                } else {
                    $controller->index();
                }
                break;

            case 'organogram':
                $controller = new Controllers\OrganogramController($this->pdo);
                if ($action === 'create_department') {
                    $controller->createDepartment();
                } elseif ($action === 'create_role') {
                    $controller->createRole();
                } else {
                    $controller->index();
                }
                break;

            case 'timelogs':
                $controller = new Controllers\TimeLogController($this->pdo);
                if ($action === 'punch') {
                    $controller->punch();
                } elseif ($action === 'bank_hours' || $action === 'bank') {
                    $controller->bankHours();
                } elseif ($action === 'request_adjustment_view' || ($action === 'request_adjustment' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET')) {
                    $controller->requestAdjustmentView();
                } elseif ($action === 'request_adjustment') {
                    $controller->requestAdjustment();
                } elseif ($action === 'approve_adjustment') {
                    $controller->approveAdjustment();
                } elseif ($action === 'reject_adjustment') {
                    $controller->rejectAdjustment();
                } elseif ($action === 'delete_adjustment') {
                    $controller->deleteAdjustment();
                } elseif ($action === 'record_overtime') {
                    $controller->recordOvertime();
                } elseif ($action === 'delete_overtime') {
                    $controller->deleteOvertime();
                } elseif ($action === 'import_rep') {
                    $controller->importRep();
                } else {
                    $controller->index();
                }
                break;

            case 'approvals':
                $controller = new Controllers\ApprovalController($this->pdo);
                if ($action === 'approve_vacation') {
                    $controller->approveVacation();
                } elseif ($action === 'reject_vacation') {
                    $controller->rejectVacation();
                } elseif ($action === 'approve_timelog') {
                    $controller->approveTimelog();
                } elseif ($action === 'reject_timelog') {
                    $controller->rejectTimelog();
                } else {
                    $controller->index();
                }
                break;

            case 'vacations':
                $controller = new Controllers\VacationController($this->pdo);
                if ($action === 'request') {
                    $controller->request();
                } elseif ($action === 'approve') {
                    $controller->approve();
                } elseif ($action === 'delete') {
                    $controller->delete();
                } elseif ($action === 'receipt') {
                    $controller->receipt();
                } else {
                    $controller->index();
                }
                break;

            case 'benefits':
                $controller = new Controllers\BenefitController($this->pdo);
                if ($action === 'store') {
                    $controller->store();
                } elseif ($action === 'assign') {
                    $controller->assign();
                } elseif ($action === 'delete') {
                    $controller->delete();
                } else {
                    $controller->index();
                }
                break;

            case 'safety':
                $controller = new Controllers\SafetyController($this->pdo);
                if ($action === 'deliver_equipment' || $action === 'deliver_epi') {
                    $controller->deliverEquipment();
                } elseif ($action === 'record_exam' || $action === 'record_aso') {
                    $controller->recordExam();
                } elseif ($action === 'delete') {
                    $controller->delete();
                } else {
                    $controller->index();
                }
                break;

            case 'insurance':
                $controller = new Controllers\InsuranceController($this->pdo);
                if ($action === 'issue') {
                    $controller->issue();
                } elseif ($action === 'delete') {
                    $controller->delete();
                } elseif ($action === 'pix') {
                    (new Controllers\PixController($this->pdo))->index();
                } else {
                    $controller->index();
                }
                break;

            case 'pix':
                $controller = new Controllers\PixController($this->pdo);
                if ($action === 'generate') {
                    $controller->generate();
                } elseif ($action === 'simulate_settlement') {
                    $controller->simulateSettlement();
                } else {
                    $controller->index();
                }
                break;

            case 'payroll':
                (new Controllers\PayrollController($this->pdo))->index();
                break;

            case 'reports':
                $controller = new Controllers\ReportController($this->pdo);
                if ($action === 'export') {
                    $controller->export();
                } else {
                    $controller->index();
                }
                break;

            case 'okrs':
                $controller = new Controllers\OkrController($this->pdo);
                if ($action === 'store') {
                    $controller->store();
                } elseif ($action === 'delete') {
                    $controller->delete();
                } else {
                    $controller->index();
                }
                break;

            case 'compliance':
                $controller = new Controllers\ComplianceController($this->pdo);
                if ($action === 'record_log') {
                    $controller->recordLog();
                } elseif ($action === 'verify_chain' || $action === 'verify') {
                    $controller->verifyChain();
                } elseif ($action === 'export' || $action === 'export_audit_trail') {
                    $controller->export();
                } else {
                    $controller->index();
                }
                break;

            case 'external_simulators':
                $controller = new Controllers\ExternalSimulatorsController($this->pdo);
                if ($action === 'test_service' || $action === 'test') {
                    $controller->testService();
                } else {
                    $controller->index();
                }
                break;

            case 'dashboard':
            default:
                (new Controllers\AdminController($this->pdo))->dashboard();
                break;
        }
    }
}
