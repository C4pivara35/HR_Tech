<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-qr-code me-2 text-warning"></i>Gateway de Liquidação Instantânea PIX BACEN</h2>
            <p class="text-muted mb-0">Integração externa oficial com o Sistema de Pagamentos Instantâneos (SPI) do Banco Central do Brasil.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-tag-fill me-1"></i>AIE_PIX_BACEN_GATEWAY (5 PFB)</span>
            <span class="badge bg-success text-uppercase me-2"><i class="bi bi-lightning-charge me-1"></i>SPI Sub-Segundo</span>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#newPixModal">
                <i class="bi bi-plus-circle me-1"></i>Nova Cobrança Dinâmica
            </button>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['flash_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Interactive Terminal: Current Inspected Transaction -->
<?php if (!empty($currentTx)): ?>
<div class="card bg-dark border-secondary shadow-sm mb-4 aie-panel">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-terminal text-warning me-2"></i>Terminal de Liquidação SPI BACEN — Cobrança Selecionada
        </span>
        <span class="badge <?= $currentTx['status'] === 'SETTLED' ? 'bg-success' : 'bg-warning text-dark' ?> fs-6">
            <i class="bi <?= $currentTx['status'] === 'SETTLED' ? 'bi-check-all' : 'bi-hourglass-split' ?> me-1"></i>
            <?= $currentTx['status'] === 'SETTLED' ? 'LIQUIDADO NO SPI (SETTLED)' : 'AGUARDANDO PAGAMENTO (PENDING)' ?>
        </span>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <!-- QR Code Box -->
            <div class="col-md-4 text-center mb-4 mb-md-0">
                <div class="bg-white p-3 rounded d-inline-block shadow">
                    <?php if (!empty($currentTx['qr_code_svg'])): ?>
                        <?= $currentTx['qr_code_svg'] ?>
                    <?php else: ?>
                        <div class="p-4 text-dark text-center" style="width: 200px; height: 200px; display: flex; flex-direction: column; justify-content: center; align-items: center; border: 2px dashed #000;">
                            <i class="bi bi-qr-code fs-1"></i>
                            <div class="small fw-bold mt-2">QR Code EMVCo</div>
                            <div class="small text-muted" style="font-size: 10px;">Padrão BACEN BR Code</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-2 small text-muted">Padrão EMVCo / BR Code com CRC-16</div>
            </div>

            <!-- Details & Actions -->
            <div class="col-md-8">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Identificador Único da Cobrança (TxID):</small>
                        <code class="text-info fw-bold fs-6"><?= htmlspecialchars($currentTx['txid']) ?></code>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Valor da Transação:</small>
                        <h4 class="text-success fw-bold mb-0">R$ <?= number_format(((int)$currentTx['amount_cents']) / 100, 2, ',', '.') ?></h4>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Vínculo com Apólice FinCorp:</small>
                        <span class="text-light"><?= htmlspecialchars($currentTx['policy_number'] ?? 'Cobrança Avulsa') ?></span>
                        <small class="text-muted d-block"><?= htmlspecialchars($currentTx['employee_name'] ?? '') ?></small>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Horário de Geração:</small>
                        <span class="text-light"><?= date('d/m/Y H:i:s', strtotime($currentTx['created_at'])) ?></span>
                    </div>
                </div>

                <!-- Copia e Cola Payload Box -->
                <div class="mb-3">
                    <label class="form-label text-light small fw-bold"><i class="bi bi-clipboard-data me-1"></i>Payload PIX Copia-e-Cola (EMVCo BR Code):</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-black text-warning border-secondary font-monospace small" id="emvPayloadInput" value="<?= htmlspecialchars($currentTx['emv_payload'] ?? '') ?>" readonly>
                        <button class="btn btn-outline-warning" type="button" onclick="navigator.clipboard.writeText(document.getElementById('emvPayloadInput').value); alert('Payload PIX copiado para a área de transferência!');">
                            <i class="bi bi-clipboard"></i> Copiar
                        </button>
                    </div>
                </div>

                <!-- Settlement Status & Action Button -->
                <?php if ($currentTx['status'] === 'SETTLED'): ?>
                    <div class="p-3 rounded bg-success bg-opacity-10 border border-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Comprovante de Liquidação Bancária SPI:</span>
                                <div class="small text-light mt-1">End-to-End ID: <code class="text-info"><?= htmlspecialchars($currentTx['end_to_end_id'] ?? 'E0000000020261024...') ?></code></div>
                                <div class="small text-muted">Liquidado em: <?= date('d/m/Y H:i:s', strtotime($currentTx['settled_at'])) ?> (Latência SPI: <strong class="text-warning"><?= (int)($currentTx['latency_ms'] ?? 142) ?>ms</strong>)</div>
                            </div>
                            <span class="badge bg-success p-2">IRREVERSÍVEL</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-between p-3 rounded bg-black border border-warning">
                        <div>
                            <span class="text-warning fw-bold"><i class="bi bi-lightning-charge me-1"></i>Simulação de Pagamento Sub-Segundo:</span>
                            <div class="small text-muted">Executa o webhook assíncrono de liquidação junto ao Banco Central do Brasil.</div>
                        </div>
                        <a href="index.php?route=pix&action=simulate_settlement&txid=<?= urlencode($currentTx['txid']) ?>" class="btn btn-success fw-bold px-4">
                            <i class="bi bi-check-circle me-1"></i>Simular Pagamento no SPI
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Table of PIX Transactions -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-list-columns-reverse text-info me-2"></i>Histórico de Cobranças e Liquidações PIX
            <span class="badge bg-secondary ms-2"><?= count($transactions) ?> transação(ões)</span>
        </span>
        <span class="badge bg-primary">AIE_PIX_BACEN_GATEWAY (Fernando)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>TxID</th>
                        <th>Vínculo / Apólice</th>
                        <th>Valor (R$)</th>
                        <th>Status SPI</th>
                        <th>End-to-End ID BACEN</th>
                        <th>Latência</th>
                        <th>Criado Em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Nenhuma transação PIX registrada para este tenant.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr class="<?= (!empty($currentTx) && $currentTx['txid'] === $tx['txid']) ? 'table-active' : '' ?>">
                                <td>
                                    <code class="text-info fw-bold"><?= htmlspecialchars($tx['txid']) ?></code>
                                </td>
                                <td>
                                    <span class="text-white"><?= htmlspecialchars($tx['policy_number'] ?? 'Avulso FinCorp') ?></span>
                                    <small class="text-muted d-block"><?= htmlspecialchars($tx['employee_name'] ?? 'Segurado') ?></small>
                                </td>
                                <td>
                                    <span class="text-success fw-bold">R$ <?= number_format(((int)$tx['amount_cents']) / 100, 2, ',', '.') ?></span>
                                </td>
                                <td>
                                    <?php if ($tx['status'] === 'SETTLED'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>SETTLED</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>PENDING</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($tx['end_to_end_id'])): ?>
                                        <code class="small text-light" style="font-size: 11px;"><?= htmlspecialchars(substr($tx['end_to_end_id'], 0, 18)) ?>...</code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($tx['latency_ms'])): ?>
                                        <span class="badge bg-dark border border-secondary text-warning"><?= (int)$tx['latency_ms'] ?>ms</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></small>
                                </td>
                                <td class="text-end">
                                    <a href="index.php?route=pix&txid=<?= urlencode($tx['txid']) ?>" class="btn btn-outline-info btn-sm" title="Inspecionar no Terminal">
                                        <i class="bi bi-search"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Nova Cobrança Dinâmica PIX -->
<div class="modal fade" id="newPixModal" tabindex="-1" aria-labelledby="newPixModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="newPixModalLabel">
                    <i class="bi bi-qr-code text-warning me-2"></i>Emitir Cobrança Dinâmica PIX BACEN
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=pix&action=generate" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="policy_id" class="form-label text-light">Vincular a Apólice FinCorp (Opcional)</label>
                        <select name="policy_id" id="policy_id" class="form-select bg-dark text-white border-secondary">
                            <option value="">Cobrança Avulsa Sem Apólice</option>
                            <?php foreach ($policies as $pol): ?>
                                <option value="<?= htmlspecialchars($pol['id']) ?>">
                                    Apólice <?= htmlspecialchars($pol['policy_number']) ?> (<?= htmlspecialchars($pol['employee_name'] ?? 'Titular') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label text-light">Valor da Cobrança (R$) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" id="amount" class="form-control bg-dark text-white border-secondary" value="150.00" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label text-light">Descrição / Identificador da Cobrança</label>
                        <input type="text" name="description" id="description" class="form-control bg-dark text-white border-secondary" value="Pagamento Prêmio FinCorp">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-qr-code me-1"></i>Gerar Payload EMVCo</button>
                </div>
            </form>
        </div>
    </div>
</div>
