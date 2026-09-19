<div style="margin-bottom: 24px;">
  <span class="badge badge-danger" style="margin-bottom: 8px;">ÁREA RESTRITA DO ADMINISTRADOR GLOBAL</span>
  <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Matriz de Feature Toggles & Linha de Produção (LPS)</h1>
  <p style="color: var(--text-muted); font-size: 14px;">Ative ou desative recursos por empresa cliente em tempo real para demonstrar a variabilidade dinâmica de software pro professor.</p>
</div>

<?php foreach ($tenants as $tId => $tName): ?>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">
        <?php if ($tId === 'tenant-tech'): ?>
          <i class="fa-solid fa-laptop-code" style="color: #3b82f6;"></i>
        <?php elseif ($tId === 'tenant-ind'): ?>
          <i class="fa-solid fa-industry" style="color: #f59e0b;"></i>
        <?php else: ?>
          <i class="fa-solid fa-piggy-bank" style="color: #10b981;"></i>
        <?php endif; ?>
        <?= htmlspecialchars($tName) ?>
      </h3>
      <span class="badge badge-info"><?= strtoupper($tId) ?></span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Identificador da Feature Flag</th>
          <th>Descrição Funcional do Recurso</th>
          <th>Estado Atual no Tenant</th>
          <th style="text-align: right;">Ação em Tempo Real</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($featuresCatalog as $fKey => $fDesc): ?>
          <?php 
            $isEnabled = $ftManager->isFeatureEnabled($fKey, null, null, $tId); 
          ?>
          <tr>
            <td><code style="color: #38bdf8; font-weight: 700;"><?= $fKey ?></code></td>
            <td><?= htmlspecialchars($fDesc) ?></td>
            <td>
              <?php if ($isEnabled): ?>
                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Ativa (Habilitada)</span>
              <?php else: ?>
                <span class="badge badge-danger"><i class="fa-solid fa-xmark"></i> Inativa (Desabilitada)</span>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <a href="index.php?route=admin&action=toggle_feature&tenant_id=<?= $tId ?>&feature=<?= $fKey ?>" class="btn <?= $isEnabled ? 'btn-danger' : 'btn-success' ?>" style="font-size: 11px; padding: 4px 12px;">
                <?= $isEnabled ? '<i class="fa-solid fa-toggle-on"></i> Desativar' : '<i class="fa-solid fa-toggle-off"></i> Ativar Feature' ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endforeach; ?>
