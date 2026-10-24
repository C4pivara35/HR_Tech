<?php
/**
 * View: Organograma Institucional Interativo & Gestão de Departamentos (Tela 07)
 * Funções de APF:
 * - ALI_DEPARTAMENTOS_CARGOS (Andryus Zolet - 7 PFB / Baixa Complexidade)
 * - EE_CRIAR_DEPARTAMENTO_CARGO (Andryus Zolet - 3 PFB / Baixa Complexidade)
 * - CE_ORGANOGRAMA_GRAFICO (Andryus Zolet - 3 PFB / Baixa Complexidade)
 *
 * @var array<int, array<string, mixed>> $departments
 * @var array<string, array<int, array<string, mixed>>> $rolesByDept
 * @var array<int, array<string, mixed>> $potentialManagers
 */
$filterDept = $_GET['dept_filter'] ?? '';
$filterSearch = $_GET['search_term'] ?? '';
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Organograma Institucional &amp; Alçadas Hierárquicas</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Representação gráfica da arquitetura departamental (ALI_DEPARTAMENTOS_CARGOS) e consulta visual da estrutura corporativa (CE_ORGANOGRAMA_GRAFICO).</p>
  </div>
  <div style="display: flex; gap: 12px;">
    <button onclick="toggleModal('modalDept')" class="btn btn-outline">
      <i class="fa-solid fa-folder-plus"></i> Novo Departamento (EE)
    </button>
    <button onclick="toggleModal('modalRole')" class="btn">
      <i class="fa-solid fa-briefcase"></i> Novo Cargo (EE)
    </button>
  </div>
</div>

