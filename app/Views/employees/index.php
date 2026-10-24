<?php
/**
 * View: Gestão de Colaboradores & Consulta Parametrizada (Tela 04)
 * Funções de APF:
 * - ALI_COLABORADORES (Andryus Zolet - 10 PFB / Média Complexidade)
 * - CE_BUSCA_COLABORADOR (Andryus Zolet - 3 PFB / Baixa Complexidade)
 * - EE_CADASTRAR_COLABORADOR (Andryus Zolet - 4 PFB)
 *
 * @var array<int, array<string, mixed>> $employees
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $roles
 */
$filterName = $_GET['search_name'] ?? '';
$filterCpf = $_GET['search_cpf'] ?? '';
$filterDept = $_GET['search_dept'] ?? '';
$filterType = $_GET['search_type'] ?? '';
$filterStatus = $_GET['search_status'] ?? 'active';
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Gestão de Colaboradores &amp; Admissão</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Manutenção do catálogo relacional de colaboradores (ALI_COLABORADORES) e busca parametrizada (CE_BUSCA_COLABORADOR).</p>
  </div>
  <div style="display: flex; gap: 12px;">
    <a href="index.php?route=employees&action=new" class="btn btn-success">
      <i class="fa-solid fa-id-card"></i> Admissão com Receita Federal (AIE)
    </a>
    <button onclick="toggleModal('employeeModal')" class="btn">
      <i class="fa-solid fa-user-plus"></i> Cadastro Rápido (EE)
    </button>
  </div>
</div>

<!-- CE_BUSCA_COLABORADOR: DADOS DE ENTRADA (FILTROS) -->
<div class="ce-input-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
      <span class="ce-badge"><i class="fa-solid fa-filter"></i> CONSULTA EXTERNA (CE_BUSCA_COLABORADOR) — DADOS DE ENTRADA (FILTROS)</span>
      <h3 style="font-size: 16px; font-weight: 800; color: #fff;">Parâmetros de Consulta de Colaboradores</h3>
    </div>
    <span class="badge badge-info" style="font-size: 11px;">Complexidade: Baixa (3 PFB)</span>
  </div>

  <form action="index.php" method="GET">
    <input type="hidden" name="route" value="employees">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 14px;">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Nome Completo</label>
        <input type="text" name="search_name" class="form-control" value="<?= htmlspecialchars($filterName) ?>" placeholder="Buscar por nome...">
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">CPF</label>
        <input type="text" name="search_cpf" class="form-control" value="<?= htmlspecialchars($filterCpf) ?>" placeholder="000.000.000-00">
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Departamento</label>
        <select name="search_dept" class="form-control">
          <option value="">Todos os Departamentos</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $filterDept === $d['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Regime Contratual</label>
        <select name="search_type" class="form-control">
          <option value="">Todos os Regimes</option>
          <option value="CLT" <?= $filterType === 'CLT' ? 'selected' : '' ?>>CLT (Efetivo)</option>
          <option value="PJ" <?= $filterType === 'PJ' ? 'selected' : '' ?>>PJ (Pessoa Jurídica)</option>
          <option value="ESTAGIO" <?= $filterType === 'ESTAGIO' ? 'selected' : '' ?>>Estágio (Lei 11.788)</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Situação Cadastral</label>
        <select name="search_status" class="form-control">
          <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Ativos no Quadro</option>
          <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Desligados (Inativos)</option>
          <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Todos os Registros</option>
        </select>
      </div>
    </div>

    <div style="display: flex; gap: 10px; justify-content: flex-end;">
      <a href="index.php?route=employees" class="btn btn-outline" style="font-size: 12px;">
        <i class="fa-solid fa-rotate-left"></i> Limpar Filtros
      </a>
      <button type="submit" class="btn" style="font-size: 12px;">
        <i class="fa-solid fa-magnifying-glass"></i> Aplicar Filtros (CE)
      </button>
    </div>
  </form>
</div>

<!-- CE_BUSCA_COLABORADOR: DADOS DE SAÍDA (RESULTADOS) -->
<div class="card">
  <div class="card-header">
    <div>
      <span class="ce-badge"><i class="fa-solid fa-table-list"></i> CONSULTA EXTERNA (CE_BUSCA_COLABORADOR) — DADOS DE SAÍDA (RESULTADOS)</span>
      <h3 class="card-title" style="margin-top: 4px;">
        <i class="fa-solid fa-users" style="color: var(--primary);"></i> Quadro de Colaboradores Retornados
      </h3>
    </div>
    <span class="badge badge-info" style="font-size: 12px; padding: 6px 12px;"><?= count($employees) ?> colaboradores localizados</span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table">
      <thead>
        <tr>
          <th>Matrícula</th>
          <th>Nome Completo</th>
          <th>CPF (LGPD)</th>
          <th>Departamento / Cargo</th>
          <th>Regime</th>
          <th>Salário Base</th>
          <th>Banco de Horas</th>
          <th>Admissão</th>
          <th style="text-align: right;">Ações de Detalhe</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($employees)): ?>
          <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 32px;">Nenhum colaborador encontrado com os filtros aplicados.</td></tr>
        <?php else: ?>
          <?php foreach ($employees as $emp): ?>
            <tr>
              <td><span class="badge badge-outline" style="font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars($emp['matricula'] ?? 'MAT-0000') ?></span></td>
              <td>
                <strong style="color: #fff;"><?= htmlspecialchars($emp['full_name']) ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($emp['email']) ?></div>
              </td>
              <td><code style="color: #38bdf8; font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars($emp['cpf']) ?></code></td>
              <td>
                <div style="font-weight: 600; color: #fff;"><?= htmlspecialchars($emp['department_name'] ?? 'Geral') ?></div>
                <small style="color: var(--text-muted);"><?= htmlspecialchars($emp['role_name'] ?? 'Colaborador') ?></small>
              </td>
              <td>
                <span class="badge <?= $emp['employment_type'] === 'CLT' ? 'badge-success' : ($emp['employment_type'] === 'PJ' ? 'badge-info' : 'badge-warning') ?>">
                  <?= htmlspecialchars($emp['employment_type']) ?>
                </span>
              </td>
              <td><strong style="color: #34d399;">R$ <?= number_format($emp['base_salary_cents'] / 100, 2, ',', '.') ?></strong></td>
              <td>
                <span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; font-weight: 700;">
                  <i class="fa-solid fa-clock"></i> <?= round(((int)($emp['bank_hours_balance'] ?? 0)) / 60, 1) ?>h
                </span>
              </td>
              <td><?= date('d/m/Y', strtotime($emp['admission_date'])) ?></td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="index.php?route=employees&action=detail&id=<?= urlencode($emp['id']) ?>" class="btn btn-outline" style="font-size: 11px; padding: 5px 10px; margin-right: 4px;">
                  <i class="fa-solid fa-file-code" style="color: #38bdf8;"></i> eSocial S-2200
                </a>
                <a href="index.php?route=employees&action=delete&id=<?= urlencode($emp['id']) ?>" onclick="return confirm('Deseja realmente desativar este colaborador?')" class="btn btn-danger" style="font-size: 11px; padding: 5px 10px;">
                  <i class="fa-solid fa-user-xmark"></i> Desativar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Cadastro Rápido de Colaborador (EE_CADASTRAR_COLABORADOR) -->
