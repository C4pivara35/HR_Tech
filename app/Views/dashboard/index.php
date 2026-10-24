<?php
/**
 * View: Dashboard Corporativo & Painel Executivo de RH (Tela 03)
 * Funções de APF:
 * - SE_REL_TURNO_CONTRATO (Andryus Zolet - 4 PFB / Baixa Complexidade)
 * - Visão Geral ALI_COLABORADORES, ALI_ESPELHO_PONTO, ALI_FERIAS_CONCESSOES, ALI_BENEFICIOS_CONTRATOS
 *
 * @var int|string $employeeCount
 * @var int|string $timeLogCount
 * @var int|string $pendingVacations
 * @var int|string $benefitCount
 * @var array<string, mixed> $turnover
 * @var float $monthlyPayrollGross
 * @var array<int, array<string, mixed>> $typeDistribution
 */
?>
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end;">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Dashboard Corporativo &amp; Gestão de RH</h1>
    <p style="color: var(--text-muted); font-size: 14px;">Visão executiva do tenant ativo, indicadores operacionais e apuração analítica de turnover.</p>
  </div>
  <div>
    <span class="badge badge-info" style="padding: 6px 12px; font-size: 12px;">
      <i class="fa-solid fa-chart-line"></i> Painel Executivo PUCPR
    </span>
  </div>
</div>

<!-- Stat Cards -->
<div class="grid-4">
  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(99,102,241,0.15); color: #818cf8;">
      <i class="fa-solid fa-users"></i>
    </div>
    <div>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">COLABORADORES ATIVOS</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 2px;"><?= $employeeCount ?? 0 ?></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: #34d399;">
      <i class="fa-solid fa-clock"></i>
    </div>
    <div>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">BATIDAS DE PONTO (671)</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 2px;"><?= $timeLogCount ?? 0 ?></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #fbbf24;">
      <i class="fa-solid fa-umbrella-beach"></i>
    </div>
    <div>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">FÉRIAS PENDENTES</span>
      <h3 style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 2px;"><?= $pendingVacations ?? 0 ?></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: rgba(236,72,153,0.15); color: #f472b6;">
      <i class="fa-solid fa-money-bill-wave"></i>
    </div>
    <div>
      <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">FOLHA MENSAL BASE</span>
      <h3 style="font-size: 22px; font-weight: 800; color: #fff; margin-top: 2px;">R$ <?= number_format($monthlyPayrollGross ?? 0, 2, ',', '.') ?></h3>
    </div>
  </div>
</div>

