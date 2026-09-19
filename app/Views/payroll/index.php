<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Demonstrativo & Cálculo de Folha (Template Method & Strategy)</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Processamento da folha de pagamento invariante com hooks para CLT, PJ e Estágio.</p>
  </div>
  <button onclick="window.print()" class="btn btn-outline">
    <i class="fa-solid fa-print"></i> Imprimir Holerite
  </button>
</div>

<div class="grid-4" style="grid-template-columns: 1fr 1fr;">
  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(99,102,241,0.15); color: #818cf8;">
      <i class="fa-solid fa-money-bill-wave"></i>
    </div>
    <div>
      <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">FOLHA BRUTA TOTAL</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 2px;">R$ <?= number_format($totalGross, 2, ',', '.') ?></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: #34d399;">
      <i class="fa-solid fa-wallet"></i>
    </div>
    <div>
      <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">FOLHA LÍQUIDA A PAGAR</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 2px;">R$ <?= number_format($totalNet, 2, ',', '.') ?></h3>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-calculator" style="color: var(--info);"></i> Demonstrativo de Holerites Individuais</h3>
    <span class="badge badge-info"><?= count($payrolls) ?> Colaboradores Calculados</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Colaborador</th>
        <th>Modalidade Contratual</th>
        <th>Salário Bruto</th>
        <th>Descontos Legais (INSS/IRRF)</th>
        <th>Salário Líquido</th>
        <th>Estratégia Aplicada (Pattern)</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($payrolls)): ?>
        <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Nenhum colaborador para calcular folha.</td></tr>
      <?php else: ?>
        <?php foreach ($payrolls as $pay): ?>
          <tr>
            <td>
              <strong style="color: #fff;"><?= htmlspecialchars($pay['name']) ?></strong>
              <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($pay['cpf']) ?></div>
            </td>
            <td><span class="badge badge-info"><?= htmlspecialchars($pay['type']) ?></span></td>
            <td>R$ <?= number_format($pay['base'], 2, ',', '.') ?></td>
            <td><span style="color: #f87171;">- R$ <?= number_format($pay['deductions'], 2, ',', '.') ?></span></td>
            <td><strong style="color: #34d399;">R$ <?= number_format($pay['net'], 2, ',', '.') ?></strong></td>
            <td><code style="color: #fbbf24; font-size: 11px;"><?= htmlspecialchars($pay['strategy']) ?></code></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
