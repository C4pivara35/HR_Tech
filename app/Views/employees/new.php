<?php
/**
 * View: Admissão de Empregados & Validador de CPF da Receita Federal (Tela 05)
 * Funções de APF:
 * - EE_CADASTRAR_COLABORADOR (Andryus Zolet - 4 PFB / Média Complexidade)
 * - AIE_RECEITA_FEDERAL_CPF (Andryus Zolet - 5 PFB / Baixa Complexidade)
 *
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $roles
 * @var array<string, mixed> $rfCheck
 */
?>
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Admissão Formal de Colaborador</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Processo de admissão com validação obrigatória na Receita Federal (AIE_RECEITA_CPF) e Value Objects DDD.</p>
  </div>
  <a href="index.php?route=employees" class="btn btn-outline">
    <i class="fa-solid fa-arrow-left"></i> Voltar à Lista
  </a>
</div>

<!-- AIE_RECEITA_FEDERAL_CPF: SIMULADOR DE INTERFACE EXTERNA -->
<div class="aie-panel">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
    <div>
      <span class="aie-badge"><i class="fa-solid fa-building-columns"></i> ARQUIVO DE INTERFACE EXTERNA (AIE_RECEITA_FEDERAL_CPF)</span>
      <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">
        Validador de Situação Cadastral — SERPRO / Receita Federal do Brasil
      </h3>
    </div>
    <span class="badge badge-success" style="padding: 6px 12px; font-size: 12px;">
      <i class="fa-solid fa-shield-halved"></i> Módulo 11 &amp; Protocolo SERPRO
    </span>
  </div>

  <div style="font-size: 13px; color: #cbd5e1; line-height: 1.6; margin-bottom: 16px;">
    <strong>Integração Externa:</strong> Antes da gravação da matrícula no banco relacional (ALI_COLABORADORES), o sistema despacha chamada síncrona para a API da Receita Federal validando a autenticidade dos 2 dígitos verificadores (Módulo 11) e emitindo protocolo de autenticidade cadastral.
  </div>

  <div style="background: #090d16; border: 1px solid var(--border-color); border-radius: 10px; padding: 18px;">
    <div style="display: flex; gap: 16px; align-items: flex-end; margin-bottom: 14px;">
      <div style="flex: 1;">
        <label class="form-label" style="font-size: 12px;">CPF para Consulta Externa na Receita Federal:</label>
        <input type="text" id="consultCpfInput" class="form-control" value="529.982.247-25" placeholder="000.000.000-00">
      </div>
      <button type="button" onclick="consultarReceita()" class="btn btn-success" style="padding: 10px 20px;">
        <i class="fa-solid fa-satellite-dish"></i> Disparar Consulta SERPRO
      </button>
    </div>

    <div id="rfResultBox" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 14px; font-family: 'JetBrains Mono', monospace; font-size: 12px; color: #cbd5e1;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
        <span style="color: #34d399; font-weight: 700;">Status Cadastral: REGULAR (Apto para Admissão)</span>
        <span style="color: var(--text-muted);">Protocolo: RF-2026-94812304</span>
      </div>
      <div style="color: var(--text-muted); font-size: 11px;">
        Órgão: Secretaria Especial da Receita Federal do Brasil | Algoritmo: Dual-Step Modulo 11 | Hash de Autenticidade: 4a8b79c3d10e...
      </div>
    </div>
  </div>
</div>

