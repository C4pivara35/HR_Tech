<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Segurança do Trabalho, EPIs & ASO (CRUD 9)</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Conformidade estrita NR-6 (Ficha de EPI) e NR-7 (Atestados de Saúde Ocupacional) com trava operacional.</p>
  </div>
  <div style="display: flex; gap: 12px;">
    <button onclick="toggleModal('asoModal')" class="btn btn-warning">
      <i class="fa-solid fa-notes-medical"></i> Registrar Exame ASO
    </button>
    <button onclick="toggleModal('epiModal')" class="btn" style="background: #f59e0b; color: #000;">
      <i class="fa-solid fa-shield"></i> Entregar EPI (NR-6)
    </button>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-hard-hat" style="color: #f59e0b;"></i> Fichas de EPI & Atestados Médicos ASO</h3>
    <span class="badge badge-warning"><?= count($safetyRecords) ?> Registros de Segurança</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Tipo de Registro</th>
        <th>Colaborador</th>
        <th>Item / Exame</th>
        <th>CA (Certificado de Aprovação)</th>
        <th>Data Emissão / Validade</th>
        <th>Status de Aptidão Clínica</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($safetyRecords)): ?>
        <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Nenhum registro de EPI ou ASO cadastrado.</td></tr>
      <?php else: ?>
        <?php foreach ($safetyRecords as $sec): ?>
          <tr>
            <td>
              <?php if ($sec['record_type'] === 'EPI'): ?>
                <span class="badge badge-warning"><i class="fa-solid fa-shield"></i> EPI NR-6</span>
              <?php else: ?>
                <span class="badge badge-info"><i class="fa-solid fa-stethoscope"></i> ASO NR-7</span>
              <?php endif; ?>
            </td>
            <td>
              <strong style="color: #fff;"><?= htmlspecialchars($sec['employee_name']) ?></strong>
              <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($sec['cpf']) ?></div>
            </td>
            <td><strong style="color: #fff;"><?= htmlspecialchars($sec['item_name']) ?></strong></td>
            <td><code style="color: #38bdf8;"><?= htmlspecialchars($sec['ca_number']) ?></code></td>
            <td>
              <?= date('d/m/Y', strtotime($sec['issue_date'])) ?> 
              (Val: <span style="color: #fbbf24;"><?= date('d/m/Y', strtotime($sec['expiration_date'])) ?></span>)
            </td>
            <td>
              <?php if ($sec['is_fit']): ?>
                <span class="badge badge-success"><i class="fa-solid fa-user-check"></i> APTO PARA OPERAÇÃO</span>
              <?php else: ?>
                <span class="badge badge-danger"><i class="fa-solid fa-user-xmark"></i> INAPTO / ALOCAÇÃO BLOQUEADA</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Entregar EPI -->
<div id="epiModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Entregar Equipamento de Proteção (NR-6)</h3>
      <button onclick="toggleModal('epiModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=safety&action=deliver_equipment" method="POST">
      <div class="form-group">
        <label class="form-label">Colaborador *</label>
        <select name="employee_id" class="form-control" required>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Descrição do EPI *</label>
        <input type="text" name="item_name" class="form-control" placeholder="ex: Capacete com Protetor Auricular 3M" required>
      </div>

      <div class="form-group">
        <label class="form-label">Número do CA (Ministério do Trabalho) *</label>
        <input type="text" name="ca_number" class="form-control" placeholder="CA-45920" required>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('epiModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn" style="background: #f59e0b; color: #000;"><i class="fa-solid fa-floppy-disk"></i> Registrar Entrega com Termo</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Exame ASO -->
<div id="asoModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Registrar Atestado Médico ASO (NR-7)</h3>
      <button onclick="toggleModal('asoModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=safety&action=record_exam" method="POST">
      <div class="form-group">
        <label class="form-label">Colaborador Avaliado *</label>
        <select name="employee_id" class="form-control" required>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Tipo de Exame Clínico</label>
        <select name="exam_type" class="form-control">
          <option value="ADMISSIONAL">Admissional</option>
          <option value="PERIODIC">Periódico Anual</option>
          <option value="DEMISSIONAL">Demissional</option>
        </select>
      </div>

      <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
        <input type="checkbox" name="is_fit" id="is_fit" value="1" checked style="width: 18px; height: 18px;">
        <label for="is_fit" class="form-label" style="margin: 0; color: #fff;">Colaborador Clinicamente APTO para a Função</label>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('asoModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-warning"><i class="fa-solid fa-stethoscope"></i> Salvar ASO</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id) {
  document.getElementById(id).classList.toggle('active');
}
</script>
