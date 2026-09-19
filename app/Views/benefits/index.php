<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Catálogo de Benefícios Corporativos (CRUD 8)</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Parametrização de regras de desconto folha com Padrão Strategy.</p>
  </div>
  <button onclick="toggleModal('benefitModal')" class="btn" style="background: #ec4899;">
    <i class="fa-solid fa-gift"></i> Novo Benefício
  </button>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-gift" style="color: #ec4899;"></i> Benefícios Ativos na Empresa</h3>
    <span class="badge badge-info"><?= count($benefits) ?> Benefícios</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Nome do Benefício</th>
        <th>Categoria</th>
        <th>Provedor</th>
        <th>Valor Benefício</th>
        <th>Coparticipação / Desconto (Strategy)</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($benefits)): ?>
        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Nenhum benefício disponível nesta empresa.</td></tr>
      <?php else: ?>
        <?php foreach ($benefits as $ben): ?>
          <tr>
            <td><strong style="color: #fff;"><?= htmlspecialchars($ben['name']) ?></strong></td>
            <td><span class="badge badge-info"><?= htmlspecialchars($ben['type']) ?></span></td>
            <td><?= htmlspecialchars($ben['provider'] ?? 'Provedor') ?></td>
            <td><strong style="color: #34d399;">R$ <?= number_format(($ben['value_cents'] ?? 0) / 100, 2, ',', '.') ?></strong></td>
            <td>
              <strong style="color: #f472b6;">
                <?= ($ben['employee_cost_share_percentage'] ?? 0) > 0 ? $ben['employee_cost_share_percentage'] . '% sobre o salário' : 'Isento / Copag Zero' ?>
              </strong>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Novo Benefício -->
<div id="benefitModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Cadastrar Novo Benefício</h3>
      <button onclick="toggleModal('benefitModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=benefits&action=store" method="POST">
      <div class="form-group">
        <label class="form-label">Nome do Benefício *</label>
        <input type="text" name="name" class="form-control" placeholder="ex: Vale Refeição Caju / Flash" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Tipo de Benefício</label>
          <select name="type" class="form-control">
            <option value="MEAL_VOUCHER">Vale Refeição / Alimentação</option>
            <option value="TRANSPORTATION">Vale Transporte (CLT 6%)</option>
            <option value="HEALTH_INSURANCE">Plano de Saúde Corporativo</option>
            <option value="FLEXIBLE">Cartão Flexível Multicarteira</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Valor do Benefício (R$)</label>
          <input type="number" step="10" name="value" class="form-control" value="600.00" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">% Desconto/Coparticipação na Folha</label>
        <input type="number" step="0.1" name="employee_cost_share_percentage" class="form-control" placeholder="6.0" value="6.0">
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('benefitModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn" style="background: #ec4899;"><i class="fa-solid fa-floppy-disk"></i> Cadastrar Benefício</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id) {
  document.getElementById(id).classList.toggle('active');
}
</script>
