<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Gestão de Apólices Corporativas FinCorp (CRUD 10)</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Emissão e gestão de seguros de vida em grupo e Responsabilidade Civil D&O executivo.</p>
  </div>
  <button onclick="toggleModal('policyModal')" class="btn btn-success">
    <i class="fa-solid fa-file-contract"></i> Emitir Nova Apólice
  </button>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-shield-halved" style="color: var(--success);"></i> Apólices Ativas no Portal FinCorp</h3>
    <span class="badge badge-success"><?= count($policies) ?> Apólices</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Número da Apólice</th>
        <th>Tipo de Cobertura</th>
        <th>Capital Segurado (Teto)</th>
        <th>Prêmio Mensal</th>
        <th>Vigência</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($policies)): ?>
        <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Nenhuma apólice cadastrada para esta empresa.</td></tr>
      <?php else: ?>
        <?php foreach ($policies as $pol): ?>
          <tr>
            <td><code style="color: #38bdf8; font-weight: 700;"><?= htmlspecialchars($pol['policy_number']) ?></code></td>
            <td><strong style="color: #fff;"><?= htmlspecialchars($pol['policy_type'] ?? 'D_AND_O') ?></strong></td>
            <td><strong style="color: #34d399;">R$ <?= number_format(($pol['insured_capital_cents'] ?? 0) / 100, 2, ',', '.') ?></strong></td>
            <td>R$ <?= number_format(($pol['monthly_premium_cents'] ?? 0) / 100, 2, ',', '.') ?></td>
            <td>
              <?= date('d/m/Y', strtotime($pol['start_date'])) ?> até <span style="color: #fbbf24;"><?= date('d/m/Y', strtotime($pol['end_date'])) ?></span>
            </td>
            <td><span class="badge badge-success">VIGENTE</span></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Emitir Apólice -->
<div id="policyModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Emitir Apólice FinCorp Seguros</h3>
      <button onclick="toggleModal('policyModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=insurance&action=issue" method="POST">
      <div class="form-group">
        <label class="form-label">Tipo de Apólice Corporativa *</label>
        <select name="policy_type" class="form-control">
          <option value="D_AND_O">Seguro D&O (Responsabilidade Civil de Diretores & Executivos)</option>
          <option value="GROUP_LIFE">Seguro de Vida em Grupo FinCorp Capital Alto</option>
          <option value="OPERATIONAL">Seguro Operacional Industrial de Riscos Nomeados</option>
        </select>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Capital Cobertura (R$) *</label>
          <input type="number" step="1000" name="coverage_amount" class="form-control" value="1000000" required>
        </div>
        <div class="form-group">
          <label class="form-label">Prêmio Mensal (R$) *</label>
          <input type="number" step="10" name="monthly_premium" class="form-control" value="2500" required>
        </div>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('policyModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-stamp"></i> Emitir Apólice FinCorp</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id) {
  document.getElementById(id).classList.toggle('active');
}
</script>
