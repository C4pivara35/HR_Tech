<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use HrTech\Lps\FeatureToggleManager;

class AdminController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function dashboard(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        // Fetch counts from database
        $empStmt = $this->pdo->prepare("SELECT COUNT(*) FROM employees WHERE is_active = 1 AND tenant_id = ?");
        $empStmt->execute([$activeTenantId]);
        $employeeCount = $empStmt->fetchColumn();

        $logStmt = $this->pdo->prepare("SELECT COUNT(*) FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE e.tenant_id = ?");
        $logStmt->execute([$activeTenantId]);
        $timeLogCount = $logStmt->fetchColumn();

        $vacStmt = $this->pdo->prepare("SELECT COUNT(*) FROM vacation_requests vr JOIN employees e ON vr.employee_id = e.id WHERE e.tenant_id = ? AND vr.status = 'PENDING'");
        $vacStmt->execute([$activeTenantId]);
        $pendingVacations = $vacStmt->fetchColumn();

        $benStmt = $this->pdo->prepare("SELECT COUNT(*) FROM benefits WHERE tenant_id = ?");
        $benStmt->execute([$activeTenantId]);
        $benefitCount = $benStmt->fetchColumn();

        logQuery("SELECT stats FOR tenant_id = ?", [$activeTenantId], "DatabaseManager SQLite");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/dashboard/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function index(): void {
        if (($_SESSION['active_role'] ?? '') !== 'admin') {
            $_SESSION['flash_error'] = "Acesso restrito ao Administrador Global.";
            header("Location: index.php?route=dashboard");
            exit;
        }

        $ftManager = FeatureToggleManager::getInstance();

        $tenants = [
            'tenant-tech' => 'TechStart Inovações Ltda (Tecnologia)',
            'tenant-ind'  => 'Metalúrgica Sul S.A. (Indústria)',
            'tenant-fin'  => 'FinCorp Seguros e Investimentos (Financeiro)'
        ];

        $featuresCatalog = [
            'bank_of_hours'             => 'Banco de Horas compensatório (Art. 59 §2 CLT)',
            'overtime_payout'           => 'Pagamento pecuniário de horas extras (50% e 100%)',
            'risk_ppe_required'         => 'Obrigatoriedade estrita de EPI e ASO (NR-6 / NR-7)',
            'flexible_benefits'         => 'Cartão de benefícios flexíveis multicarteira',
            'd_and_o_insurance'         => 'Seguro de Responsabilidade Civil D&O FinCorp',
            'chartered_transport'       => 'Transporte Fretado para colaboradores industriais',
            'biometric_punch_mandatory' => 'Marcação de ponto com biometria facial obrigatória',
            'executive_health_plan'     => 'Plano de Saúde Executivo sem coparticipação',
            'aggressive_bonus'          => 'Metas e Bônus Agressivos com alavancagem até 120%',
            'strict_lgpd_audit'         => 'Auditoria estrita de acessos a dados sensíveis (LGPD)',
            'fincorp_life_policy'       => 'Apólice de Seguro de Vida em Grupo FinCorp',
        ];

        logQuery("LPS FeatureToggleManager::getInstance() catalog resolution", [], "Singleton FeatureToggleManager");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/admin/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function toggleFeature(): void {
        $tenantId = $_GET['tenant_id'] ?? '';
        $feature  = $_GET['feature'] ?? '';

        if (!empty($tenantId) && !empty($feature)) {
            $current = $_SESSION['feature_overrides'][$tenantId][$feature] ?? false;
            $_SESSION['feature_overrides'][$tenantId][$feature] = !$current;

            $statusText = !$current ? 'ATIVADA' : 'DESATIVADA';
            $_SESSION['flash_message'] = "Funcionalidade '$feature' $statusText para a empresa $tenantId!";

            logQuery("FeatureToggleManager::setTenantFeature('$tenantId', '$feature', " . (!$current ? 'true' : 'false') . ")", [], "LpsVariabilityEngine");
        }

        header("Location: index.php?route=admin");
        exit;
    }
}