<div id="employeeModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <div>
        <span class="badge badge-primary" style="font-size: 10px;">ENTRADA EXTERNA (EE)</span>
        <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">Cadastrar Novo Colaborador</h3>
      </div>
      <button onclick="toggleModal('employeeModal')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=employees&action=store" method="POST">
      <div class="form-group">
        <label class="form-label">Nome Completo *</label>
        <input type="text" name="full_name" class="form-control" placeholder="ex: Ana Paula Ferreira" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">CPF (com Módulo 11) *</label>
          <input type="text" name="cpf" class="form-control" placeholder="529.982.247-25" required>
        </div>
        <div class="form-group">
          <label class="form-label">E-mail Corporativo *</label>
          <input type="email" name="email" class="form-control" placeholder="nome@empresa.com.br" required>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Salário Base Contratual (R$) *</label>
          <input type="number" step="0.01" name="base_salary" class="form-control" placeholder="5500.00" required>
        </div>
        <div class="form-group">
          <label class="form-label">Regime Contratual</label>
          <select name="employment_type" class="form-control">
            <option value="CLT">CLT (Consolidação das Leis do Trabalho)</option>
            <option value="PJ">PJ (Prestador de Serviços)</option>
            <option value="ESTAGIO">Estágio (Bolsa Auxílio)</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Departamento</label>
          <select name="department_id" class="form-control">
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Cargo Inicial</label>
          <select name="role_id" class="form-control">
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?> (Nível <?= $r['hierarchy_level'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" onclick="toggleModal('employeeModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-success">Salvar e Emitir Matrícula</button>
      </div>
    </form>
  </div>
</div>
