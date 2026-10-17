<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <span class="badge badge-info" style="margin-bottom: 8px;"><i class="fa-solid fa-microchip"></i> EXCLUSIVO SEGMENTO TECNOLOGIA (TECH)</span>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Avaliação de Desempenho &amp; OKRs (Padrão Strategy)</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Metas ágeis trimestrais com cálculo de bônus por sobrecumprimento via <code>OkrStrategy</code>.</p>
  </div>
  <button onclick="toggleModal('okrModal')" class="btn btn-primary">
    <i class="fa-solid fa-bullseye"></i> Novo OKR Trimestral
  </button>
</div>

<!-- Score Banner -->
<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(99,102,241,0.2) 0%, rgba(168,85,247,0.1) 100%); border: 1px solid #6366f1;">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <span style="font-size: 12px; font-weight: 700; color: #a5b4fc; text-transform: uppercase;">Atingimento Médio Ponderado (Q3 2026)</span>
      <h2 style="font-size: 32px; font-weight: 800; color: #fff; margin-top: 4px;"><?= $performanceMultiplier ?>%</h2>
      <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">Padrão GoF Strategy: <code style="color: #38bdf8;">OkrStrategy::calculateBonusMultiplier()</code> ativado para este segmento.</p>
    </div>
    <div style="text-align: right;">
      <span class="badge badge-success" style="font-size: 13px; padding: 6px 14px;">
        <i class="fa-solid fa-award"></i> Bônus Elegível: 1.2x Salário Base
      </span>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-chart-line" style="color: var(--primary);"></i> Objetivos e Resultados-Chave da Equipe</h3>
    <span class="badge badge-info"><?= count($okrs) ?> Objetivos</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Objetivo Estratégico (Key Result)</th>
        <th>Responsável</th>
        <th>Trimestre</th>
        <th>Progresso</th>
        <th>Peso</th>
        <th style="text-align: right;">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($okrs as $okr): ?>
        <tr>
          <td>
            <strong style="color: #fff;"><?= htmlspecialchars($okr['title']) ?></strong>
          </td>
          <td><span style="color: #c7d2fe;"><?= htmlspecialchars($okr['owner']) ?></span></td>
          <td><span class="badge badge-warning"><?= htmlspecialchars($okr['quarter']) ?></span></td>
          <td style="min-width: 180px;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="flex: 1; background: #0f172a; height: 8px; border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color);">
                <div style="width: <?= min(100, $okr['current']) ?>%; height: 100%; background: <?= $okr['current'] >= 100 ? '#10b981' : '#6366f1' ?>;"></div>
              </div>
              <strong style="color: <?= $okr['current'] >= 100 ? '#34d399' : '#fff' ?>; font-size: 12px;"><?= $okr['current'] ?>%</strong>
            </div>
          </td>
          <td><?= $okr['weight'] ?>%</td>
          <td style="text-align: right;">
            <a href="index.php?route=okrs&action=delete&id=<?= $okr['id'] ?>" class="btn btn-danger" style="font-size: 11px; padding: 4px 8px;" onclick="return confirm('Remover este OKR?');">
              <i class="fa-solid fa-trash"></i> Excluir
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Novo OKR -->
<div id="okrModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Cadastrar Novo OKR Trimestral</h3>
      <button onclick="toggleModal('okrModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=okrs&action=store" method="POST">
      <div class="form-group">
        <label class="form-label">Título do Objetivo / Meta *</label>
        <input type="text" name="title" class="form-control" placeholder="ex: Migrar microsserviços para PHP 8.3" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Líder / Responsável</label>
          <input type="text" name="owner" class="form-control" placeholder="ex: Andryus (Tech Lead)">
        </div>

        <div class="form-group">
          <label class="form-label">Progresso Atual (%)</label>
          <input type="number" name="progress" min="0" max="120" value="50" class="form-control">
        </div>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('okrModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Salvar OKR</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id) {
  document.getElementById(id).classList.toggle('active');
}
</script>
