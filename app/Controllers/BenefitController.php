<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Services\External\AieOperadoraBeneficiosService;
use App\Services\External\AieOperadoraSaudeService;

/**
 * ==============================================================================
 * BenefitController — Gestão de Benefícios & Fatura de Coparticipação
 * ==============================================================================
 * Funções de APF Conectadas:
 * - EE_VINCULAR_BENEFICIO (Valentin - 4 PFB)
 *   Vinculação formal de pacotes de benefícios a colaboradores individuais (`employee_benefits`).
 *
 * - SE_DEMONSTRATIVO_BENEFICIOS (Valentin - 5 PFB / Média Complexidade)
 *   Fatura de coparticipação aplicando os tetos da Lei 7.418/85 (VT máx. 6% do salário base),
 *   Programa de Alimentação do Trabalhador - PAT (VR/VA máx. 20% de coparticipação)
 *   e planos de saúde regulados pela ANS.
 * ==============================================================================
 */
class BenefitController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        // Filtros CE_CATALOGO_BENEFICIOS
        $filterType = trim($_GET['search_type'] ?? '');
        $filterProvider = trim($_GET['search_provider'] ?? '');

        $sql = "SELECT * FROM benefits WHERE tenant_id = ?";
        $params = [$activeTenantId];

        if (!empty($filterType)) {
            $sql .= " AND type = ?";
            $params[] = $filterType;
        }
        if (!empty($filterProvider)) {
            $sql .= " AND provider LIKE ?";
            $params[] = "%$filterProvider%";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $benefits = $stmt->fetchAll();

        // Lista de vínculos atuais com colaboradores
        $ebStmt = $this->pdo->prepare(
            "SELECT eb.*, e.full_name as employee_name, e.cpf, b.name as benefit_name, b.type, b.value_cents
             FROM employee_benefits eb
             JOIN employees e ON eb.employee_id = e.id
             JOIN benefits b ON eb.benefit_id = b.id
             WHERE eb.tenant_id = ? AND eb.status = 'ACTIVE'
             ORDER BY eb.created_at DESC"
        );
        $ebStmt->execute([$activeTenantId]);
        $assignedBenefits = $ebStmt->fetchAll();

        // Fatura de Coparticipação Calculada (SE_DEMONSTRATIVO_BENEFICIOS / SE_FATURA_COPARTICIPACAO)
        $copayStatement = $this->calculateCopayStatement($activeTenantId);

        // Colaboradores para o modal de vinculação
        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        if (function_exists('logQuery')) {
            logQuery("SELECT FROM benefits [CE_CATALOGO_BENEFICIOS & SE_DEMONSTRATIVO_BENEFICIOS]", [$activeTenantId], "SE_DEMONSTRATIVO_BENEFICIOS");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/benefits/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function store(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $name  = trim($_POST['name'] ?? '');
        $type  = $_POST['type'] ?? 'MEAL_VOUCHER';
        $val   = (float)($_POST['value'] ?? 500);
        $disc  = (float)($_POST['employee_cost_share_percentage'] ?? 6.0);
        $provider = trim($_POST['provider'] ?? 'Provedor Corporativo');

        if (empty($name)) {
            $_SESSION['flash_error'] = "Nome do benefício obrigatório!";
            header("Location: index.php?route=benefits");
            exit;
        }

        $id = 'ben-' . uniqid();
        $valCents = (int)($val * 100);

        $stmt = $this->pdo->prepare(
            "INSERT INTO benefits (id, tenant_id, type, name, provider, value_cents, employee_cost_share_percentage, is_deductible)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->execute([$id, $activeTenantId, $type, $name, $provider, $valCents, $disc]);

        $_SESSION['flash_message'] = "Novo benefício corporativo registrado no catálogo!";
        header("Location: index.php?route=benefits");
        exit;
    }

    /**
     * EE_VINCULAR_BENEFICIO — Vincula um benefício do catálogo a um colaborador específico.
     */
    public function assign(): void {
        $empId = $_POST['employee_id'] ?? '';
        $benId = $_POST['benefit_id'] ?? '';
        $customShare = isset($_POST['custom_cost_share']) && $_POST['custom_cost_share'] !== '' ? (float)$_POST['custom_cost_share'] : null;

        if (empty($empId) || empty($benId)) {
            $_SESSION['flash_error'] = "Selecione o colaborador e o benefício!";
            header("Location: index.php?route=benefits");
            exit;
        }

        $empStmt = $this->pdo->prepare("SELECT tenant_id FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $tenantId = $empStmt->fetchColumn() ?: 'tenant-tech';

        $id = 'eb-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');

        $stmt = $this->pdo->prepare(
            "INSERT INTO employee_benefits (id, tenant_id, employee_id, benefit_id, enrolled_at, status, custom_cost_share, created_at)
             VALUES (?, ?, ?, ?, ?, 'ACTIVE', ?, ?)"
        );
        $stmt->execute([$id, $tenantId, $empId, $benId, date('Y-m-d'), $customShare, $now]);

        if (function_exists('logQuery')) {
            logQuery(
                "INSERT INTO employee_benefits (id, employee_id='$empId', benefit_id='$benId')", 
                [$id], 
                "EE_VINCULAR_BENEFICIO",
                "View benefits ➔ Router (?route=benefits&action=assign) ➔ BenefitController::assign() ➔ SQLite: employee_benefits"
            );
        }

        $_SESSION['flash_message'] = "Benefício vinculado com sucesso ao colaborador!";
        header("Location: index.php?route=benefits");
        exit;
    }

    public function delete(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("DELETE FROM benefits WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = "Benefício excluído do catálogo com sucesso!";
        }
        header("Location: index.php?route=benefits");
        exit;
    }

    /**
     * SE_DEMONSTRATIVO_BENEFICIOS — Fatura de Coparticipação com aplicação estrita dos tetos VT e PAT.
     *
     * Regras Legais:
     * 1. Vale Transporte (Lei 7.418/85): Teto máximo de desconto de 6% sobre o salário-base; excedente é custeado pelo empregador.
     * 2. Programa de Alimentação do Trabalhador - PAT: Coparticipação do empregado limitada ao teto legal de 20% do valor do benefício.
     * 3. Planos de Saúde ANS: Coparticipação percentual sobre consultas e exames ambulatoriais.
     *
     * @param string $tenantId
     * @return array<string, mixed>
     */
    public function calculateCopayStatement(string $tenantId = 'tenant-tech'): array {
        $stmt = $this->pdo->prepare(
            "SELECT eb.*, e.full_name as employee_name, e.cpf, e.base_salary_cents,
                    b.name as benefit_name, b.type as benefit_type, b.value_cents, b.employee_cost_share_percentage, b.provider
             FROM employee_benefits eb
             JOIN employees e ON eb.employee_id = e.id
             JOIN benefits b ON eb.benefit_id = b.id
             WHERE eb.tenant_id = ? AND eb.status = 'ACTIVE'
             ORDER BY e.full_name ASC"
        );
        $stmt->execute([$tenantId]);
        $rows = $stmt->fetchAll();

        $employeeSummaries = [];
        $grandTotalFaceValue = 0.0;
        $grandTotalEmployeeShare = 0.0;
        $grandTotalEmployerSubsidy = 0.0;

        foreach ($rows as $r) {
            $empId = $r['employee_id'];
            $baseSalary = ((int)$r['base_salary_cents']) / 100.0;
            $faceValue = ((int)$r['value_cents']) / 100.0;
            $type = $r['benefit_type'];
            
            $configuredShare = $r['custom_cost_share'] !== null ? (float)$r['custom_cost_share'] : (float)$r['employee_cost_share_percentage'];

            $employeeCost = 0.0;
            $employerCost = $faceValue;
            $legalCapApplied = 'N/A';

            if ($type === 'TRANSPORT') {
                // Teto legal de 6% do salário base (Lei 7.418/85)
                $maxLegalDeduction = round($baseSalary * 0.06, 2);
                if ($faceValue > $maxLegalDeduction) {
                    $employeeCost = $maxLegalDeduction;
                    $employerCost = round($faceValue - $maxLegalDeduction, 2);
                    $legalCapApplied = "Teto Legal de 6% do Salário Base (R$ {$maxLegalDeduction})";
                } else {
                    $employeeCost = $faceValue;
                    $employerCost = 0.0;
                    $legalCapApplied = 'Desconto Integral (Abaixo do Teto de 6%)';
                }
            } elseif ($type === 'MEAL_VOUCHER' || $type === 'FOOD_VOUCHER') {
                // Teto de 20% do PAT
                $effectiveRate = min(20.0, max(0.0, $configuredShare)) / 100.0;
                $employeeCost = round($faceValue * $effectiveRate, 2);
                $employerCost = round($faceValue - $employeeCost, 2);
                $legalCapApplied = 'Teto Legal PAT de 20% do Valor Facial';
            } elseif ($type === 'HEALTH_PLAN') {
                $effectiveRate = max(0.0, $configuredShare) / 100.0;
                $employeeCost = round($faceValue * $effectiveRate, 2);
                $employerCost = round($faceValue - $employeeCost, 2);
                $legalCapApplied = 'Coparticipação Contratual TISS ANS (' . ($effectiveRate * 100) . '%)';
            } else {
                $effectiveRate = max(0.0, $configuredShare) / 100.0;
                $employeeCost = round($faceValue * $effectiveRate, 2);
                $employerCost = round($faceValue - $employeeCost, 2);
                $legalCapApplied = 'Coparticipação Padrão (' . ($effectiveRate * 100) . '%)';
            }

            if (!isset($employeeSummaries[$empId])) {
                $employeeSummaries[$empId] = [
                    'employee_id' => $empId,
                    'employee_name' => $r['employee_name'],
                    'cpf' => $r['cpf'],
                    'base_salary' => $baseSalary,
                    'total_face_value' => 0.0,
                    'total_employee_deduction' => 0.0,
                    'total_company_subsidy' => 0.0,
                    'benefits' => []
                ];
            }

            $employeeSummaries[$empId]['total_face_value'] += $faceValue;
            $employeeSummaries[$empId]['total_employee_deduction'] += $employeeCost;
            $employeeSummaries[$empId]['total_company_subsidy'] += $employerCost;
            $employeeSummaries[$empId]['benefits'][] = [
                'benefit_name' => $r['benefit_name'],
                'type' => $type,
                'provider' => $r['provider'],
                'face_value' => $faceValue,
                'employee_deduction' => $employeeCost,
                'company_subsidy' => $employerCost,
                'rule_applied' => $legalCapApplied
            ];

            $grandTotalFaceValue += $faceValue;
            $grandTotalEmployeeShare += $employeeCost;
            $grandTotalEmployerSubsidy += $employerCost;
        }

        return [
            'tenant_id' => $tenantId,
            'total_active_enrollments' => count($rows),
            'employees_count' => count($employeeSummaries),
            'grand_total_face_value' => round($grandTotalFaceValue, 2),
            'grand_total_employee_deduction' => round($grandTotalEmployeeShare, 2),
            'grand_total_company_subsidy' => round($grandTotalEmployerSubsidy, 2),
            'employer_subsidy_percentage' => $grandTotalFaceValue > 0 ? round(($grandTotalEmployerSubsidy / $grandTotalFaceValue) * 100, 2) : 0,
            'legal_references' => [
                'VT' => 'Lei nº 7.418/1985 (Teto de 6% do salário-base)',
                'VR_VA' => 'Lei nº 6.321/1976 - Programa de Alimentação do Trabalhador (PAT - Teto de 20%)',
                'SAUDE' => 'Resoluções Normativas da Agência Nacional de Saúde Suplementar (ANS)'
            ],
            'statement_details' => array_values($employeeSummaries),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
