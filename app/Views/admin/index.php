<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <span class="badge badge-danger" style="margin-bottom: 8px;">PAINEL DO ADMINISTRADOR GLOBAL (CRUD 1 &amp; LPS)</span>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Gestão de Empresas (Tenants) &amp; Variabilidade LPS</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Cadastre novas empresas, exclua organizações ou personalize os Feature Flags de cada segmento.</p>
  </div>
  <button onclick="toggleModal('tenantModal')" class="btn" style="background: #ec4899; color: #fff;">
    <i class="fa-solid fa-building-circle-plus"></i> Nova Empresa (Tenant)
  </button>
</div>

<!-- ==================== TABELA DE EMPRESAS (CRUD 1 - FERNANDO) ==================== -->
<div class="card" style="margin-bottom: 30px;">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-building" style="color: #ec4899;"></i> Empresas Contratantes Cadastradas no Banco</h3>
    <span class="badge badge-info"><?= count($allTenants ?? []) ?> Empresas Ativas</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Código Tenant</th>
        <th>Razão Social / Nome Fantasia</th>
        <th>CNPJ</th>
        <th>Segmento LPS</th>
        <th>Colaboradores</th>
        <th>Status</th>
        <th style="text-align: right;">Ações de Gestão</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($allTenants)): ?>
        <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">Nenhuma empresa cadastrada no banco.</td></tr>
      <?php else: ?>
        <?php foreach ($allTenants as $t): ?>
          <tr>
            <td><code style="color: #38bdf8; font-weight: 700;"><?= htmlspecialchars($t['id']) ?></code></td>
            <td>
              <strong style="color: #fff;"><?= htmlspecialchars($t['corporate_name']) ?></strong>
              <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($t['trading_name'] ?? '') ?></div>
            </td>
            <td><?= htmlspecialchars($t['cnpj']) ?></td>
            <td>
              <?php if ($t['segment'] === 'tech'): ?>
                <span class="badge badge-info"><i class="fa-solid fa-laptop-code"></i> Tecnologia</span>
              <?php elseif ($t['segment'] === 'industry'): ?>
                <span class="badge badge-warning"><i class="fa-solid fa-industry"></i> Indústria</span>
              <?php else: ?>
                <span class="badge badge-success"><i class="fa-solid fa-piggy-bank"></i> Financeiro</span>
              <?php endif; ?>
            </td>
            <td><strong style="color: #a7f3d0;"><?= $t['employee_count'] ?? 0 ?> ativos</strong></td>
            <td><span class="badge badge-success">Operacional</span></td>
            <td style="text-align: right;">
              <a href="index.php?route=switch_tenant&role=<?= $t['id'] ?>" class="btn btn-primary" style="font-size: 11px; padding: 4px 10px;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Acessar
              </a>
              <a href="index.php?route=admin&action=delete_tenant&id=<?= $t['id'] ?>" class="btn btn-danger" style="font-size: 11px; padding: 4px 10px; margin-left: 4px;" onclick="return confirm('Deseja realmente excluir a empresa <?= htmlspecialchars(addslashes($t['corporate_name'])) ?> e seus dados?');">
                <i class="fa-solid fa-trash"></i> Excluir
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Cadastrar Nova Empresa -->
<div id="tenantModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Cadastrar Nova Empresa (Tenant)</h3>
      <button onclick="toggleModal('tenantModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=admin&action=create_tenant" method="POST">
      <div class="form-group">
        <label class="form-label">Razão Social da Empresa *</label>
        <input type="text" name="corporate_name" class="form-control" placeholder="ex: Nexus Inovação e Tecnologia S.A." required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Nome Fantasia</label>
          <input type="text" name="trading_name" class="form-control" placeholder="ex: NexusTech">
        </div>

        <div class="form-group">
          <label class="form-label">CNPJ *</label>
          <input type="text" name="cnpj" class="form-control" placeholder="00.000.000/0000-00" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Segmento da Linha de Produção (LPS) *</label>
        <select name="segment" class="form-control" required>
          <option value="tech">Tecnologia (Banco de Horas, Benefícios Flexíveis Caju, OKRs)</option>
          <option value="industry">Indústria &amp; Manufatura (HE 50%/100%, EPI NR-6 / ASO NR-7, Fretado)</option>
          <option value="financial">Financeiro &amp; Seguros (Ponto Biométrico Portaria 671, FinCorp Life, Bônus KPI)</option>
        </select>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('tenantModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn" style="background: #ec4899; color: #fff;"><i class="fa-solid fa-check"></i> Cadastrar Empresa</button>
      </div>
    </form>
  </div>
</div>

<div style="margin-bottom: 16px; margin-top: 30px;">
  <h2 style="font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 4px;">Matriz de Feature Toggles por Empresa (Variabilidade LPS)</h2>
  <p style="color: var(--text-muted); font-size: 13px;">Ligue ou desligue qualquer recurso individualmente para simular customizações contratuais.</p>
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