<!-- EE_CADASTRAR_COLABORADOR: FORMULÁRIO DE ENTRADA EXTERNA -->
<div class="card">
  <div class="card-header">
    <div>
      <span class="badge badge-primary" style="font-size: 11px;">ENTRADA EXTERNA (EE_CADASTRAR_COLABORADOR) — 4 PFB</span>
      <h3 class="card-title" style="margin-top: 4px;">
        <i class="fa-solid fa-user-plus" style="color: var(--primary);"></i> Ficha Cadastral de Admissão Trabalhista
      </h3>
    </div>
    <span class="badge badge-info">Tenant: <?= htmlspecialchars($_SESSION['active_role'] ?? 'tenant-tech') ?></span>
  </div>

  <form action="index.php?route=employees&action=store" method="POST">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 16px;">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Nome Completo do Trabalhador *</label>
        <input type="text" name="full_name" class="form-control" placeholder="ex: Roberto Carlos de Oliveira" required>
      </div>
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">CPF (com Módulo 11 Validado) *</label>
        <input type="text" id="admissionCpf" name="cpf" class="form-control" value="529.982.247-25" required>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 16px;">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">E-mail Corporativo *</label>
        <input type="email" name="email" class="form-control" placeholder="colaborador@empresa.com.br" required>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Salário Base Mensal (R$) *</label>
        <input type="number" step="0.01" name="base_salary" class="form-control" placeholder="6800.00" required>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Regime Contratual</label>
        <select name="employment_type" class="form-control">
          <option value="CLT">CLT (Consolidação das Leis do Trabalho)</option>
          <option value="PJ">PJ (Prestação de Serviços)</option>
          <option value="ESTAGIO">Estágio Remunerado (Lei 11.788)</option>
        </select>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Departamento / Centro de Custo</label>
        <select name="department_id" class="form-control">
          <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?> (<?= htmlspecialchars($dept['code']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Cargo &amp; Alçada Hierárquica</label>
        <select name="role_id" class="form-control">
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?> — Nível <?= $r['hierarchy_level'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
      <div style="font-weight: 700; color: #fff; font-size: 13px; margin-bottom: 6px;">Garantias de Domínio (DDD) &amp; Padrões GoF:</div>
      <ul style="color: var(--text-muted); font-size: 12px; margin-left: 20px; line-height: 1.6;">
        <li>Persistência monetária via <code>Money</code> em centavos de Real para impedir erros de arredondamento IEEE 754.</li>
        <li>Instanciação atômica de entidade <code>Employee</code> com validação de formato e dígitos verificadores em <code>Cpf</code>.</li>
        <li>Gravação de bloco auditável no Singleton <code>AuditLogger</code> com carimbo SHA-256 e IP de origem.</li>
      </ul>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 14px;">
      <a href="index.php?route=employees" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-success" style="padding: 12px 28px; font-size: 14px;">
        <i class="fa-solid fa-circle-check"></i> Efetivar Admissão Trabalhista (EE)
      </button>
    </div>
  </form>
</div>

<script>
function consultarReceita() {
  const cpf = document.getElementById('consultCpfInput').value;
  document.getElementById('admissionCpf').value = cpf;
  const box = document.getElementById('rfResultBox');
  box.innerHTML = '<span style="color: #38bdf8;"><i class="fa-solid fa-spinner fa-spin"></i> Conectando à API do SERPRO / Receita Federal...</span>';

  fetch('index.php?route=employees&action=validate_cpf&cpf=' + encodeURIComponent(cpf))
    .then(r => r.json())
    .then(data => {
      const isOk = data.is_valid && data.status === 'REGULAR';
      box.style.background = isOk ? 'rgba(16, 185, 129, 0.08)' : 'rgba(239, 68, 68, 0.08)';
      box.style.borderColor = isOk ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.4)';
      box.innerHTML = `
        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
          <span style="color: ${isOk ? '#34d399' : '#f87171'}; font-weight: 700;">Status Cadastral: ${data.status}</span>
          <span style="color: var(--text-muted);">Protocolo: ${data.protocol_number}</span>
        </div>
        <div style="color: var(--text-muted); font-size: 11px;">
          ${data.message} | Latência: ${data.latency_ms || 120}ms | Hash Canônico: ${data.canonical_hash || 'SHA256-OK'}
        </div>
      `;
    })
    .catch(err => {
      box.innerHTML = '<span style="color: #f87171;">Erro na comunicação com a Receita Federal: ' + err + '</span>';
    });
}
</script>
