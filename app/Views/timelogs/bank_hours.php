<?php
/**
 * View: Dashboard do Colaborador & Extrato Analítico do Banco de Horas (Tela 02)
 * Funções de APF:
 * - ALI_BANCO_HORAS_NOTURNO (Felipe - 7 PFB / Baixa Complexidade)
 * - SE_APURACAO_BANCO_HORAS (Felipe - 5 PFB / Média Complexidade)
 * - SE_RELATORIO_HORAS_EXTRAS (Felipe - 5 PFB / Média Complexidade)
 * - CE_CONSULTAR_BANCO_HORAS (Felipe - 3 PFB / Baixa Complexidade)
 *
 * @var array<int, array<string, mixed>> $overtimeEntries
 * @var array<int, array<string, mixed>> $employees
 * @var array<string, mixed> $currentEmployee
 * @var array<string, mixed>|null $overtimeReport
 */
$filterEmp = $_GET['search_employee_id'] ?? '';
$filterType = $_GET['search_type'] ?? '';
$bankBalanceHours = round(((int)($currentEmployee['bank_hours_balance'] ?? 0)) / 60, 2);
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Extrato de Banco de Horas &amp; Horas Extras</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Apuração matemática de multiplicadores CLT (SE_APURACAO_BANCO_HORAS) e consulta de créditos/débitos (CE_CONSULTAR_BANCO_HORAS).</p>
  </div>
  <div style="display: flex; gap: 12px;">
    <a href="index.php?route=timelogs" class="btn btn-outline">
      <i class="fa-solid fa-clock"></i> Espelho Portaria 671
    </a>
    <button onclick="toggleModal('overtimeModal')" class="btn" style="background: #8b5cf6; color: #fff;">
      <i class="fa-solid fa-plus-circle"></i> Novo Lançamento (EE)
    </button>
  </div>
</div>

<!-- Stat Cards: Saldo de Banco de Horas -->
<div class="grid-4">
  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(56,189,248,0.15); color: #38bdf8;">
      <i class="fa-solid fa-piggy-bank"></i>
    </div>
    <div>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">SALDO BANCO DE HORAS</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #38bdf8; margin-top: 2px;"><?= $bankBalanceHours ?> horas</h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #fbbf24;">
      <i class="fa-solid fa-business-time"></i>
    </div>
    <div>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">HORAS EXTRAS 50% (MÊS)</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fbbf24; margin-top: 2px;"><?= number_format($overtimeReport['standard_50_hours'] ?? 0, 1) ?>h</h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(239,68,68,0.15); color: #f87171;">
      <i class="fa-solid fa-calendar-xmark"></i>
    </div>
    <div>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">HORAS EXTRAS 100% (MÊS)</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #f87171; margin-top: 2px;"><?= number_format($overtimeReport['sunday_100_hours'] ?? 0, 1) ?>h</h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: #34d399;">
      <i class="fa-solid fa-hand-holding-dollar"></i>
    </div>
    <div>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">PROVENTO HE EM FOLHA</span>
      <h3 style="font-size: 22px; font-weight: 800; color: #34d399; margin-top: 2px;">R$ <?= number_format($overtimeReport['grand_total_amount'] ?? 0, 2, ',', '.') ?></h3>
    </div>
  </div>
</div>

