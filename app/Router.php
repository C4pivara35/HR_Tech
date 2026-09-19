<?php
declare(strict_types=1);

namespace App;

use PDO;

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

        switch ($route) {
            case 'switch_tenant':
                (new Controllers\AuthController($this->pdo))->switchTenant();
                break;

            case 'admin':
                $controller = new Controllers\AdminController($this->pdo);
                if ($action === 'toggle_feature') {
                    $controller->toggleFeature();
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
                } else {
                    $controller->index();
                }
                break;

            case 'benefits':
                $controller = new Controllers\BenefitController($this->pdo);
                if ($action === 'store') {
                    $controller->store();
                } elseif ($action === 'enroll') {
                    $controller->enroll();
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
                } else {
                    $controller->index();
                }
                break;

            case 'insurance':
                $controller = new Controllers\InsuranceController($this->pdo);
                if ($action === 'issue') {
                    $controller->issue();
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

            case 'dashboard':
            default:
                (new Controllers\AdminController($this->pdo))->dashboard();
                break;
        }
    }
}