<!-- SE_REL_TURNO_CONTRATO — DADO DERIVADO OBRIGATÓRIO (ANDRYUS ZOLET - 4 PFB) -->
<div class="se-derived-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
    <div>
      <span class="se-badge"><i class="fa-solid fa-square-root-variable"></i> SAÍDA EXTERNA (SE) — DADO DERIVADO MATEMÁTICO</span>
      <h3 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 4px;">
        <i class="fa-solid fa-arrow-right-arrow-left" style="color: #fbbf24;"></i> SE_REL_TURNO_CONTRATO — Apuração da Taxa de Turnover &amp; Rotatividade Contratual
      </h3>
    </div>
    <span class="badge badge-warning" style="font-size: 12px; padding: 6px 12px;">Complexidade: Baixa (4 PFB)</span>
  </div>

  <div style="font-size: 13px; color: #cbd5e1; line-height: 1.6; margin-bottom: 14px;">
    <strong>Justificativa do Dado Derivado:</strong> A taxa de rotatividade (turnover) não existe como atributo persistido bruto no banco relacional. Constitui um <em>dado derivado algorítmico</em> resultante da integração temporal entre o fluxo dinâmico de admissões, rescisões homologadas e o efetivo médio ponderado no período, parametrizado pela fórmula clássica de Recursos Humanos.
  </div>

  <div class="se-formula">
    <div style="color: #94a3b8; font-size: 11px; margin-bottom: 4px; text-transform: uppercase; font-weight: 700;">Fórmula Matemática Aplicada:</div>
    <strong>Turnover (%) = [ ( Admissões + Desligamentos ) / 2 ] / Efetivo Médio * 100</strong>
  </div>

  <!-- Memória de Cálculo Auditável -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 16px 0;">
    <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px;">
      <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">ADMISSÕES NO ANO</div>
      <div style="font-size: 20px; font-weight: 800; color: #34d399; margin-top: 4px;"><?= $turnover['admissions'] ?? 0 ?> novos colaboradores</div>
    </div>

    <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px;">
      <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">DESLIGAMENTOS (RESCISÕES)</div>
      <div style="font-size: 20px; font-weight: 800; color: #f87171; margin-top: 4px;"><?= $turnover['terminations'] ?? 0 ?> colaboradores</div>
    </div>

    <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px;">
      <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">EFETIVO MÉDIO PONDERADO</div>
      <div style="font-size: 20px; font-weight: 800; color: #38bdf8; margin-top: 4px;"><?= number_format($turnover['average_headcount'] ?? 1, 1) ?> colaboradores</div>
    </div>

    <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.5); border-radius: 8px; padding: 12px;">
      <div style="font-size: 11px; color: #fbbf24; font-weight: 800;">TAXA DE TURNOVER DERIVADA</div>
      <div style="font-size: 24px; font-weight: 900; color: #fbbf24; margin-top: 4px;"><?= number_format($turnover['turnover_rate_percent'] ?? 0, 2) ?>%</div>
    </div>
  </div>

  <!-- Breakdown por Departamento -->
  <?php if (!empty($turnover['by_department'])): ?>
    <div style="margin-top: 14px;">
      <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
        <i class="fa-solid fa-chart-pie"></i> Distribuição e Média Salarial por Unidade Organizacional (ALI_DEPARTAMENTOS_CARGOS):
      </div>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
        <?php foreach ($turnover['by_department'] as $dept): ?>
          <div style="background: #0f172a; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-weight: 700; color: #fff; font-size: 13px;"><?= htmlspecialchars($dept['department_name'] ?? 'N/A') ?></div>
              <div style="font-size: 11px; color: var(--text-muted);"><?= (int)($dept['headcount'] ?? 0) ?> colaboradores ativos</div>
            </div>
            <div style="text-align: right;">
              <div style="font-size: 12px; font-weight: 800; color: #34d399;">R$ <?= number_format((float)($dept['avg_salary'] ?? 0), 2, ',', '.') ?></div>
              <div style="font-size: 10px; color: var(--text-muted);">Média Salarial</div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
  
  <!-- Quick Actions Card -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fa-solid fa-bolt" style="color: var(--warning);"></i> Ações Rápidas de Operação (Transações EE &amp; CE)</h3>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
      <a href="index.php?route=employees&action=new" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-user-plus" style="font-size: 18px; color: var(--primary);"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Admitir Colaborador (EE)</div>
          <div style="font-size: 11px; color: var(--text-muted);">Com validação de CPF na Receita Federal</div>
        </div>
      </a>

      <a href="index.php?route=organogram" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-sitemap" style="font-size: 18px; color: #a78bfa;"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Organograma Gráfico (CE)</div>
          <div style="font-size: 11px; color: var(--text-muted);">Visualizador hierárquico de cargos</div>
        </div>
      </a>

      <a href="index.php?route=timelogs" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-fingerprint" style="font-size: 18px; color: var(--success);"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Bater Ponto 671 &amp; NTP (EE)</div>
          <div style="font-size: 11px; color: var(--text-muted);">Espelho mensal com hora noturna 52m30s</div>
        </div>
      </a>

      <a href="index.php?route=vacations" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-calendar-check" style="font-size: 18px; color: var(--warning);"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Férias &amp; Recibo de 1/3 (SE)</div>
          <div style="font-size: 11px; color: var(--text-muted);">Com abono pecuniário indenizado 10d</div>
        </div>
      </a>

      <a href="index.php?route=payroll" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-calculator" style="font-size: 18px; color: var(--info);"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Holerite Progressivo (SE)</div>
          <div style="font-size: 11px; color: var(--text-muted);">Motor INSS 4 faixas &amp; IRRF</div>
        </div>
      </a>

      <a href="index.php?route=safety" class="btn btn-outline" style="justify-content: flex-start; padding: 16px;">
        <i class="fa-solid fa-hard-hat" style="font-size: 18px; color: #fbbf24;"></i>
        <div>
          <div style="font-weight: 700; color: #fff;">Catraca Fabril SST (SE)</div>
          <div style="font-size: 11px; color: var(--text-muted);">Semáforo impeditivo por ASO/EPI vencido</div>
        </div>
      </a>
    </div>
  </div>

  <!-- Software Product Line (LPS) Summary -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fa-solid fa-layer-group" style="color: var(--primary);"></i> Variabilidade da LPS</h3>
    </div>
    <div style="font-size: 13px; color: var(--text-muted); display: flex; flex-direction: column; gap: 12px;">
      <p>O <strong>Motor de Variabilidade (`LpsVariabilityEngine`)</strong> adapta a arquitetura da plataforma em tempo de execução para os 3 perfis de mercado da PUCPR:</p>
      
      <div style="background: #0f172a; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
        <strong style="color: #38bdf8;">TechStart (Tecnologia):</strong> Banco de horas flexível, OKRs trimestrais e multicarteira flexível.
      </div>

      <div style="background: #0f172a; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
        <strong style="color: #fbbf24;">Metalúrgica Sul (Indústria):</strong> Trava de catraca por EPI/ASO, adicional de horas extras 50%/100% e transporte fretado.
      </div>

      <div style="background: #0f172a; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
        <strong style="color: #34d399;">FinCorp Seguros (Financeiro):</strong> Apólices corporativas, liquidação PIX instantânea BACEN e trilha SHA-256 LGPD.
      </div>
    </div>
  </div>

</div>
