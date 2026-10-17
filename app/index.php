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

// Registrador global e em sessão de consultas SQL para o console ao vivo
if (!isset($_SESSION['sql_logs']) || !is_array($_SESSION['sql_logs'])) {
    $_SESSION['sql_logs'] = [];
}
$GLOBALS['SQL_LOGS'] = &$_SESSION['sql_logs'];
$GLOBALS['GOF_PATTERNS_USED'] = [];

function logQuery(string $sql, array $params = [], string $pattern = '', string $pathway = ''): void {
    if (empty($pathway)) {
        $route = $_GET['route'] ?? 'dashboard';
        $action = $_GET['action'] ?? 'index';
        $pathway = "Navegador (View: $route) ➔ Router (?route=$route&action=$action) ➔ Controller" . (!empty($pattern) ? " [$pattern]" : "") . " ➔ SQLite (hrtech_db.sqlite)";
    }

    $entry = [
        'time' => date('H:i:s'),
        'sql' => $sql,
        'params' => $params,
        'pattern' => $pattern,
        'pathway' => $pathway
    ];
    
    if (!isset($_SESSION['sql_logs']) || !is_array($_SESSION['sql_logs'])) {
        $_SESSION['sql_logs'] = [];
    }
    
    $_SESSION['sql_logs'][] = $entry;
    
    // Mantém as últimas 50 consultas gravadas no rolling buffer
    if (count($_SESSION['sql_logs']) > 50) {
        array_shift($_SESSION['sql_logs']);
    }
    
    $GLOBALS['SQL_LOGS'] = $_SESSION['sql_logs'];
    
    if (!empty($pattern) && !in_array($pattern, $GLOBALS['GOF_PATTERNS_USED'] ?? [])) {
        $GLOBALS['GOF_PATTERNS_USED'][] = $pattern;
    }
}

// Se o console estiver vazio nesta sessão, registra inicialização da arquitetura
if (empty($_SESSION['sql_logs'])) {
    logQuery("DatabaseManager::getInstance('hrtech_db.sqlite') [SQLite 3 WAL Mode]", [], "Singleton DatabaseManager", "Bootstrap (app/index.php) ➔ DatabaseManager::getInstance() ➔ Conexão PDO Única SQLite");
    logQuery("TenantContextManager::getInstance()->setActiveTenant('" . ($_SESSION['active_role'] ?? 'tenant-tech') . "')", [], "Singleton TenantContextManager", "Bootstrap (app/index.php) ➔ TenantContextManager::getInstance() ➔ Sessão Multi-Tenant Isolada");
    logQuery("FeatureToggleManager::getInstance()->loadCatalog()", [], "LpsVariabilityEngine", "Bootstrap (app/index.php) ➔ FeatureToggleManager::loadCatalog() ➔ Tabela feature_toggles");
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
if (!isset($_SESSION['feature_overrides']) 
    || !isset($_SESSION['feature_overrides']['tenant-all'])
    || !isset($_SESSION['feature_overrides']['tenant-tech']['okr_evaluation']) 
    || ($_SESSION['feature_overrides']['tenant-tech']['d_and_o_insurance'] ?? true) === true
    || ($_SESSION['feature_overrides']['tenant-tech']['fincorp_life_policy'] ?? true) === true) {
    $_SESSION['feature_overrides'] = [
        'tenant-tech' => [
            'bank_of_hours'             => true,
            'overtime_payout'           => false,
            'risk_ppe_required'         => false,
            'flexible_benefits'         => true,
            'okr_evaluation'            => true,
            'd_and_o_insurance'         => false,
            'fincorp_life_policy'       => false,
            'strict_lgpd_audit'         => false,
        ],
        'tenant-ind' => [
            'bank_of_hours'             => false,
            'overtime_payout'           => true,
            'risk_ppe_required'         => true,
            'flexible_benefits'         => false,
            'chartered_transport'       => true,
            'okr_evaluation'            => false,
            'd_and_o_insurance'         => false,
            'fincorp_life_policy'       => false,
            'strict_lgpd_audit'         => false,
        ],
        'tenant-fin' => [
            'bank_of_hours'             => false,
            'overtime_payout'           => false,
            'risk_ppe_required'         => false,
            'flexible_benefits'         => false,
            'biometric_punch_mandatory' => true,
            'executive_health_plan'     => true,
            'aggressive_bonus'          => true,
            'strict_lgpd_audit'         => true,
            'fincorp_life_policy'       => true,
            'okr_evaluation'            => false,
        ],
        'tenant-all' => [
            'bank_of_hours'             => true,
            'overtime_payout'           => true,
            'risk_ppe_required'         => true,
            'flexible_benefits'         => true,
            'd_and_o_insurance'         => true,
            'fincorp_life_policy'       => true,
            'okr_evaluation'            => true,
            'strict_lgpd_audit'         => true,
            'biometric_punch_mandatory' => true,
            'chartered_transport'       => true,
            'executive_health_plan'     => true,
            'aggressive_bonus'          => true,
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
