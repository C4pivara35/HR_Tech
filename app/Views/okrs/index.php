<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-bullseye me-2 text-primary"></i>Avaliação de Desempenho & OKRs (Padrão Strategy)</h2>
            <p class="text-muted mb-0">Gestão de metas ágeis trimestrais e cálculo do multiplicador de bônus via <code>OkrStrategy</code>.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-award me-1"></i>Strategy GoF</span>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#okrModal">
                <i class="bi bi-plus-circle me-1"></i>Novo OKR
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

<!-- Performance Score Banner -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="text-info fw-bold small text-uppercase">Atingimento Médio Ponderado (Q3 / 2026)</span>
            <h1 class="display-5 text-white fw-bold mb-1"><?= $performanceMultiplier ?>%</h1>
            <p class="text-muted small mb-0">Multiplicador atuarial calculado via algoritmo <code>OkrStrategy::calculateBonusMultiplier()</code>.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-success p-3 fs-6">
                <i class="bi bi-trophy-fill me-2"></i>Bônus Elegível: 1.2x Salário Base
            </span>
        </div>
    </div>
</div>

<!-- OKR List Table -->
<div class="card bg-dark border-secondary shadow-sm">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-list-task text-primary me-2"></i>Objetivos Estratégicos e Key Results Cadastrados
            <span class="badge bg-secondary ms-2"><?= count($okrs) ?> objetivo(s)</span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>Objetivo Estratégico (Key Result)</th>
                        <th>Líder / Responsável</th>
                        <th>Ciclo</th>
                        <th style="min-width: 220px;">Progresso</th>
                        <th>Peso</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($okrs as $okr): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-white"><?= htmlspecialchars($okr['title']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-info"><?= htmlspecialchars($okr['owner']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($okr['quarter']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1 bg-black border border-secondary" style="height: 10px;">
                                        <div class="progress-bar <?= $okr['current'] >= 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= min(100, $okr['current']) ?>%;" aria-valuenow="<?= $okr['current'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small fw-bold <?= $okr['current'] >= 100 ? 'text-success' : 'text-white' ?>"><?= $okr['current'] ?>%</span>
                                </div>
                            </td>
                            <td><?= $okr['weight'] ?>%</td>
                            <td class="text-end">
                                <a href="index.php?route=okrs&action=delete&id=<?= urlencode($okr['id']) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Excluir este objetivo OKR?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Novo OKR -->
<div class="modal fade" id="okrModal" tabindex="-1" aria-labelledby="okrModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="okrModalLabel">
                    <i class="bi bi-bullseye text-primary me-2"></i>Cadastrar Novo OKR Corporativo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=okrs&action=store" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="title" class="form-label text-light">Título do Objetivo / Meta <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Migrar microsserviços para PHP 8.3" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="owner" class="form-label text-light">Líder / Responsável</label>
                            <input type="text" name="owner" id="owner" class="form-control bg-dark text-white border-secondary" value="Andryus Zolet (Tech Lead)">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="progress" class="form-label text-light">Progresso Atual (%)</label>
                            <input type="number" name="progress" id="progress" min="0" max="120" value="50" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Salvar OKR</button>
                </div>
            </form>
        </div>
    </div>
</div>
