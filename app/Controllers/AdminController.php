<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use HrTech\Lps\FeatureToggleManager;

/**
 * ==============================================================================
 * AdminController — Administração Master, Multi-Tenancy & Linha de Produção (LPS)
 * ==============================================================================
 * Responsabilidades:
 * - Fernando: CRUD 1 (Tenants / Empresas) e Isolamento Multi-Tenant
 * - Nicholas: Linha de Produção de Software (LPS) e Matriz de Feature Toggles
 *
 * Conceitos de Arquitetura & Engenharia de Reuso:
 * 1. Arquitetura Multi-Tenant com Banco Compartilhado (Shared Database, Separate Schemas/IDs):
 *    - Cada registro de dados possui chave estrangeira `tenant_id`.
 *    - A troca de contexto no switch de empresa restringe a visualização em tempo de execução.
 *
 * 2. Linha de Produção de Software (LPS - Software Product Line):
 *    - O sistema adapta suas funcionalidades para múltiplos perfis de mercado:
 *      * TechStart (Tecnologia): OKRs, Banco de Horas flexível, EPIs dispensados.
 *      * Metalúrgica Sul (Indústria): NR-06/07 obrigatórios (EPI/ASO), Horas Extras pagas na folha, Avaliação 360°.
 *      * FinCorp Seguros (Financeiro): Biometria, KPIs e Portal exclusivo de Apólices.
 *
 * 3. Feature Toggle Manager:
 *    - Liga/desliga funcionalidades dinamicamente por empresa através da tabela `feature_toggles`.
 * ==============================================================================
 */
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
        $_SESSION['active_role'] = 'admin'; // Ativa contexto de administrador global

        $ftManager = FeatureToggleManager::getInstance();

        // Busca todas as empresas cadastradas no banco relacional SQLite (CRUD 1 - Fernando)
        $stmt = $this->pdo->query(
            "SELECT t.*, 
                    (SELECT COUNT(*) FROM employees e WHERE e.tenant_id = t.id AND e.is_active = 1) as employee_count
             FROM tenants t
             ORDER BY t.created_at ASC"
        );
        $allTenants = $stmt->fetchAll();

        // Mapeia tenants para a matriz de feature toggles
        $tenants = [];
        foreach ($allTenants as $t) {
            $tenants[$t['id']] = $t['corporate_name'] . ' (' . strtoupper($t['segment']) . ')';
        }

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

        logQuery("SELECT FROM tenants (all active tenants count=" . count($allTenants) . ")", [], "CRUD 1: Tenant Management (Fernando)");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/admin/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function createTenant(): void {
        $corpName  = trim($_POST['corporate_name'] ?? '');
        $tradeName = trim($_POST['trading_name'] ?? $corpName);
        $cnpjRaw   = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');
        $segment   = $_POST['segment'] ?? 'tech';

        if (empty($corpName) || empty($cnpjRaw)) {
            $_SESSION['flash_error'] = "Preencha a Razão Social e o CNPJ da empresa!";
            header("Location: index.php?route=admin");
            exit;
        }

        $id = 'tenant-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', substr($tradeName, 0, 8))) . '-' . rand(100, 999);
        $now = date('Y-m-d\TH:i:sP');
        $licenses = json_encode(['time_tracking', 'payroll', 'benefits', 'insurance']);

        $stmt = $this->pdo->prepare(
            "INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)"
        );
        $stmt->execute([$id, $cnpjRaw, $corpName, $tradeName, $segment, $licenses, $now]);

        // Configura perfil de variabilidade LPS padrão
        $_SESSION['feature_overrides'][$id] = match($segment) {
            'industry', 'industria' => [
                'bank_of_hours' => false,
                'overtime_payout' => true,
                'risk_ppe_required' => true,
                'flexible_benefits' => false,
                'chartered_transport' => true,
                'okr_evaluation' => false,
                'fincorp_life_policy' => false,
                'd_and_o_insurance' => false,
                'strict_lgpd_audit' => false,
            ],
            'financial', 'financeiro' => [
                'bank_of_hours' => false,
                'overtime_payout' => true,
                'risk_ppe_required' => false,
                'flexible_benefits' => false,
                'biometric_punch_mandatory' => true,
                'executive_health_plan' => true,
                'aggressive_bonus' => true,
                'strict_lgpd_audit' => true,
                'fincorp_life_policy' => true,
                'd_and_o_insurance' => true,
                'okr_evaluation' => false,
            ],
            default => [
                'bank_of_hours' => true,
                'overtime_payout' => false,
                'risk_ppe_required' => false,
                'flexible_benefits' => true,
                'okr_evaluation' => true,
                'd_and_o_insurance' => false,
                'fincorp_life_policy' => false,
                'strict_lgpd_audit' => false,
            ]
        };

        logQuery("INSERT INTO tenants (id=$id, corp=$corpName, cnpj=$cnpjRaw)", [$id], "CRUD 1: TenantService::createTenant (Fernando)");

        $_SESSION['flash_message'] = "Empresa '$corpName' criada com sucesso no sistema multi-tenant!";
        header("Location: index.php?route=admin");
        exit;
    }

    public function deleteTenant(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("DELETE FROM tenants WHERE id = ?");
            $stmt->execute([$id]);

            unset($_SESSION['feature_overrides'][$id]);

            logQuery("DELETE FROM tenants WHERE id = '$id'", [$id], "CRUD 1: TenantService::deleteTenant (Fernando)");

            $_SESSION['flash_message'] = "Empresa removida com sucesso do banco de dados!";
        }
        header("Location: index.php?route=admin");
        exit;
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
