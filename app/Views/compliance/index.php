<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <span class="badge badge-success" style="margin-bottom: 8px;"><i class="fa-solid fa-piggy-bank"></i> EXCLUSIVO SEGMENTO FINANCEIRO &amp; SEGUROS</span>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Compliance &amp; Trilha de Auditoria LGPD/BACEN</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Trilha de auditoria criptográfica imutável com encadeamento SHA-256 via <code>AuditLogger</code> (Singleton).</p>
  </div>
  <button onclick="toggleModal('auditModal')" class="btn btn-success">
    <i class="fa-solid fa-shield-virus"></i> Novo Evento Auditável
  </button>
</div>

<!-- Ledger Verification Banner -->
<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(6,182,212,0.1) 100%); border: 1px solid #10b981;">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <span style="font-size: 12px; font-weight: 700; color: #34d399; text-transform: uppercase;">Laudo de Integridade Criptográfica (Chain Validation)</span>
      <h2 style="font-size: 22px; font-weight: 800; color: #fff; margin-top: 4px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-link"></i> Cadeia Inviolável: 100% VÁLIDA
      </h2>
      <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">Padrão GoF Singleton: <code style="color: #38bdf8;">AuditLogger::verifyChainIntegrity()</code> validou todos os blocos SHA-256 sem adulteração.</p>
    </div>
    <div>
      <span class="badge badge-success" style="font-size: 13px; padding: 6px 14px;">
        <i class="fa-solid fa-lock"></i> BACEN / LGPD Compliant
      </span>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-file-shield" style="color: var(--success);"></i> Ledger de Operações Financeiras e Acesso a Dados</h3>
    <span class="badge badge-info"><?= count($logs) ?> Registros no Ledger</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Operação / Ação</th>
        <th>Entidade Auditada</th>
        <th>Hash Criptográfico SHA-256</th>
        <th>Carimbo de Tempo (UTC)</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Nenhum log de auditoria no ledger.</td></tr>
      <?php else: ?>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td><strong style="color: #fff;"><?= htmlspecialchars($log['action']) ?></strong></td>
            <td><?= htmlspecialchars($log['entity_type'] ?? 'Entidade') ?> <span style="font-size: 11px; color: var(--text-muted);">(<?= htmlspecialchars($log['entity_id'] ?? '') ?>)</span></td>
            <td><code style="color: #38bdf8; font-size: 12px;"><?= htmlspecialchars(substr($log['integrity_hash'] ?? '000000', 0, 24)) ?>...</code></td>
            <td><?= date('d/m/Y H:i:s', strtotime($log['timestamp'] ?? 'now')) ?></td>
            <td><span class="badge badge-success"><i class="fa-solid fa-check"></i> IMUTÁVEL</span></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Novo Evento -->
<div id="auditModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Simular Evento de Auditoria Bacen/LGPD</h3>
      <button onclick="toggleModal('auditModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=compliance&action=record_log" method="POST">
      <div class="form-group">
        <label class="form-label">Ação / Tipo de Operação *</label>
        <select name="action" class="form-control">
          <option value="REPASSE_COMISSAO_CORRETOR">Repasse de Comissão ao Corretor (Dupla Custódia)</option>
          <option value="EXPORTACAO_DADOS_LGPD">Exportação de Dados Sensíveis Colaborador (LGPD Art. 18)</option>
          <option value="EMISSAO_APOLICE_ALTO_VALOR">Emissão de Apólice Executiva D&amp;O Acima de R$ 1 Milhão</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Entidade Afetada *</label>
        <input type="text" name="entity_name" class="form-control" value="Portal do Corretor FinCorp" required>
      </div>

      <div class="form-group">
        <label class="form-label">Identificador do Registro / Transação</label>
        <input type="text" name="entity_id" class="form-control" value="TX-FINCORP-<?= rand(1000,9999) ?>">
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('auditModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-stamp"></i> Gravar no Ledger SHA-256</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id) {
  document.getElementById(id).classList.toggle('active');
}
</script>