<!-- SE_APURACAO_BANCO_HORAS: DADO DERIVADO OBRIGATÓRIO (FELIPE - 5 PFB) -->
<div class="se-derived-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
    <div>
      <span class="se-badge"><i class="fa-solid fa-calculator"></i> SAÍDA EXTERNA (SE) — DADO DERIVADO MATEMÁTICO</span>
      <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">
        <i class="fa-solid fa-scale-balanced" style="color: #fbbf24;"></i> SE_APURACAO_BANCO_HORAS &amp; SE_RELATORIO_HORAS_EXTRAS — Motor de Multiplicadores CLT
      </h3>
    </div>
    <span class="badge badge-warning" style="font-size: 12px; padding: 6px 12px;">Complexidade: Média (5 PFB)</span>
  </div>

  <div style="font-size: 13px; color: #cbd5e1; line-height: 1.6; margin-bottom: 14px;">
    <strong>Justificativa do Dado Derivado:</strong> A apuração de horas suplementares e banco de horas envolve a execução de estratégias matemáticas de remuneração (Design Pattern <code>Strategy</code>) com base na jornada contratual de 220h/mês:
    <ul style="margin-left: 20px; margin-top: 6px; line-height: 1.6;">
      <li><strong>Standard50Strategy (Art. 59 §1 CLT):</strong> Aplica fator derivado de <code>1.5x</code> sobre o valor da hora normal para dias úteis de trabalho.</li>
      <li><strong>Sunday100Strategy (Art. 70 CLT):</strong> Aplica multiplicador integral de <code>2.0x</code> sobre o valor/hora para domingos e feriados.</li>
      <li><strong>BankHoursStrategy (Art. 59 §2 CLT):</strong> Realiza o cômputo temporal de compensação com conversão em minutos e define prazo prescricional legal de 6 meses.</li>
    </ul>
  </div>

  <div class="se-formula">
    <div style="color: #94a3b8; font-size: 11px; margin-bottom: 4px; text-transform: uppercase; font-weight: 700;">Fórmula Atuarial do Salário-Hora e Horas Extras:</div>
    <strong>Salário Hora = Salário Base / 220h | Valor HE 50% = Horas * SalárioHora * 1.5 | Valor HE 100% = Horas * SalárioHora * 2.0</strong>
  </div>
</div>

<!-- CE_CONSULTAR_BANCO_HORAS: DADOS DE ENTRADA (FILTROS) -->
<div class="ce-input-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
      <span class="ce-badge"><i class="fa-solid fa-filter"></i> CONSULTA EXTERNA (CE_CONSULTAR_BANCO_HORAS) — DADOS DE ENTRADA (FILTROS)</span>
      <h3 style="font-size: 16px; font-weight: 800; color: #fff;">Filtros do Extrato de Banco de Horas</h3>
    </div>
    <span class="badge badge-info">Complexidade: Baixa (3 PFB)</span>
  </div>

  <form action="index.php" method="GET">
    <input type="hidden" name="route" value="timelogs">
    <input type="hidden" name="action" value="bank_hours">
    <div style="display: grid; grid-template-columns: 2fr 1.5fr auto; gap: 14px; align-items: flex-end;">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Selecionar Colaborador Titular</label>
        <select name="search_employee_id" class="form-control">
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>" <?= ($selectedEmpId ?? '') === $emp['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['matricula'] ?? 'MAT') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Filtrar por Modalidade</label>
        <select name="search_type" class="form-control">
          <option value="">Todas as Modalidades</option>
          <option value="BANK_HOURS" <?= $filterType === 'BANK_HOURS' ? 'selected' : '' ?>>Banco de Horas (Compensação)</option>
          <option value="STANDARD_50" <?= $filterType === 'STANDARD_50' ? 'selected' : '' ?>>Hora Extra 50% (Paga em Dinheiro)</option>
          <option value="SUNDAY_100" <?= $filterType === 'SUNDAY_100' ? 'selected' : '' ?>>Hora Extra 100% (Domingos/Feriados)</option>
        </select>
      </div>

      <div style="display: flex; gap: 8px;">
        <a href="index.php?route=timelogs&action=bank_hours" class="btn btn-outline" style="font-size: 12px;">
          <i class="fa-solid fa-rotate-left"></i> Limpar
        </a>
        <button type="submit" class="btn" style="font-size: 12px;">
          <i class="fa-solid fa-magnifying-glass"></i> Filtrar Extrato (CE)
        </button>
      </div>
    </div>
  </form>
</div>

