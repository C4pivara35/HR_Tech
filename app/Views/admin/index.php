<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <span class="badge bg-danger mb-2 text-uppercase">Painel Master do Administrador Global</span>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-gear-fill me-2 text-danger"></i>Gestão de Empresas (Tenants) & Linha de Produção (LPS)</h2>
            <p class="text-muted mb-0">Administração multi-tenant com banco compartilhado e matriz de variabilidade LPS com Feature Toggles dinâmicos.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-building me-1"></i>CRUD 1: Multi-Tenancy</span>
            <span class="badge bg-warning text-dark text-uppercase me-2"><i class="bi bi-sliders me-1"></i>Variabilidade LPS</span>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#tenantModal">
                <i class="bi bi-plus-circle me-1"></i>Nova Empresa (Tenant)
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

<!-- ==================== TABELA DE EMPRESAS (CRUD 1 - FERNANDO) ==================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-buildings text-danger me-2"></i>Empresas Contratantes Cadastradas (Isolamento Multi-Tenant)
            <span class="badge bg-secondary ms-2"><?= count($allTenants ?? []) ?> empresa(s)</span>
        </span>
        <span class="badge bg-primary">CRUD 1 / Fernando Lopes</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>Código Tenant</th>
                        <th>Razão Social / Nome Fantasia</th>
                        <th>CNPJ Oficial</th>
                        <th>Segmento LPS</th>
                        <th>Colaboradores</th>
                        <th>Status</th>
                        <th class="text-end">Ações de Gestão</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allTenants)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Nenhuma empresa contratante cadastrada no banco.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allTenants as $t): ?>
                            <tr>
                                <td>
                                    <code class="text-info fw-bold fs-6"><?= htmlspecialchars($t['id']) ?></code>
                                </td>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($t['corporate_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($t['trading_name'] ?? '') ?></small>
                                </td>
                                <td><?= htmlspecialchars($t['cnpj']) ?></td>
                                <td>
                                    <?php if ($t['segment'] === 'tech'): ?>
                                        <span class="badge bg-info text-dark"><i class="bi bi-laptop me-1"></i>Tecnologia</span>
                                    <?php elseif ($t['segment'] === 'industry'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-gear me-1"></i>Indústria</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="bi bi-cash-coin me-1"></i>Financeiro</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-light fw-bold"><?= $t['employee_count'] ?? 0 ?> ativos</span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Operacional</span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?route=switch_tenant&role=<?= urlencode($t['id']) ?>" class="btn btn-outline-primary" title="Alternar Contexto">
                                            <i class="bi bi-box-arrow-in-right me-1"></i>Acessar
                                        </a>
                                        <a href="index.php?route=admin&action=delete_tenant&id=<?= urlencode($t['id']) ?>" class="btn btn-outline-danger" title="Excluir Empresa" onclick="return confirm('Deseja realmente excluir a empresa <?= htmlspecialchars(addslashes($t['corporate_name'])) ?> e seus dados?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==================== MATRIZ DE FEATURE TOGGLES (LPS - NICHOLAS) ==================== -->
<div class="mb-3">
    <h4 class="text-white fw-bold mb-1"><i class="bi bi-toggles me-2 text-warning"></i>Matriz de Feature Toggles por Empresa (Variabilidade LPS)</h4>
    <p class="text-muted small">Ative ou desative qualquer funcionalidade individualmente para simular customizações contratuais em tempo de execução.</p>
</div>

<div class="row g-4">
    <?php foreach ($tenants as $tId => $tName): ?>
        <div class="col-12">
            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
                    <span class="text-white fw-semibold">
                        <?php if ($tId === 'tenant-tech'): ?>
                            <i class="bi bi-laptop text-info me-2"></i>
                        <?php elseif ($tId === 'tenant-ind'): ?>
                            <i class="bi bi-gear text-warning me-2"></i>
                        <?php else: ?>
                            <i class="bi bi-cash-coin text-success me-2"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($tName) ?>
                    </span>
                    <span class="badge bg-secondary"><?= strtoupper($tId) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead class="table-dark text-muted text-uppercase small">
                                <tr>
                                    <th>Feature Flag Key</th>
                                    <th>Descrição Funcional do Recurso</th>
                                    <th>Estado Atual</th>
                                    <th class="text-end">Ação em Tempo Real</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($featuresCatalog as $fKey => $fDesc): ?>
                                    <?php $isEnabled = $ftManager->isFeatureEnabled($fKey, null, null, $tId); ?>
                                    <tr>
                                        <td><code class="text-info fw-bold"><?= $fKey ?></code></td>
                                        <td class="text-light small"><?= htmlspecialchars($fDesc) ?></td>
                                        <td>
                                            <?php if ($isEnabled): ?>
                                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Habilitada</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Desabilitada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="index.php?route=admin&action=toggle_feature&tenant_id=<?= urlencode($tId) ?>&feature=<?= urlencode($fKey) ?>" class="btn <?= $isEnabled ? 'btn-outline-danger' : 'btn-outline-success' ?> btn-sm">
                                                <i class="bi <?= $isEnabled ? 'bi-toggle-on' : 'bi-toggle-off' ?> me-1"></i>
                                                <?= $isEnabled ? 'Desativar' : 'Ativar' ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Cadastrar Nova Empresa -->
<div class="modal fade" id="tenantModal" tabindex="-1" aria-labelledby="tenantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="tenantModalLabel">
                    <i class="bi bi-building text-danger me-2"></i>Cadastrar Nova Empresa (Tenant)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=admin&action=create_tenant" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="corporate_name" class="form-label text-light">Razão Social da Empresa <span class="text-danger">*</span></label>
                        <input type="text" name="corporate_name" id="corporate_name" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Nexus Inovação e Tecnologia S.A." required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="trading_name" class="form-label text-light">Nome Fantasia</label>
                            <input type="text" name="trading_name" id="trading_name" class="form-control bg-dark text-white border-secondary" placeholder="Ex: NexusTech">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="cnpj" class="form-label text-light">CNPJ <span class="text-danger">*</span></label>
                            <input type="text" name="cnpj" id="cnpj" class="form-control bg-dark text-white border-secondary" placeholder="00.000.000/0000-00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="segment" class="form-label text-light">Segmento de Mercado (Linha de Produção LPS) <span class="text-danger">*</span></label>
                        <select name="segment" id="segment" class="form-select bg-dark text-white border-secondary" required>
                            <option value="tech">Tecnologia (Banco de Horas, Benefícios Flexíveis Caju, OKRs)</option>
                            <option value="industry">Indústria & Manufatura (HE 50%/100%, EPI NR-6 / ASO NR-7, Fretado)</option>
                            <option value="financial">Financeiro & Seguros (Ponto Biométrico Portaria 671, FinCorp Life, Bônus KPI)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-check2-circle me-1"></i>Cadastrar Empresa</button>
                </div>
            </form>
        </div>
    </div>
</div>
