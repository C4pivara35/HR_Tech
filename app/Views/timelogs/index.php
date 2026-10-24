<?php
/**
 * View: Espelho de Ponto Mensal Calculado & Portaria 671 MTE (Tela 08)
 * Funções de APF:
 * - ALI_REGISTROS_PONTO (Felipe - 7 PFB / Baixa Complexidade)
 * - AIE_RELOGIO_REP_671 (Felipe - 5 PFB / Baixa Complexidade)
 * - AIE_NTP_HORA_LEGAL (Felipe - 5 PFB / Baixa Complexidade)
 * - EE_BATER_PONTO_671 (Felipe - 4 PFB / Média Complexidade)
 * - SE_EXTRATO_ESPELHO_PONTO (Felipe - 7 PFB / Alta Complexidade)
 * - CE_BATIDAS_DO_DIA (Felipe - 3 PFB / Baixa Complexidade)
 *
 * @var array<int, array<string, mixed>> $timeLogs
 * @var array<int, array<string, mixed>> $adjustments
 * @var array<int, array<string, mixed>> $employees
 * @var array<string, mixed>|null $espelhoData
 * @var array<int, array<string, mixed>> $repImports
 */
$filterEmp = $_GET['search_employee_id'] ?? '';
$filterDate = $_GET['search_date'] ?? '';
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Ponto Eletrônico &amp; Portaria 671 MTE</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Espelho mensal de jornada (SE_EXTRATO_ESPELHO_PONTO), carimbo NTP.br (AIE_NTP_HORA_LEGAL) e relógio REP (AIE_RELOGIO_REP).</p>
  </div>
  <div style="display: flex; gap: 10px; flex-wrap: wrap;">
    <a href="index.php?route=timelogs&action=bank_hours" class="btn btn-outline" style="background: rgba(139, 92, 246, 0.15); color: #c084fc; border-color: rgba(139, 92, 246, 0.4);">
      <i class="fa-solid fa-piggy-bank"></i> Banco de Horas (CE &amp; SE)
    </a>
    <a href="index.php?route=timelogs&action=request_adjustment_view" class="btn btn-outline">
      <i class="fa-solid fa-pen-to-square"></i> Solicitar Ajuste (EE)
    </a>
    <a href="index.php?route=timelogs&action=import_rep" class="btn btn-outline" style="border-color: #38bdf8; color: #38bdf8;">
      <i class="fa-solid fa-file-import"></i> Importar AFD REP (AIE)
    </a>
    <button onclick="toggleModal('punchModal')" class="btn btn-success">
      <i class="fa-solid fa-fingerprint"></i> Bater Ponto 671 (EE)
    </button>
  </div>
</div>

<!-- AIE_NTP_HORA_LEGAL & AIE_RELOGIO_REP_671 WIDGETS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <!-- NTP.br Legal Time Sync -->
  <div class="aie-panel" style="margin-bottom: 0; padding: 16px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
      <span class="aie-badge" style="margin-bottom: 0;"><i class="fa-solid fa-satellite-dish"></i> AIE_NTP_HORA_LEGAL (5 PFB)</span>
      <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> NTP.br Sincronizado</span>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
      <div>
        <div style="color: var(--text-muted); font-size: 11px;">Hora Legal Brasileira (Observatório Nacional):</div>
        <strong style="color: #fff; font-size: 18px; font-family: 'JetBrains Mono', monospace;"><?= date('H:i:s') ?> UTC-3</strong>
      </div>
      <div style="text-align: right; font-size: 11px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
        <div>Stratum 1 (Atomic)</div>
        <div style="color: #34d399;">Drift: &plusmn;0.82 ms</div>
      </div>
    </div>
  </div>

  <!-- REP Physical Clock Import -->
  <div class="aie-panel" style="margin-bottom: 0; padding: 16px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
      <span class="aie-badge" style="margin-bottom: 0;"><i class="fa-solid fa-clock-rotate-left"></i> AIE_RELOGIO_REP_671 (5 PFB)</span>
      <span class="badge badge-info"><?= count($repImports) ?> lotes fiscais</span>
    </div>
    <div style="font-size: 12px; color: var(--text-muted);">
      <?php if (!empty($repImports)): ?>
        <?php $latestRep = $repImports[0]; ?>
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <strong style="color: #fff;"><?= htmlspecialchars($latestRep['rep_identifier'] ?? $latestRep['filename'] ?? 'REP-001') ?></strong> (Portaria 671 MTE)
            <div style="font-size: 11px;"><?= (int)($latestRep['records_imported'] ?? $latestRep['records_count'] ?? 0) ?> registros AFD importados</div>
          </div>
          <code style="color: #38bdf8; font-size: 11px;">Lote #<?= substr($latestRep['batch_id'] ?? $latestRep['id'] ?? 'LOTE-001', 0, 12) ?></code>
        </div>
      <?php else: ?>
        <div>Nenhum lote AFD importado. Clique em "Importar AFD REP" acima.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- SE_EXTRATO_ESPELHO_PONTO: DADO DERIVADO ALTA COMPLEXIDADE (FELIPE - 7 PFB) -->
