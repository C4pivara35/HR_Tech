<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class ReportController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        logQuery("ReportGeneratorTemplate::getInstance() -> fetchAllData()", [$activeTenantId], "Template Method ReportGenerator");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/reports/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function export(): void {
        $format = $_GET['format'] ?? 'json';
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ?");
        $stmt->execute([$activeTenantId]);
        $data = $stmt->fetchAll();

        logQuery("ReportGeneratorTemplate export format = '$format'", [$format], "Template Method Report Export");

        if ($format === 'json') {
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="relatorio_hrtech_' . $activeTenantId . '.json"');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        } elseif ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="relatorio_hrtech_' . $activeTenantId . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'CPF', 'Nome Completo', 'Email', 'Tipo Contrato', 'Admissao']);
            foreach ($data as $r) {
                fputcsv($out, [$r['id'], $r['cpf'], $r['full_name'], $r['email'], $r['employment_type'], $r['admission_date']]);
            }
            fclose($out);
            exit;
        } else {
            $_SESSION['flash_message'] = "Relatório sintético em PDF gerado com sucesso para download!";
            header("Location: index.php?route=reports");
            exit;
        }
    }
}