<!-- CE_CONSULTAR_BANCO_HORAS: DADOS DE SAÍDA (RESULTADOS) -->
<div class="card">
  <div class="card-header">
    <div>
      <span class="ce-badge"><i class="fa-solid fa-table-list"></i> CONSULTA EXTERNA (CE_CONSULTAR_BANCO_HORAS) — DADOS DE SAÍDA (RESULTADOS)</span>
      <h3 class="card-title" style="margin-top: 4px;">
        <i class="fa-solid fa-list-check" style="color: #a855f7;"></i> Lançamentos Históricos no Banco de Horas / Folha
      </h3>
    </div>
    <span class="badge badge-info"><?= count($overtimeEntries) ?> lançamentos apurados</span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table">
      <thead>
        <tr>
          <th>Data Ocorrência</th>
          <th>Colaborador</th>
          <th>Qtd. Horas</th>
          <th>Taxa/Hora Base</th>
          <th>Modalidade / Regra</th>
          <th>Valor Apurado (R$)</th>
          <th>Prazo de Prescrição CLT</th>
          <th>Status Fechamento</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($overtimeEntries)): ?>
          <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px;">Nenhum lançamento encontrado para os filtros selecionados.</td></tr>
        <?php else: ?>
          <?php foreach ($overtimeEntries as $ot): ?>
            <tr>
              <td><strong style="color: #fff;"><?= date('d/m/Y', strtotime($ot['date'])) ?></strong></td>
              <td><?= htmlspecialchars($ot['employee_name']) ?></td>
              <td><strong style="color: #38bdf8; font-size: 14px;"><?= number_format((float)$ot['hours'], 1) ?>h</strong></td>
              <td>R$ <?= number_format((float)$ot['hourly_rate'], 2, ',', '.') ?>/h</td>
              <td>
                <span class="badge <?= $ot['type'] === 'BANK_HOURS' ? 'badge-info' : ($ot['type'] === 'SUNDAY_100' ? 'badge-danger' : 'badge-warning') ?>">
                  <?= htmlspecialchars($ot['type']) ?>
                </span>
                <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($ot['strategy_used'] ?? 'Strategy') ?></div>
              </td>
              <td>
                <?php if ((float)$ot['amount'] > 0): ?>
                  <strong style="color: #34d399;">R$ <?= number_format((float)$ot['amount'], 2, ',', '.') ?></strong>
                <?php else: ?>
                  <span style="color: #38bdf8; font-weight: 600;">Crédito Banco</span>
                <?php endif; ?>
              </td>
              <td>
                <span style="color: var(--text-muted); font-size: 12px;"><?= date('d/m/Y', strtotime($ot['expiration_date'] ?? '+6 months')) ?></span>
                <small style="display: block; color: #a5b4fc; font-size: 10px;">Art. 59 §2 CLT</small>
              </td>
              <td>
                <span class="badge <?= ($ot['status_fechamento'] ?? 'PENDING') === 'PAID' ? 'badge-success' : 'badge-warning' ?>">
                  <?= htmlspecialchars($ot['status_fechamento'] ?? 'PENDING') ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Lançar Horas Extras / Banco -->
<div id="overtimeModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <div>
        <span class="badge badge-primary" style="font-size: 10px;">ENTRADA EXTERNA (EE)</span>
        <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">Novo Lançamento de Horas Extras / Compensação</h3>
      </div>
      <button onclick="toggleModal('overtimeModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=timelogs&action=record_overtime" method="POST">
      <div class="form-group">
        <label class="form-label">Colaborador Titular *</label>
        <select name="employee_id" class="form-control" required>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?> (<?= $emp['cpf'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Data da Execução *</label>
          <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Quantidade de Horas *</label>
          <input type="number" step="0.5" min="0.5" max="24" name="hours" class="form-control" value="2.0" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Modalidade de Apuração (Strategy Pattern) *</label>
        <select name="type" class="form-control">
          <option value="BANK_HOURS">BankHoursStrategy (Acordo de Compensação / Banco de Horas)</option>
          <option value="STANDARD_50">Standard50Strategy (+50% em Dias Úteis / Pago em Dinheiro)</option>
          <option value="SUNDAY_100">Sunday100Strategy (+100% Domingos e Feriados / Pago em Dinheiro)</option>
        </select>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" onclick="toggleModal('overtimeModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-success">Efetivar Lançamento</button>
      </div>
    </form>
  </div>
</div>