<!-- CE_ORGANOGRAMA_GRAFICO: DADOS DE ENTRADA (FILTROS) -->
<div class="ce-input-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
      <span class="ce-badge"><i class="fa-solid fa-filter"></i> CONSULTA EXTERNA (CE_ORGANOGRAMA_GRAFICO) — DADOS DE ENTRADA (FILTROS)</span>
      <h3 style="font-size: 16px; font-weight: 800; color: #fff;">Filtros de Navegação da Árvore Organizacional</h3>
    </div>
    <span class="badge badge-info">Complexidade: Baixa (3 PFB)</span>
  </div>

  <form action="index.php" method="GET">
    <input type="hidden" name="route" value="organogram">
    <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 14px; align-items: flex-end;">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Buscar Departamento ou Código de Centro de Custo</label>
        <input type="text" name="search_term" class="form-control" value="<?= htmlspecialchars($filterSearch) ?>" placeholder="ex: TI, Operações, DP-01...">
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Filtrar por Unidade Organizacional Específica</label>
        <select name="dept_filter" class="form-control">
          <option value="">Todas as Unidades (Árvore Completa)</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $filterDept === $d['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display: flex; gap: 8px;">
        <a href="index.php?route=organogram" class="btn btn-outline" style="font-size: 12px;">
          <i class="fa-solid fa-rotate-left"></i> Limpar
        </a>
        <button type="submit" class="btn" style="font-size: 12px;">
          <i class="fa-solid fa-magnifying-glass"></i> Filtrar Árvore (CE)
        </button>
      </div>
    </div>
  </form>
</div>

<!-- CE_ORGANOGRAMA_GRAFICO: DADOS DE SAÍDA (ÁRVORE VISUAL) -->
<div class="card">
  <div class="card-header">
    <div>
      <span class="ce-badge"><i class="fa-solid fa-sitemap"></i> CONSULTA EXTERNA (CE_ORGANOGRAMA_GRAFICO) — DADOS DE SAÍDA (RESULTADOS)</span>
      <h3 class="card-title" style="margin-top: 4px;">
        <i class="fa-solid fa-network-wired" style="color: #a78bfa;"></i> Estrutura Gráfica de Governança &amp; Cargos
      </h3>
    </div>
    <span class="badge badge-info"><?= count($departments) ?> unidades organizacionais</span>
  </div>

  <!-- Raiz Institucional (Diretoria Executiva) -->
  <div style="text-align: center; margin-bottom: 30px;">
    <div style="display: inline-block; background: linear-gradient(135deg, #4f46e5, #7c3aed); border: 2px solid #a5b4fc; border-radius: 16px; padding: 18px 36px; box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4); text-align: center; min-width: 320px;">
      <div style="font-size: 11px; font-weight: 800; color: #e0e7ff; text-transform: uppercase; letter-spacing: 1px;">DIRETORIA EXECUTIVA / CONSELHO</div>
      <h2 style="font-size: 20px; font-weight: 900; color: #fff; margin: 4px 0;">HRTech Core Global S.A.</h2>
      <div style="font-size: 12px; color: #cbd5e1;">LPS Segment: <strong style="color: #38bdf8;"><?= strtoupper(str_replace('tenant-', '', $_SESSION['active_role'] ?? 'tech')) ?></strong></div>
    </div>
    <div style="width: 2px; height: 32px; background: var(--border-color); margin: 0 auto;"></div>
  </div>

  <!-- Grid Hierárquico dos Departamentos -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <?php foreach ($departments as $dept): ?>
      <?php $deptRoles = $rolesByDept[$dept['id']] ?? []; ?>
      <div class="tree-node">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
          <div>
            <span class="badge badge-outline" style="font-size: 10px; font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars($dept['code']) ?></span>
            <h4 style="font-size: 16px; font-weight: 800; color: #fff; margin-top: 4px;"><?= htmlspecialchars($dept['name']) ?></h4>
          </div>
          <span class="badge badge-success" style="font-size: 11px;">
            <i class="fa-solid fa-users"></i> <?= (int)$dept['headcount'] ?> no quadro
          </span>
        </div>

        <div style="margin-bottom: 12px; font-size: 12px;">
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Gestor Titular Responsável:</span>
          <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-user-tie" style="color: #38bdf8;"></i>
            <strong style="color: #fff;"><?= htmlspecialchars($dept['manager_name'] ?? 'Diretoria Geral / Indefinido') ?></strong>
          </div>
        </div>

        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; justify-content: space-between; font-size: 12px;">
          <div>
            <span style="color: var(--text-muted);">Cargos Mapeados:</span>
            <strong style="color: #fff; display: block;"><?= count($deptRoles) ?> alçadas</strong>
          </div>
          <div style="text-align: right;">
            <span style="color: var(--text-muted);">Média Salarial Base:</span>
            <strong style="color: #34d399; display: block;">R$ <?= number_format((float)($dept['avg_salary'] ?? 0), 2, ',', '.') ?></strong>
          </div>
        </div>

        <!-- Alçadas Hierárquicas dos Cargos -->
        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">
          Alçadas &amp; Níveis Salariais (roles):
        </div>
        <div style="display: flex; flex-direction: column; gap: 6px;">
          <?php if (empty($deptRoles)): ?>
            <div style="font-size: 11px; color: var(--text-muted); font-style: italic;">Nenhum cargo específico associado.</div>
          <?php else: ?>
            <?php foreach ($deptRoles as $r): ?>
              <div style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; padding: 6px 10px; border-radius: 6px; font-size: 11px;">
                <div>
                  <span style="color: #a78bfa; font-weight: 800; margin-right: 6px;">N<?= $r['hierarchy_level'] ?></span>
                  <span style="color: #cbd5e1; font-weight: 600;"><?= htmlspecialchars($r['name']) ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="color: var(--text-muted);"><?= (int)$r['occupants_count'] ?> ocupantes</span>
                  <span style="color: #34d399; font-weight: 700;"><?= isset($r['base_salary_cents']) ? 'R$ ' . number_format($r['base_salary_cents'] / 100, 2, ',', '.') : 'Alçada N' . $r['hierarchy_level'] ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal 1: Criar Departamento (EE_CRIAR_DEPARTAMENTO_CARGO) -->
<div id="modalDept" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <div>
        <span class="badge badge-primary" style="font-size: 10px;">ENTRADA EXTERNA (EE)</span>
        <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">Criar Unidade Organizacional / Centro de Custo</h3>
      </div>
      <button onclick="toggleModal('modalDept')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=organogram&action=create_department" method="POST">
      <div class="form-group">
        <label class="form-label">Nome do Departamento *</label>
        <input type="text" name="name" class="form-control" placeholder="ex: Engenharia de Inteligência Artificial" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Código / Centro de Custo *</label>
          <input type="text" name="code" class="form-control" placeholder="ex: DP-AI-01" required>
        </div>
        <div class="form-group">
          <label class="form-label">Gestor Titular Responsável</label>
          <select name="manager_employee_id" class="form-control">
            <option value="">Sem gestor definido</option>
            <?php foreach ($potentialManagers as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?> (<?= htmlspecialchars($m['email']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" onclick="toggleModal('modalDept')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-success">Salvar Departamento</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 2: Criar Cargo (EE_CRIAR_DEPARTAMENTO_CARGO) -->
<div id="modalRole" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <div>
        <span class="badge badge-primary" style="font-size: 10px;">ENTRADA EXTERNA (EE)</span>
        <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">Cadastrar Novo Cargo &amp; Alçada Salarial</h3>
      </div>
      <button onclick="toggleModal('modalRole')" style="background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="index.php?route=organogram&action=create_role" method="POST">
      <div class="form-group">
        <label class="form-label">Departamento Pertencente *</label>
        <select name="department_id" class="form-control" required>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Título Oficial do Cargo *</label>
        <input type="text" name="name" class="form-control" placeholder="ex: Arquiteto de Software Cloud Sênior" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Nível de Alçada Hierárquica (1 = Operacional, 5 = Diretoria)</label>
          <input type="number" min="1" max="5" name="hierarchy_level" class="form-control" value="2" required>
        </div>
        <div class="form-group">
          <label class="form-label">Piso Salarial do Cargo (R$) *</label>
          <input type="number" step="0.01" name="base_salary" class="form-control" placeholder="9500.00" required>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" onclick="toggleModal('modalRole')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-success">Salvar Cargo &amp; Alçada</button>
      </div>
    </form>
  </div>
</div>
