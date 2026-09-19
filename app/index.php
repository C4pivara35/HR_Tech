<?php
declare(strict_types=1);

session_start();

define('ROOT_DIR', dirname(__DIR__));
define('BACKEND_BASE', ROOT_DIR . '/hrtech_backend_patterns');

// Autoload HrTech backend
require_once BACKEND_BASE . '/src/Autoloader.php';
use HrTech\Autoloader;

$loader = new Autoloader();
$loader->addNamespace('HrTech', BACKEND_BASE . '/src');
$loader->register();

use HrTech\Database\DatabaseManager;
use HrTech\Patterns\Singleton\TenantContextManager;
use HrTech\Lps\FeatureToggleManager;

// Initialize Database Connection
$dbManager = DatabaseManager::getInstance(ROOT_DIR . '/hrtech_db.sqlite');
$dbManager->migrate();
$pdo = $dbManager->getConnection();

// Global SQL query logger for presentation console
$GLOBALS['SQL_LOGS'] = [];
$GLOBALS['GOF_PATTERNS_USED'] = [];

function logQuery(string $sql, array $params = [], string $pattern = ''): void {
    $GLOBALS['SQL_LOGS'][] = [
        'time' => date('H:i:s'),
        'sql' => $sql,
        'params' => $params,
        'pattern' => $pattern
    ];
    if (!empty($pattern) && !in_array($pattern, $GLOBALS['GOF_PATTERNS_USED'])) {
        $GLOBALS['GOF_PATTERNS_USED'][] = $pattern;
    }
}

// Ensure default session values
if (!isset($_SESSION['active_role'])) {
    $_SESSION['active_role'] = 'tenant-tech'; // admin | tenant-tech | tenant-ind | tenant-fin
}

// Sync Tenant Context Singleton
if ($_SESSION['active_role'] !== 'admin') {
    TenantContextManager::getInstance()->setActiveTenant($_SESSION['active_role']);
} else {
    TenantContextManager::getInstance()->setActiveTenant('tenant-tech');
}

// Load Tenant Feature Overrides
if (!isset($_SESSION['feature_overrides'])) {
    $_SESSION['feature_overrides'] = [
        'tenant-tech' => [
            'bank_of_hours' => true,
            'overtime_payout' => false,
            'risk_ppe_required' => false,
            'flexible_benefits' => true,
            'd_and_o_insurance' => true,
        ],
        'tenant-ind' => [
            'bank_of_hours' => false,
            'overtime_payout' => true,
            'risk_ppe_required' => true,
            'flexible_benefits' => false,
            'chartered_transport' => true,
        ],
        'tenant-fin' => [
            'biometric_punch_mandatory' => true,
            'executive_health_plan' => true,
            'aggressive_bonus' => true,
            'strict_lgpd_audit' => true,
            'fincorp_life_policy' => true,
        ]
    ];
}

// Apply overrides to FeatureToggleManager Singleton
$ftManager = FeatureToggleManager::getInstance();
foreach ($_SESSION['feature_overrides'] as $tId => $features) {
    foreach ($features as $feat => $val) {
        $ftManager->setTenantFeature($tId, $feat, (bool)$val);
    }
}

// Load Router
require_once __DIR__ . '/Router.php';
$router = new App\Router($pdo);
$router->dispatch();
