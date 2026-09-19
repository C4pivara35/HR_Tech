<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Ponto Eletrônico & Portaria 671 MTE (CRUD 5 & 6)</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Registros imutáveis com NSR sequencial e encadeamento de hash SHA-256 inviolável.</p>
  </div>
  <div style="display: flex; gap: 12px;">
    <button onclick="toggleModal('adjustmentModal')" class="btn btn-outline">
      <i class="fa-solid fa-pen-to-square"></i> Solicitar Retificação
    </button>
    <button onclick="toggleModal('punchModal')" class="btn btn-success">
      <i class="fa-solid fa-fingerprint"></i> Registrar Batida
    </button>
  </div>
</div>

<!-- Time Logs Table -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-shield-halved" style="color: var(--success);"></i> Espelho de Ponto Eletrônico (Cadeia SHA-256)</h3>
    <span class="badge badge-success"><i class="fa-solid fa-lock"></i> Portaria 671 MTE</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>NSR</th>
        <th>Colaborador</th>
        <th>Data / Hora Registro</th>
        <th>Tipo</th>
        <th>Hash Criptográfico SHA-256</th>
        <th>Status Validação</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($timeLogs)): ?>
        <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Nenhuma batida de ponto registrada ainda.</td></tr>
      <?php else: ?>
        <?php foreach ($timeLogs as $log): ?>
          <tr>
            <td><strong style="color: #fbbf24;">#<?= sprintf('%06d', $log['nsr']) ?></strong></td>
            <td>
              <strong style="color: #fff;"><?= htmlspecialchars($log['employee_name']) ?></strong>
              <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($log['cpf']) ?></div>
            </td>
            <td><?= date('d/m/Y H:i:s', strtotime($log['timestamp'])) ?></td>
            <td>
              <?php if ($log['type'] === 'ENTRY'): ?>
                <span class="badge badge-success">Entrada</span>
              <?php elseif ($log['type'] === 'EXIT'): ?>
                <span class="badge badge-danger">Saída</span>
              <?php else: ?>
                <span class="badge badge-warning">Intervalo</span>
              <?php endif; ?>
            </td>
            <td><code style="color: #38bdf8; font-size: 11px;"><?= substr($log['signature_hash'], 0, 24) ?>...</code></td>
            <td><span class="badge badge-success"><i class="fa-solid fa-check-double"></i> VÁLIDO</span></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Adjustments Requests Table -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-clock-rotate-left" style="color: var(--warning);"></i> Workflow de Solicitações de Ajuste</h3>
    <span class="badge badge-warning"><?= count($adjustments) ?> Solicitações</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Colaborador</th>
        <th>Horário Solicitado</th>
        <th>Justificativa</th>
        <th>Status Aprovação</th>
        <th style="text-align: right;">Ação do Gestor</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($adjustments)): ?>
        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Nenhuma solicitação de retificação pendente.</td></tr>
      <?php else: ?>
        <?php foreach ($adjustments as $adj): ?>
          <tr>
            <td><strong style="color: #fff;"><?= htmlspecialchars($adj['employee_name']) ?></strong></td>
            <td><?= date('d/m/Y H:i', strtotime($adj['requested_time'] ?? $adj['requested_date'] ?? 'now')) ?></td>
            <td><?= htmlspecialchars($adj['reason']) ?></td>
            <td>
              <?php if ($adj['status'] === 'APPROVED'): ?>
                <span class="badge badge-success">Aprovado</span>
              <?php else: ?>
                <span class="badge badge-warning">Pendente</span>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <?php if ($adj['status'] === 'PENDING'): ?>
                <a href="index.php?route=timelogs&action=approve_adjustment&id=<?= $adj['id'] ?>" class="btn btn-success" style="font-size: 11px; padding: 4px 8px;">
                  <i class="fa-solid fa-check"></i> Aprovar Ajuste
                </a>
              <?php else: ?>
                <span style="color: var(--text-muted); font-size: 12px;">Concluído</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Batida de Ponto -->
<div id="punchModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Registrar Marcação de Ponto</h3>
      <button onclick="toggleModal('punchModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=timelogs&action=punch" method="POST">
      <div class="form-group">
        <label class="form-label">Selecionar Colaborador *</label>
        <select name="employee_id" class="form-control" required>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?> (<?= $emp['cpf'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Tipo de Batida *</label>
        <select name="type" class="form-control">
          <option value="ENTRY">Entrada Turno</option>
          <option value="INTERVAL_START">Início Intervalo Almoço</option>
          <option value="INTERVAL_END">Fim Intervalo Almoço</option>
          <option value="EXIT">Saída Turno</option>
        </select>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('punchModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-fingerprint"></i> Gerar Hash SHA-256 & Bater</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Solicitação de Ajuste -->
<div id="adjustmentModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Solicitar Retificação de Marcação</h3>
      <button onclick="toggleModal('adjustmentModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=timelogs&action=request_adjustment" method="POST">
      <div class="form-group">
        <label class="form-label">Colaborador *</label>
        <select name="employee_id" class="form-control" required>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Horário Correto Pretendido *</label>
        <input type="datetime-local" name="requested_timestamp" class="form-control" required>
      </div>

      <div class="form-group">
        <label class="form-label">Justificativa Legal / Atestado *</label>
        <textarea name="reason" class="form-control" rows="3" placeholder="ex: Consulta médica no horário de entrada..." required></textarea>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('adjustmentModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-warning"><i class="fa-solid fa-paper-plane"></i> Enviar Solicitação</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id) {
  document.getElementById(id).classList.toggle('active');
}
</script>
