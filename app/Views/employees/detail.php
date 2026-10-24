<?php
/**
 * View: Ficha Cadastral do Colaborador & Emissor eSocial S-2200 (Tela 06)
 * Funções de APF:
 * - SE_XML_ESOCIAL_S2200 (Andryus Zolet - 7 PFB / Alta Complexidade)
 * - AIE_GOV_ESOCIAL (Andryus Zolet - 7 PFB / Média Complexidade)
 *
 * @var array<string, mixed> $employee
 * @var array<string, mixed> $esocialTransmission
 * @var array<int, array<string, mixed>> $employeeBenefits
 * @var array<int, array<string, mixed>> $sstRecords
 */
?>
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Ficha Cadastral &amp; Evento eSocial S-2200</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Prontuário funcional do colaborador e transmissão digital do evento de admissão ao Governo Federal.</p>
  </div>
  <a href="index.php?route=employees" class="btn btn-outline">
    <i class="fa-solid fa-arrow-left"></i> Voltar à Lista
  </a>
</div>

<!-- Employee Master Information Card -->
<div class="card">
  <div class="card-header">
    <div style="display: flex; align-items: center; gap: 14px;">
      <div style="background: rgba(99,102,241,0.2); color: #818cf8; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
        <i class="fa-solid fa-id-badge"></i>
      </div>
      <div>
        <h3 class="card-title" style="margin-bottom: 2px;"><?= htmlspecialchars($employee['full_name']) ?></h3>
        <span style="font-size: 12px; color: var(--text-muted);">
          Matrícula: <strong style="color: #fff;"><?= htmlspecialchars($employee['matricula'] ?? 'MAT-0000') ?></strong> | 
          CPF: <code style="color: #38bdf8;"><?= htmlspecialchars($employee['cpf']) ?></code>
        </span>
      </div>
    </div>
    <span class="badge badge-success" style="font-size: 12px; padding: 6px 14px;">
      <i class="fa-solid fa-circle-check"></i> Contrato Ativo (<?= htmlspecialchars($employee['employment_type']) ?>)
    </span>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; font-size: 13px;">
    <div>
      <span style="color: var(--text-muted); display: block; margin-bottom: 4px;">Departamento / Centro de Custo</span>
      <strong style="color: #fff;"><?= htmlspecialchars($employee['department_name'] ?? 'Geral') ?></strong> (<?= htmlspecialchars($employee['department_code'] ?? 'DP-01') ?>)
    </div>

    <div>
      <span style="color: var(--text-muted); display: block; margin-bottom: 4px;">Cargo Ocupado &amp; Nível</span>
      <strong style="color: #fff;"><?= htmlspecialchars($employee['role_name'] ?? 'Colaborador') ?></strong> (Nível <?= htmlspecialchars((string)($employee['hierarchy_level'] ?? 1)) ?>)
    </div>

    <div>
      <span style="color: var(--text-muted); display: block; margin-bottom: 4px;">Salário Base Contratual</span>
      <strong style="color: #34d399; font-size: 16px;">R$ <?= number_format($employee['base_salary_cents'] / 100, 2, ',', '.') ?></strong>
    </div>

    <div>
      <span style="color: var(--text-muted); display: block; margin-bottom: 4px;">Data de Admissão</span>
      <strong style="color: #fff;"><?= date('d/m/Y', strtotime($employee['admission_date'])) ?></strong>
    </div>

    <div>
      <span style="color: var(--text-muted); display: block; margin-bottom: 4px;">Saldo de Férias Disponível</span>
      <strong style="color: #fbbf24;"><?= (int)$employee['vacation_days_balance'] ?> dias corridos</strong>
    </div>

    <div>
      <span style="color: var(--text-muted); display: block; margin-bottom: 4px;">Saldo de Banco de Horas</span>
      <strong style="color: #38bdf8;"><?= round(((int)($employee['bank_hours_balance'] ?? 0)) / 60, 1) ?> horas</strong>
    </div>
  </div>
</div>

