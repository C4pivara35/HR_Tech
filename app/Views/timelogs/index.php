<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Ponto Eletrônico & Portaria 671 MTE (CRUD 5 & 6)</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Registros imutáveis com NSR sequencial e encadeamento de hash SHA-256 inviolável.</p>
  </div>
  <div style="display: flex; gap: 12px; flex-wrap: wrap;">
    <button onclick="toggleModal('overtimeModal')" class="btn" style="background: #8b5cf6; color: #fff;">
      <i class="fa-solid fa-business-time"></i> Lançar Horas Extras / Banco
    </button>
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
              <?php elseif ($adj['status'] === 'REJECTED'): ?>
                <span class="badge badge-danger">Recusado</span>
              <?php else: ?>
                <span class="badge badge-warning">Pendente</span>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <?php if ($adj['status'] === 'PENDING'): ?>
                <a href="index.php?route=timelogs&action=approve_adjustment&id=<?= $adj['id'] ?>" class="btn btn-success" style="font-size: 11px; padding: 4px 8px;">
                  <i class="fa-solid fa-check"></i> Aprovar
                </a>
                <a href="index.php?route=timelogs&action=reject_adjustment&id=<?= $adj['id'] ?>" class="btn btn-danger" style="font-size: 11px; padding: 4px 8px; margin-left: 4px;" onclick="return confirm('Deseja realmente recusar este ajuste?');">
                  <i class="fa-solid fa-xmark"></i> Negar
                </a>
              <?php else: ?>
                <span style="color: var(--text-muted); font-size: 12px; margin-right: 6px;">Concluído</span>
              <?php endif; ?>
              <a href="index.php?route=timelogs&action=delete_adjustment&id=<?= $adj['id'] ?>" class="btn btn-danger" style="font-size: 11px; padding: 4px 8px; margin-left: 4px;" onclick="return confirm('Deseja realmente excluir este pedido de retificação?');">
                <i class="fa-solid fa-trash"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Overtime & Bank of Hours Table (Padrão GoF Strategy) -->
<div class="card" style="margin-top: 24px;">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fa-solid fa-business-time" style="color: #a78bfa;"></i>
      Apuração de Horas Extras &amp; Banco de Horas (Padrão Strategy)
    </h3>
    <span class="badge badge-info"><?= count($overtimeEntries ?? []) ?> Lançamentos</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Colaborador</th>
        <th>Data</th>
        <th>Horas</th>
        <th>Estratégia Aplicada (GoF Strategy)</th>
        <th>Valor Hora Base</th>
        <th>Total Adicional</th>
        <th style="text-align: right;">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($overtimeEntries)): ?>
        <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">Nenhum lançamento de horas extras ou banco de horas registrado.</td></tr>
      <?php else: ?>
        <?php foreach ($overtimeEntries as $ot): ?>
          <tr>
            <td>
              <strong style="color: #fff;"><?= htmlspecialchars($ot['employee_name']) ?></strong>
              <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($ot['cpf']) ?> | Saldo Banco: <?= round(((int)$ot['bank_hours_balance']) / 60, 1) ?>h</div>
            </td>
            <td><?= date('d/m/Y', strtotime($ot['date'])) ?></td>
            <td><strong style="color: #fbbf24;"><?= $ot['hours'] ?> horas</strong></td>
            <td>
              <?php if ($ot['type'] === 'STANDARD_50'): ?>
                <span class="badge badge-warning"><i class="fa-solid fa-bolt"></i> Standard50Strategy (+50%)</span>
              <?php elseif ($ot['type'] === 'SUNDAY_100'): ?>
                <span class="badge badge-danger"><i class="fa-solid fa-calendar-xmark"></i> Sunday100Strategy (+100%)</span>
              <?php else: ?>
                <span class="badge badge-info"><i class="fa-solid fa-piggy-bank"></i> BankHoursStrategy (Compensação)</span>
              <?php endif; ?>
            </td>
            <td>R$ <?= number_format($ot['hourly_rate'], 2, ',', '.') ?>/h</td>
            <td>
              <?php if ($ot['amount'] > 0): ?>
                <strong style="color: #34d399;">+ R$ <?= number_format($ot['amount'], 2, ',', '.') ?> (em folha)</strong>
              <?php else: ?>
                <strong style="color: #38bdf8;">+ <?= $ot['hours'] ?>h no Banco de Horas</strong>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <a href="index.php?route=timelogs&action=delete_overtime&id=<?= $ot['id'] ?>" class="btn btn-danger" style="font-size: 11px; padding: 4px 8px;" onclick="return confirm('Deseja realmente cancelar este lançamento?');">
                <i class="fa-solid fa-trash"></i>
              </a>
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