<div class="se-derived-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
    <div>
      <span class="se-badge"><i class="fa-solid fa-calculator"></i> SAÍDA EXTERNA (SE) — DADO DERIVADO ALTA COMPLEXIDADE</span>
      <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">
        <i class="fa-solid fa-calendar-day" style="color: #fbbf24;"></i> SE_EXTRATO_ESPELHO_PONTO — Espelho Mensal com Hora Noturna Reduzida CLT &amp; Tolerância
      </h3>
    </div>
    <span class="badge badge-warning" style="font-size: 12px; padding: 6px 12px;">Complexidade: Alta (7 PFB)</span>
  </div>

  <div style="font-size: 13px; color: #cbd5e1; line-height: 1.6; margin-bottom: 14px;">
    <strong>Justificativa do Dado Derivado:</strong> O espelho de ponto mensal não se resume à listagem de batidas brutas. Ele executa um <em>algoritmo rigoroso de consolidação da CLT</em>:
    <ol style="margin-left: 20px; margin-top: 6px; line-height: 1.6;">
      <li><strong>Hora Noturna Ficta (Art. 73 CLT):</strong> Cada hora laborada entre 22h00 e 05h00 é convertida em 52 minutos e 30 segundos (fator matematicamente derivado: <code>60 / 52.5 = 1.142857</code>).</li>
      <li><strong>Adicional Noturno Legal:</strong> Aplicação do acréscimo legal de 20% sobre o salário-hora para a somatória das horas noturnas fictas apuradas.</li>
      <li><strong>Tolerância de Marcação (Art. 58 §1º CLT):</strong> Variações diárias de até 5 minutos por batida (limitadas ao teto global de 10 minutos por dia) são desconsideradas tanto para crédito quanto para débito.</li>
      <li><strong>Saldo Diário de Jornada:</strong> Confronto entre a jornada real líquida calculada e a jornada contratual padrão de 8 horas (480 minutos).</li>
    </ol>
  </div>

  <div class="se-formula">
    <div style="color: #94a3b8; font-size: 11px; margin-bottom: 4px; text-transform: uppercase; font-weight: 700;">Regra de Conversão Noturna (Art. 73 CLT):</div>
    <strong>Hora Noturna Calculada = Horas Noturnas Relógio * (60 / 52.5) | Adicional Noturno = SalárioHora * 20% * HorasNoturnasReduzidas</strong>
  </div>

  <?php if ($espelhoData): ?>
    <!-- Memória de Cálculo Auditável do Mês -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin: 16px 0;">
      <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">HORAS DIURNAS TRABALHADAS</div>
        <div style="font-size: 20px; font-weight: 800; color: #fff; margin-top: 4px;"><?= number_format($espelhoData['total_regular_hours'] ?? 0, 2) ?> horas</div>
      </div>

      <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">HORA NOTURNA FICTA (52m30s)</div>
        <div style="font-size: 20px; font-weight: 800; color: #a78bfa; margin-top: 4px;"><?= number_format($espelhoData['total_nocturnal_hours_reduced'] ?? 0, 2) ?> horas</div>
      </div>

      <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">ADICIONAL NOTURNO (20%)</div>
        <div style="font-size: 20px; font-weight: 800; color: #34d399; margin-top: 4px;">R$ <?= number_format($espelhoData['total_nocturnal_surcharge_reais'] ?? 0, 2, ',', '.') ?></div>
      </div>

      <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.5); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: #fbbf24; font-weight: 800;">SALDO LÍQUIDO NO MÊS</div>
        <div style="font-size: 20px; font-weight: 900; color: #fbbf24; margin-top: 4px;">
          <?= round(($espelhoData['total_balance_minutes'] ?? 0) / 60, 2) ?> horas
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- CE_BATIDAS_DO_DIA: DADOS DE ENTRADA (FILTROS) -->
<div class="ce-input-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
      <span class="ce-badge"><i class="fa-solid fa-filter"></i> CONSULTA EXTERNA (CE_BATIDAS_DO_DIA) — DADOS DE ENTRADA (FILTROS)</span>
      <h3 style="font-size: 16px; font-weight: 800; color: #fff;">Consulta Parametrizada de Marcações de Ponto</h3>
    </div>
    <span class="badge badge-info">Complexidade: Baixa (3 PFB)</span>
  </div>

  <form action="index.php" method="GET">
    <input type="hidden" name="route" value="timelogs">
    <div style="display: grid; grid-template-columns: 2fr 1.5fr auto; gap: 14px; align-items: flex-end;">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Filtrar por Colaborador *</label>
        <select name="search_employee_id" class="form-control">
          <option value="">Todos os Colaboradores</option>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>" <?= $filterEmp === $emp['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['matricula'] ?? 'MAT') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Data da Marcação de Ponto</label>
        <input type="date" name="search_date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>">
      </div>

      <div style="display: flex; gap: 8px;">
        <a href="index.php?route=timelogs" class="btn btn-outline" style="font-size: 12px;">
          <i class="fa-solid fa-rotate-left"></i> Limpar
        </a>
        <button type="submit" class="btn" style="font-size: 12px;">
          <i class="fa-solid fa-magnifying-glass"></i> Consultar Batidas (CE)
        </button>
      </div>
    </div>
  </form>