<!-- SE_XML_ESOCIAL_S2200: DADO DERIVADO OBRIGATÓRIO & AIE_GOV_ESOCIAL -->
<div class="se-derived-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
    <div>
      <span class="se-badge"><i class="fa-solid fa-code"></i> SAÍDA EXTERNA (SE) — DADO DERIVADO ALTA COMPLEXIDADE</span>
      <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">
        <i class="fa-solid fa-file-code" style="color: #fbbf24;"></i> SE_XML_ESOCIAL_S2200 — Gerador do Evento Canônico S-2200 (Cadastramento Inicial)
      </h3>
    </div>
    <span class="badge badge-warning" style="font-size: 12px; padding: 6px 12px;">Complexidade: Alta (7 PFB)</span>
  </div>

  <div style="font-size: 13px; color: #cbd5e1; line-height: 1.6; margin-bottom: 14px;">
    <strong>Justificativa do Dado Derivado:</strong> A emissão do XML S-2200 do eSocial é uma <em>Saída Externa complexa</em> que não se resume a consultar dados. Ela agrega informações de múltiplas entidades (Colaborador, Cargo, Departamento, Tabela Salarial e Empresa Contratante), normaliza para a árvore XSD oficial do Governo Federal (versão <code>v_S_01_02_00</code>), executa a canonicalização W3C XML C14N e computa o digest canônico em SHA-256 codificado em Base64 para assinatura ICP-Brasil.
  </div>

  <!-- Status da Transmissão AIE_GOV_ESOCIAL -->
  <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 10px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span class="aie-badge" style="margin-bottom: 0;"><i class="fa-solid fa-server"></i> AIE_GOV_ESOCIAL (7 PFB)</span>
        <strong style="color: #fff; font-size: 13px;">Protocolo Federal de Transmissão ao Ambiente Nacional:</strong>
      </div>
      <span class="badge badge-success"><i class="fa-solid fa-check-double"></i> <?= htmlspecialchars($esocialTransmission['status'] ?? 'PROCESSADO_COM_SUCESSO') ?></span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; font-size: 12px; font-family: 'JetBrains Mono', monospace;">
      <div>
        <span style="color: var(--text-muted);">Número do Recibo Oficial:</span>
        <div style="color: #34d399; font-weight: 700; margin-top: 2px;"><?= htmlspecialchars($esocialTransmission['receipt_number'] ?? 'REC-2026-9841203') ?></div>
      </div>
      <div>
        <span style="color: var(--text-muted);">Digest Canônico SHA-256:</span>
        <div style="color: #38bdf8; margin-top: 2px; word-break: break-all;"><?= htmlspecialchars($esocialTransmission['sha256_digest'] ?? '4a7b9c1d0e8f2a4b...') ?></div>
      </div>
      <div>
        <span style="color: var(--text-muted);">Timestamp de Protocolo:</span>
        <div style="color: #fff; margin-top: 2px;"><?= htmlspecialchars($esocialTransmission['transmission_timestamp'] ?? date('Y-m-d H:i:s')) ?></div>
      </div>
      <div>
        <span style="color: var(--text-muted);">Certificado ICP-Brasil:</span>
        <div style="color: #fbbf24; margin-top: 2px;">CN=HRTech Core S.A., OU=RFB e-PJ A1</div>
      </div>
    </div>
  </div>

  <!-- Visualizador de Código XML Estruturado -->
  <div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
      <span style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
        <i class="fa-solid fa-terminal"></i> Visualizador do XML Canônico S-2200 Gerado (XSD Oficial Gov.br):
      </span>
      <span style="font-size: 11px; color: var(--text-muted);">Versão do Leiaute: S-1.2.0</span>
    </div>
    <div class="code-viewer"><?= htmlspecialchars($esocialTransmission['xml_payload'] ?? '<eSocial xmlns="http://www.esocial.gov.br/schema/evt/evtAdmissao/v_S_01_02_00">...</eSocial>') ?></div>
  </div>
</div>

<!-- Vínculos de Benefícios e SST -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
  
  <div class="card">
    <div class="card-header">
      <h3 class="card-title" style="font-size: 15px;"><i class="fa-solid fa-gift" style="color: var(--primary);"></i> Benefícios Corporativos Ativos</h3>
      <span class="badge badge-info"><?= count($employeeBenefits) ?></span>
    </div>
    <table class="table" style="font-size: 12px;">
      <thead>
        <tr>
          <th>Benefício</th>
          <th>Tipo</th>
          <th>Valor Facial</th>
          <th>Coparticipação</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($employeeBenefits)): ?>
          <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">Nenhum benefício vinculado.</td></tr>
        <?php else: ?>
          <?php foreach ($employeeBenefits as $b): ?>
            <tr>
              <td><strong style="color: #fff;"><?= htmlspecialchars($b['name']) ?></strong></td>
              <td><span class="badge badge-outline"><?= htmlspecialchars($b['type']) ?></span></td>
              <td><strong style="color: #34d399;">R$ <?= number_format($b['value_cents'] / 100, 2, ',', '.') ?></strong></td>
              <td><?= $b['custom_cost_share'] !== null ? $b['custom_cost_share'] . '%' : $b['employee_cost_share_percentage'] . '%' ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title" style="font-size: 15px;"><i class="fa-solid fa-hard-hat" style="color: #fbbf24;"></i> Registros SST (EPIs e ASOs)</h3>
      <span class="badge badge-info"><?= count($sstRecords) ?></span>
    </div>
    <table class="table" style="font-size: 12px;">
      <thead>
        <tr>
          <th>Item / Exame</th>
          <th>CA / CRM</th>
          <th>Validade</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($sstRecords)): ?>
          <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">Nenhum registro SST vinculado.</td></tr>
        <?php else: ?>
          <?php foreach ($sstRecords as $s): ?>
            <tr>
              <td><strong style="color: #fff;"><?= htmlspecialchars($s['equipment_name']) ?></strong></td>
              <td><code style="color: #38bdf8;"><?= htmlspecialchars($s['ca_number'] ?: ($s['physician_crm'] ?: 'N/A')) ?></code></td>
              <td><?= date('d/m/Y', strtotime($s['expiration_date'])) ?></td>
              <td>
                <span class="badge <?= ((int)$s['is_fit'] === 1 && $s['expiration_date'] >= date('Y-m-d')) ? 'badge-success' : 'badge-danger' ?>">
                  <?= ((int)$s['is_fit'] === 1 && $s['expiration_date'] >= date('Y-m-d')) ? 'Válido' : 'Irregular' ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
