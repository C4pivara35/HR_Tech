<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-broadcast-pin me-2 text-info"></i>Central de Integrações & Simuladores dos 10 AIEs</h2>
            <p class="text-muted mb-0">Monitoramento e disparo interativo de todos os 10 Arquivos de Interface Externa (AIE) catalogados no APF (58 PFB).</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-diagram-3 me-1"></i>10 AIEs 100% Cobertos</span>
            <span class="badge bg-success text-uppercase"><i class="bi bi-shield-check me-1"></i>IFPUG / NESMA Padrão</span>
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

<!-- 10 AIEs Interactive Catalog Cards -->
<div class="row g-3 mb-4">
    <?php foreach ($catalog as $key => $aie): 
        $st = $serviceStats[$key] ?? ['total_calls' => 0, 'avg_latency' => 0, 'last_call' => null, 'successful_calls' => 0];
        $totalCalls = (int)$st['total_calls'];
        $avgLat = round((float)($st['avg_latency'] ?? 0), 1);
        $successRate = $totalCalls > 0 ? round(((int)$st['successful_calls'] / $totalCalls) * 100) : 100;
    ?>
        <div class="col-lg-6">
            <div class="card bg-dark border-secondary h-100 aie-panel">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center py-3">
                    <div>
                        <span class="badge bg-info text-dark me-2"><?= htmlspecialchars($aie['ifpug_code']) ?></span>
                        <span class="text-white fw-bold"><?= htmlspecialchars($aie['member']) ?></span>
                    </div>
                    <span class="aie-badge"><?= htmlspecialchars($aie['complexity']) ?></span>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="text-white fw-bold mb-2"><?= htmlspecialchars($aie['name']) ?></h6>
                        <p class="small text-muted mb-2"><?= htmlspecialchars($aie['description']) ?></p>
                        <div class="small mb-3">
                            <span class="text-muted">Endpoint: </span>
                            <code class="text-warning"><?= htmlspecialchars($aie['endpoint']) ?></code>
                        </div>
                    </div>

                    <div class="pt-3 border-top border-secondary d-flex justify-content-between align-items-center">
                        <div class="small">
                            <span class="text-muted d-block">Telemetria:</span>
                            <span class="text-light fw-bold"><?= $totalCalls ?> chamadas</span>
                            <span class="text-muted"> | Latência: <?= $avgLat ?>ms</span>
                            <span class="badge bg-success ms-1"><?= $successRate ?>% OK</span>
                        </div>
                        <a href="index.php?route=external_simulators&action=test&service=<?= urlencode($key) ?>" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-play-circle me-1"></i>Testar API
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Integration Logs Table -->
<div class="card bg-dark border-secondary shadow-sm">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-activity text-info me-2"></i>Trilha de Telemetria de Chamadas Externas (Últimas 40)
            <span class="badge bg-secondary ms-2"><?= count($integrationLogs) ?> registro(s)</span>
        </span>
        <div class="d-flex gap-2">
            <a href="index.php?route=external_simulators" class="btn btn-outline-secondary btn-sm">Ver Todas</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0 font-monospace small">
                <thead class="table-dark text-muted text-uppercase">
                    <tr>
                        <th style="font-family: inherit;">Data / Hora</th>
                        <th style="font-family: inherit;">Serviço AIE</th>
                        <th style="font-family: inherit;">Status HTTP</th>
                        <th style="font-family: inherit;">Latência</th>
                        <th style="font-family: inherit;">Identificador / Protocolo</th>
                        <th style="font-family: inherit;">Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($integrationLogs)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted font-sans">Nenhuma chamada externa registrada até o momento. Clique em "Testar API" nos cards acima.</td></tr>
                    <?php else: ?>
                        <?php foreach ($integrationLogs as $log): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-info"><?= htmlspecialchars($log['service_name']) ?></span>
                                </td>
                                <td>
                                    <?php if (($log['status'] ?? '') === 'SUCCESS' || (string)($log['status'] ?? '') === '200'): ?>
                                        <span class="badge bg-success">200 OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><?= htmlspecialchars((string)($log['status'] ?? 'N/A')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-warning"><?= (int)$log['duration_ms'] ?>ms</span>
                                </td>
                                <td>
                                    <span class="text-light"><?= htmlspecialchars($log['endpoint_url'] ?? '') ?></span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block text-muted" style="max-width: 250px;" title="<?= htmlspecialchars($log['response_payload'] ?? '') ?>">
                                        <?= htmlspecialchars(substr($log['response_payload'] ?? '', 0, 40)) ?>...
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
