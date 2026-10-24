<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Services\External\AiePixBacenService;

/**
 * ==============================================================================
 * PixController — Gateway de Liquidação Instantânea PIX BACEN (Tela 20)
 * ==============================================================================
 * Responsabilidade: Fernando Lopes Duarte
 *
 * Funções de APF Conectadas:
 * - AIE_PIX_BACEN_GATEWAY (Fernando Lopes Duarte - 5 PFB / Baixa Complexidade)
 *   Interface externa com o Sistema de Pagamentos Instantâneos (SPI) do BACEN:
 *   Geração de QR Code dinâmico EMVCo, payload Copia-e-Cola com CRC-16,
 *   liquidação instantânea em sub-segundo e emissão de comprovante bancário com End-to-End ID.
 * ==============================================================================
 */
class PixController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        // Transações PIX do tenant
        $stmt = $this->pdo->prepare(
            "SELECT px.*, ip.policy_number, ip.insurer_name, e.full_name as employee_name
             FROM pix_transactions px
             LEFT JOIN insurance_policies ip ON px.policy_id = ip.id
             LEFT JOIN employees e ON ip.employee_id = e.id
             WHERE px.tenant_id = ?
             ORDER BY px.created_at DESC"
        );
        $stmt->execute([$activeTenantId]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Apólices ativas para emissão de nova cobrança
        $polStmt = $this->pdo->prepare(
            "SELECT ip.*, e.full_name as employee_name
             FROM insurance_policies ip
             LEFT JOIN employees e ON ip.employee_id = e.id
             WHERE ip.tenant_id = ? AND ip.status = 'ACTIVE'
             ORDER BY ip.start_date DESC"
        );
        $polStmt->execute([$activeTenantId]);
        $policies = $polStmt->fetchAll(PDO::FETCH_ASSOC);

        // Se houver transação selecionada para inspeção
        $selectedTxId = $_GET['txid'] ?? ($transactions[0]['txid'] ?? null);
        $currentTx = null;
        if (!empty($selectedTxId)) {
            foreach ($transactions as $t) {
                if ($t['txid'] === $selectedTxId) {
                    $currentTx = $t;
                    break;
                }
            }
        }

        if (function_exists('logQuery')) {
            logQuery("SELECT FROM pix_transactions [AIE_PIX_BACEN_GATEWAY]", [$activeTenantId], "AIE_PIX_BACEN_GATEWAY");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/pix/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function generate(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        $policyId = $_POST['policy_id'] ?? null;
        $amount = (float)($_POST['amount'] ?? 150.0);
        $desc = trim($_POST['description'] ?? 'Cobrança Avulsa FinCorp');

        $amountCents = (int)round($amount * 100);

        $pixService = new AiePixBacenService($this->pdo);
        $billing = $pixService->generateDynamicPix($policyId, $amountCents, $desc, $activeTenantId);

        $_SESSION['flash_message'] = "Cobrança PIX gerada com sucesso! TxID: " . $billing['txid'];
        header("Location: index.php?route=pix&txid=" . urlencode($billing['txid']));
        exit;
    }

    public function simulateSettlement(): void {
        $txid = $_GET['txid'] ?? '';
        if (empty($txid)) {
            $_SESSION['flash_error'] = "Informe o TxID para liquidação!";
            header("Location: index.php?route=pix");
            exit;
        }

        $pixService = new AiePixBacenService($this->pdo);
        $result = $pixService->simulateInstantSettlement($txid);

        if ($result['status'] === 'SETTLED') {
            $_SESSION['flash_message'] = "LIQUIDAÇÃO INSTANTÂNEA CONCLUÍDA NO SPI BACEN! EndToEndId: {$result['end_to_end_id']} em {$result['latency_ms']}ms";
        } else {
            $_SESSION['flash_error'] = "Falha na liquidação PIX: " . ($result['message'] ?? 'Transação não encontrada');
        }

        header("Location: index.php?route=pix&txid=" . urlencode($txid));
        exit;
    }
}
