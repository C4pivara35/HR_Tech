<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>Central de Relatórios Gerenciais (Template Method)</h2>
            <p class="text-muted mb-0">Exportação dinâmica de relatórios em múltiplos formatos (PDF, CSV/Excel, JSON) isolados por tenant.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-diagram-3 me-1"></i>Template Method</span>
            <span class="badge bg-success text-uppercase"><i class="bi bi-shield-check me-1"></i>Multi-Tenant</span>
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

<div class="row g-4">
    <!-- PDF Report Card -->
    <div class="col-md-4">
        <div class="card bg-dark border-secondary h-100 text-center p-4 shadow-sm">
            <div class="rounded-circle bg-danger bg-opacity-25 text-danger d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                <i class="bi bi-file-earmark-pdf fs-1"></i>
            </div>
            <h4 class="text-white fw-bold mb-2">Relatório Executivo PDF</h4>
            <p class="text-muted small mb-4">Exportação de síntese gerencial com formatação padrão corporativa, quadro de colaboradores e remunerações.</p>
            <div class="mt-auto">
                <a href="index.php?route=reports&action=export&format=pdf" class="btn btn-danger w-100 fw-bold">
                    <i class="bi bi-download me-2"></i>Gerar e Baixar PDF
                </a>
            </div>
        </div>
    </div>

    <!-- CSV / Excel Card -->
    <div class="col-md-4">
        <div class="card bg-dark border-secondary h-100 text-center p-4 shadow-sm">
            <div class="rounded-circle bg-success bg-opacity-25 text-success d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                <i class="bi bi-file-earmark-spreadsheet fs-1"></i>
            </div>
            <h4 class="text-white fw-bold mb-2">Planilha Consolidada CSV</h4>
            <p class="text-muted small mb-4">Tabela bruta com delimitação RFC-4180 de colaboradores, admissões e CPFs para conciliação em Excel ou BI.</p>
            <div class="mt-auto">
                <a href="index.php?route=reports&action=export&format=csv" class="btn btn-success w-100 fw-bold">
                    <i class="bi bi-download me-2"></i>Exportar CSV / Excel
                </a>
            </div>
        </div>
    </div>

    <!-- JSON API Card -->
    <div class="col-md-4">
        <div class="card bg-dark border-secondary h-100 text-center p-4 shadow-sm">
            <div class="rounded-circle bg-info bg-opacity-25 text-info d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                <i class="bi bi-file-earmark-code fs-1"></i>
            </div>
            <h4 class="text-white fw-bold mb-2">Estrutura de Dados JSON</h4>
            <p class="text-muted small mb-4">Exportação de payload canônico estruturado em JSON para ingestão de dados em pipelines e integrações REST.</p>
            <div class="mt-auto">
                <a href="index.php?route=reports&action=export&format=json" class="btn btn-outline-info w-100 fw-bold">
                    <i class="bi bi-download me-2"></i>Exportar JSON Canônico
                </a>
            </div>
        </div>
    </div>
</div>