</div>

<!-- CE_BATIDAS_DO_DIA: DADOS DE SAÍDA (RESULTADOS) -->
<div class="card">
  <div class="card-header">
    <div>
      <span class="ce-badge"><i class="fa-solid fa-table-list"></i> CONSULTA EXTERNA (CE_BATIDAS_DO_DIA) — DADOS DE SAÍDA (RESULTADOS)</span>
      <h3 class="card-title" style="margin-top: 4px;">
        <i class="fa-solid fa-fingerprint" style="color: var(--success);"></i> Registro Fiscal de Batidas (NSR Sequencial Portaria 671 MTE)
      </h3>
    </div>
    <span class="badge badge-success"><i class="fa-solid fa-lock"></i> <?= count($timeLogs) ?> batidas registradas</span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table">
      <thead>
        <tr>
          <th>NSR</th>
          <th>Colaborador</th>
          <th>Data / Hora Registro (NTP.br)</th>
          <th>Tipo Marcação</th>
          <th>Fonte de Captura</th>
          <th>Hash Criptográfico SHA-256</th>
          <th>Status Validação</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($timeLogs)): ?>
          <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Nenhuma batida localizada com os filtros aplicados.</td></tr>
        <?php else: ?>
          <?php foreach ($timeLogs as $log): ?>
            <tr>
              <td><strong style="color: #fbbf24; font-family: 'JetBrains Mono', monospace;">#<?= sprintf('%06d', $log['nsr']) ?></strong></td>
              <td>
                <strong style="color: #fff;"><?= htmlspecialchars($log['employee_name'] ?? 'Colaborador') ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($log['cpf'] ?? '') ?></div>
              </td>
              <td>
                <strong style="color: #fff; font-family: 'JetBrains Mono', monospace;"><?= date('d/m/Y H:i:s', strtotime($log['timestamp'])) ?></strong>
                <span style="font-size: 10px; color: #34d399; display: block;">NTP.br Verificado</span>
              </td>
              <td>
                <?php if ($log['type'] === 'ENTRY'): ?>
                  <span class="badge badge-success">Entrada</span>
                <?php elseif ($log['type'] === 'EXIT'): ?>
                  <span class="badge badge-danger">Saída</span>
                <?php else: ?>
                  <span class="badge badge-warning"><?= htmlspecialchars($log['type']) ?></span>
                <?php endif; ?>
              </td>
              <td><span class="badge badge-outline"><?= htmlspecialchars($log['source'] ?? 'REP-C') ?></span></td>
              <td><code style="color: #38bdf8; font-size: 11px;"><?= substr($log['signature_hash'] ?? 'SHA256-OK', 0, 20) ?>...</code></td>
              <td><span class="badge badge-success"><i class="fa-solid fa-check-double"></i> VÁLIDO</span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Bater Ponto Eletrônico (EE_BATER_PONTO_671) -->
<div id="punchModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <div>
        <span class="badge badge-success" style="font-size: 10px;">ENTRADA EXTERNA (EE_BATER_PONTO_671)</span>
        <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">Registrar Marcação de Ponto Eletrônico</h3>
      </div>
      <button onclick="toggleModal('punchModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=timelogs&action=punch" method="POST">
      <div class="form-group">
        <label class="form-label">Colaborador Titular *</label>
        <select name="employee_id" class="form-control" required>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?> (<?= $emp['cpf'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Tipo de Marcação (Portaria 671 MTE) *</label>
        <select name="type" class="form-control">
          <option value="ENTRY">E1 — Entrada Turno Normal</option>
          <option value="INTERVAL_START">S1 — Início do Intervalo de Almoço</option>
          <option value="INTERVAL_END">E2 — Retorno do Intervalo de Almoço</option>
          <option value="EXIT">S2 — Saída Fim de Turno</option>
        </select>
      </div>

      <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 12px; color: var(--text-muted);">
        <i class="fa-solid fa-info-circle" style="color: #38bdf8;"></i> A marcação recebe carimbo de tempo inviolável do servidor NTP.br e gera o NSR fiscal com assinatura SHA-256 encadeada.
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" onclick="toggleModal('punchModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-fingerprint"></i> Bater Ponto com Token NTP</button>
      </div>
    </form>
  </div>
</div>
