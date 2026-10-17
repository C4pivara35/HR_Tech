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
        require_once __DIR__ . '/Controllers/TimeLogController.php';
        require_once __DIR__ . '/Controllers/VacationController.php';
        require_once __DIR__ . '/Controllers/BenefitController.php';
        require_once __DIR__ . '/Controllers/SafetyController.php';
        require_once __DIR__ . '/Controllers/InsuranceController.php';
        require_once __DIR__ . '/Controllers/PayrollController.php';
        require_once __DIR__ . '/Controllers/ReportController.php';
        require_once __DIR__ . '/Controllers/OkrController.php';
        require_once __DIR__ . '/Controllers/ComplianceController.php';

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
                } else {
                    $controller->index();
                }
                break;

            case 'timelogs':
                $controller = new Controllers\TimeLogController($this->pdo);
                if ($action === 'punch') {
                    $controller->punch();
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
                } else {
                    $controller->index();
                }
                break;

            case 'benefits':
                $controller = new Controllers\BenefitController($this->pdo);
                if ($action === 'store') {
                    $controller->store();
                } elseif ($action === 'delete') {
                    $controller->delete();
                } else {
                    $controller->index();
                }
                break;

            case 'safety':
                $controller = new Controllers\SafetyController($this->pdo);
                if ($action === 'deliver_equipment') {
                    $controller->deliverEquipment();
                } elseif ($action === 'record_exam') {
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
