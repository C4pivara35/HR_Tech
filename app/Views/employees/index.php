<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Gestão de Colaboradores (CRUD 3 & 4)</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Controle completo de matrículas, dados contratuais e cargos no SQLite.</p>
  </div>
  <button onclick="toggleModal('employeeModal')" class="btn">
    <i class="fa-solid fa-user-plus"></i> Novo Colaborador
  </button>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-users" style="color: var(--primary);"></i> Quadro de Colaboradores Cadastrados</h3>
    <span class="badge badge-info"><?= count($employees) ?> Colaboradores</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Nome Completo</th>
        <th>CPF</th>
        <th>E-mail</th>
        <th>Departamento / Cargo</th>
        <th>Tipo Contrato</th>
        <th>Salário Base</th>
        <th>Admissão</th>
        <th style="text-align: right;">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($employees)): ?>
        <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">Nenhum colaborador encontrado para esta empresa.</td></tr>
      <?php else: ?>
        <?php foreach ($employees as $emp): ?>
          <tr>
            <td>
              <strong style="color: #fff;"><?= htmlspecialchars($emp['full_name']) ?></strong>
            </td>
            <td><code style="color: #38bdf8;"><?= htmlspecialchars($emp['cpf']) ?></code></td>
            <td><?= htmlspecialchars($emp['email']) ?></td>
            <td>
              <div><?= htmlspecialchars($emp['department_name'] ?? 'Geral') ?></div>
              <small style="color: var(--text-muted);"><?= htmlspecialchars($emp['role_name'] ?? 'Colaborador') ?></small>
            </td>
            <td><span class="badge badge-info"><?= htmlspecialchars($emp['employment_type']) ?></span></td>
            <td><strong style="color: #34d399;">R$ <?= number_format($emp['base_salary_cents'] / 100, 2, ',', '.') ?></strong></td>
            <td><?= date('d/m/Y', strtotime($emp['admission_date'])) ?></td>
            <td style="text-align: right;">
              <a href="index.php?route=employees&action=delete&id=<?= $emp['id'] ?>" onclick="return confirm('Deseja realmente desativar este colaborador?')" class="btn btn-danger" style="font-size: 11px; padding: 4px 8px;">
                <i class="fa-solid fa-user-xmark"></i> Desativar
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Novo Colaborador -->
<div id="employeeModal" class="modal-backdrop">
  <div class="modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 18px; font-weight: 800; color: #fff;">Cadastrar Novo Colaborador</h3>
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
          <label class="form-label">CPF *</label>
          <input type="text" name="cpf" class="form-control" placeholder="000.000.000-00" required>
        </div>
        <div class="form-group">
          <label class="form-label">E-mail Corporativo *</label>
          <input type="email" name="email" class="form-control" placeholder="nome@empresa.com.br" required>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label">Salário Base (R$) *</label>
          <input type="number" step="0.01" name="base_salary" class="form-control" placeholder="5500.00" required>
        </div>
        <div class="form-group">
          <label class="form-label">Tipo de Contrato</label>
          <select name="employment_type" class="form-control">
            <option value="CLT">CLT (Consolidação das Leis do Trabalho)</option>
            <option value="PJ">PJ (Pessoa Jurídica / Prestação de Serviço)</option>
            <option value="INTERN">Estágio (Lei 11.788/2008)</option>
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
          <label class="form-label">Cargo / Função</label>
          <select name="role_id" class="form-control">
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="margin-top: 24px; text-align: right; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" onclick="toggleModal('employeeModal')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Salvar no SQLite</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id) {
  document.getElementById(id).classList.toggle('active');
}
</script>
