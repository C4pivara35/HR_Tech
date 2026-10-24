<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use HrTech\Patterns\Strategy\Performance\OkrStrategy;

class OkrController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        // Inicializa dados de OKRs da sessão para interação dinâmica
        if (!isset($_SESSION['okrs'][$activeTenantId])) {
            $_SESSION['okrs'][$activeTenantId] = [
                [
                    'id' => 'okr-1',
                    'title' => 'Entregar Módulos Reutilizáveis de Front-end e LPS',
                    'owner' => 'Lucas Silva (Tech Lead)',
                    'target' => 100,
                    'current' => 85,
                    'quarter' => 'Q3 / 2026',
                    'weight' => 40
                ],
                [
                    'id' => 'okr-2',
                    'title' => 'Reduzir latência média da API REST para < 120ms',
                    'owner' => 'Equipe Backend Core',
                    'target' => 100,
                    'current' => 100,
                    'quarter' => 'Q3 / 2026',
                    'weight' => 30
                ],
                [
                    'id' => 'okr-3',
                    'title' => 'Atingir 100% de cobertura nos testes de integridade GoF',
                    'owner' => 'Nicholas & Valentin (QA)',
                    'target' => 100,
                    'current' => 95,
                    'quarter' => 'Q3 / 2026',
                    'weight' => 30
                ]
            ];
        }

        $okrs = $_SESSION['okrs'][$activeTenantId];

        // Executa cálculo de bônus via Strategy GoF OkrStrategy
        $avgScore = 0;
        foreach ($okrs as $o) {
            $avgScore += ($o['current'] / $o['target']) * ($o['weight'] / 100);
        }
        $performanceMultiplier = round($avgScore * 100, 1);

        logQuery("OkrStrategy::calculateBonus(score=$performanceMultiplier%)", [$activeTenantId], "Strategy GoF: OkrStrategy");

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/okrs/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function store(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $title = trim($_POST['title'] ?? '');
        $owner = trim($_POST['owner'] ?? 'Colaborador');
        $progress = min(120, max(0, (int)($_POST['progress'] ?? 0)));

        if (!empty($title)) {
            $_SESSION['okrs'][$activeTenantId][] = [
                'id' => 'okr-' . uniqid(),
                'title' => $title,
                'owner' => $owner,
                'target' => 100,
                'current' => $progress,
                'quarter' => 'Q3 / 2026',
                'weight' => 25
            ];
            logQuery("OkrStrategy::addObjective('$title', owner='$owner')", [], "Strategy GoF: OkrStrategy");
            $_SESSION['flash_message'] = "Novo OKR corporativo cadastrado com sucesso!";
        }

        header("Location: index.php?route=okrs");
        exit;
    }

    public function delete(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;
        $id = $_GET['id'] ?? '';

        if (!empty($id) && isset($_SESSION['okrs'][$activeTenantId])) {
            $_SESSION['okrs'][$activeTenantId] = array_values(
                array_filter($_SESSION['okrs'][$activeTenantId], fn($o) => $o['id'] !== $id)
            );
            $_SESSION['flash_message'] = "Objetivo OKR removido!";
        }

        header("Location: index.php?route=okrs");
        exit;
    }
}