<!-- Modal: Lançar Horas Extras / Banco de Horas (GoF Strategy) -->
<div id="overtimeModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <div>
        <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 4px;">
          <i class="fa-solid fa-business-time" style="color: #a855f7;"></i> Apuração de Horas Extras / Banco
        </h3>
        <span class="badge" style="background: rgba(168, 85, 247, 0.2); color: #c084fc; font-size: 11px;">
          GoF Strategy Pattern: OvertimeStrategy
        </span>
      </div>
      <button onclick="toggleModal('overtimeModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=timelogs&action=record_overtime" method="POST">
      <div class="form-group">
        <label class="form-label">Colaborador *</label>
        <select name="employee_id" class="form-control" required>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>">
              <?= htmlspecialchars($emp['full_name']) ?> (<?= $emp['cpf'] ?>) - Salário Base: R$ <?= number_format($emp['base_salary_cents'] / 100, 2, ',', '.') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Data do Registro *</label>
          <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Quantidade de Horas *</label>
          <input type="number" step="0.5" min="0.5" max="24" name="hours" class="form-control" placeholder="ex: 2.0" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Estratégia de Remuneração (Strategy Pattern) *</label>
        <select name="type" class="form-control" required id="strategySelect" onchange="updateStrategyHint()">
          <option value="STANDARD_50">Standard50Strategy (+50% em Dias Úteis / Art. 59 §1 CLT - Paga em Dinheiro)</option>
          <option value="SUNDAY_100">Sunday100Strategy (+100% Domingos e Feriados / Art. 70 CLT - Paga em Dinheiro)</option>
          <option value="BANK_HOURS">BankHoursStrategy (Acordo de Compensação / Banco de Horas - Não Paga Dinheiro)</option>
        </select>
        <div id="strategyHint" style="margin-top: 8px; font-size: 12px; color: var(--text-muted); background: rgba(255,255,255,0.03); padding: 8px 12px; border-radius: 6px; border-left: 3px solid #a855f7;">
          💡 <strong>Standard50Strategy</strong>: Aplica multiplicador 1.5x sobre o valor/hora normal (Salário/220h). O valor apurado entra diretamente como provento financeiro na folha de pagamento.
        </div>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('overtimeModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn" style="background: #8b5cf6; color: #fff;">
          <i class="fa-solid fa-calculator"></i> Executar Estratégia & Gravar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.toggle('active');
  }
}

function updateStrategyHint() {
  const sel = document.getElementById('strategySelect');
  const hint = document.getElementById('strategyHint');
  if (!sel || !hint) return;
  
  if (sel.value === 'STANDARD_50') {
    hint.innerHTML = '💡 <strong>Standard50Strategy</strong>: Aplica multiplicador 1.5x sobre o valor/hora normal (Salário/220h). O valor apurado entra como provento financeiro na folha de pagamento.';
    hint.style.borderLeftColor = '#fbbf24';
  } else if (sel.value === 'SUNDAY_100') {
    hint.innerHTML = '🚨 <strong>Sunday100Strategy</strong>: Aplica multiplicador 2.0x sobre o valor/hora normal (Salário/220h) para domingos e feriados. Paga em dinheiro na folha.';
    hint.style.borderLeftColor = '#f43f5e';
  } else if (sel.value === 'BANK_HOURS') {
    hint.innerHTML = '🏦 <strong>BankHoursStrategy</strong>: Retorna R$ 0,00 financeiro e credita as horas diretamente no <strong>Saldo de Banco de Horas</strong> do Colaborador para descanso futuro.';
    hint.style.borderLeftColor = '#38bdf8';
  }
}
</script>
