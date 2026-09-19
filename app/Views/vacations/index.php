<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Gestão de Férias CLT (CRUD 7)</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Controle de períodos aquisitivos/concessivos, abono pecuniário e solicitações.</p>
  </div>
  <button onclick="toggleModal('vacationModal')" class="btn btn-warning">
    <i class="fa-solid fa-umbrella-beach"></i> Agendar Férias
  </button>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-calendar-days" style="color: var(--warning);"></i> Solicitantes de Férias</h3>
    <span class="badge badge-warning"><?= count($vacations) ?> Registros</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Colaborador</th>
        <th>Período Solicitado</th>
        <th>Dias</th>
        <th>Saldo Duração Aquisitiva</th>
        <th>Status</th>
        <th style="text-align: right;">Ação</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($vacations)): ?>
        <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Nenhuma solicitação de férias cadastrada.</td></tr>
      <?php else: ?>
        <?php foreach ($vacations as $vac): ?>
          <tr>
            <td><strong style="color: #fff;"><?= htmlspecialchars($vac['employee_name']) ?></strong></td>
            <td>
              <?= date('d/m/Y', strtotime($vac['start_date'])) ?> até <?= date('d/m/Y', strtotime($vac['end_date'])) ?>
            </td>
            <td><strong style="color: #fbbf24;"><?= $vac['duration_days'] ?? 15 ?> dias</strong></td>
            <td><span class="badge badge-info"><?= $vac['vacation_days_balance'] ?? 30 ?> dias restantes</span></td>
            <td>
              <?php if ($vac['status'] === 'APPROVED'): ?>
                <span class="badge badge-success">Aprovadas</span>
              <?php else: ?>
                <span class="badge badge-warning">Em Análise RH</span>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <?php if ($vac['status'] !== 'APPROVED'): ?>
                <a href="index.php?route=vacations&action=approve&id=<?= $vac['id'] ?>" class="btn btn-success" style="font-size: 11px; padding: 4px 8px;">
                  <i class="fa-solid fa-check"></i> Aprovar Férias
                </a>
              <?php else: ?>
                <span style="color: var(--text-muted); font-size: 12px;">Processado</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Agendar Férias -->
<div id="vacationModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Agendamento de Férias CLT</h3>
      <button onclick="toggleModal('vacationModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=vacations&action=request" method="POST">
      <div class="form-group">
        <label class="form-label">Colaborador Elegível *</label>
        <select name="employee_id" class="form-control" required>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?> (Saldo: <?= $emp['vacation_days_balance'] ?? 30 ?> dias)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Data de Início *</label>
          <input type="date" name="start_date" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Quantidade de Dias *</label>
          <select name="days_count" class="form-control">
            <option value="30">30 Dias (Período Integral)</option>
            <option value="15">15 Dias (Primeiro Período)</option>
            <option value="10">10 Dias (Fracionamento Legal)</option>
          </select>
        </div>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('vacationModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-warning"><i class="fa-solid fa-floppy-disk"></i> Confirmar Agendamento</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id) {
  document.getElementById(id).classList.toggle('active');
}
</script>
