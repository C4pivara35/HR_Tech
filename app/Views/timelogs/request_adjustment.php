<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-clock-history me-2 text-primary"></i>Solicitar Retificação / Abono de Ponto</h2>
            <p class="text-muted mb-0">Submissão de retificações de jornada conforme Portaria MTE nº 671/2021 com trilha de auditoria.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-tag-fill me-1"></i>EE_AJUSTE_ABONO_PONTO (4 PFB)</span>
            <a href="index.php?route=timelogs" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar ao Ponto</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card bg-dark border-secondary shadow-sm">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center py-3">
                <span class="text-white fw-semibold"><i class="bi bi-pencil-square me-2 text-warning"></i>Formulário de Retificação de Marcação</span>
                <span class="badge bg-warning text-dark"><i class="bi bi-shield-check me-1"></i>Portaria 671 / CLT</span>
            </div>
            <div class="card-body p-4">
                <form action="index.php?route=timelogs&action=request_adjustment" method="POST">
                    <div class="mb-3">
                        <label for="employee_id" class="form-label text-light">Colaborador Solicitante <span class="text-danger">*</span></label>
                        <select name="employee_id" id="employee_id" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Selecione o colaborador...</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= htmlspecialchars($emp['id']) ?>">
                                    <?= htmlspecialchars($emp['full_name']) ?> (Matrícula: <?= htmlspecialchars($emp['matricula'] ?? 'N/A') ?> — CPF: <?= htmlspecialchars($emp['cpf'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="requested_timestamp" class="form-label text-light">Data e Horário Pretendido <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control bg-dark text-white border-secondary" id="requested_timestamp" name="requested_timestamp" value="<?= date('Y-m-d\T08:00') ?>" required>
                            <div class="form-text text-muted">Horário ajustado segundo a Hora Legal Brasileira (NTP.br).</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="punch_type" class="form-label text-light">Tipo de Marcação</label>
                            <select class="form-select bg-dark text-white border-secondary" id="punch_type" name="type">
                                <option value="ENTRY">Entrada Inicial (E1)</option>
                                <option value="INTERVAL_START">Saída Almoço / Intervalo (S1)</option>
                                <option value="INTERVAL_END">Retorno Almoço / Intervalo (E2)</option>
                                <option value="EXIT">Saída Final (S2)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="reason" class="form-label text-light">Justificativa Legal / Motivo <span class="text-danger">*</span></label>
                        <textarea class="form-control bg-dark text-white border-secondary" id="reason" name="reason" rows="4" placeholder="Ex: Esquecimento de registro na entrada; falha de biometria no terminal físico; consulta médica comprovada via atestado anexo..." required></textarea>
                        <div class="form-text text-muted">A justificativa constará permanentemente no espelho fiscal e no comprovante do trabalhador.</div>
                    </div>

                    <div class="p-3 mb-4 rounded bg-black border border-secondary">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle text-info fs-4 me-3"></i>
                            <div class="small text-muted">
                                <strong class="text-light">Fluxo de Aprovação:</strong> Após o envio, a solicitação ficará com status <span class="badge bg-warning text-dark">PENDING</span> e será despachada pelo gestor na <a href="index.php?route=approvals" class="text-info text-decoration-none">Central de Aprovações (Tela 11)</a>. Uma vez aprovada, a marcação receberá novo NSR e assinatura SHA-256.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php?route=timelogs" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send me-1"></i>Enviar para Homologação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
