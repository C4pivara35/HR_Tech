<div style="margin-bottom: 24px;">
  <h1 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px;">Central de Exportação de Relatórios Gerenciais (Template Method)</h1>
  <p style="color: var(--text-muted); font-size: 14px;">Geração dinâmica de relatórios em múltiplos formatos (PDF, Excel/CSV, JSON) isolados por tenant.</p>
</div>

<div class="grid-4" style="grid-template-columns: repeat(3, 1fr);">
  
  <div class="card" style="text-align: center; padding: 32px 24px;">
    <div style="background: rgba(239,68,68,0.15); color: #f87171; width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px;">
      <i class="fa-solid fa-file-pdf"></i>
    </div>
    <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 8px;">Relatório Executivo PDF</h3>
    <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">Exportação de síntese gerencial com formatação padrão ABNT / corporativa.</p>
    <a href="index.php?route=reports&action=export&format=pdf" class="btn btn-danger" style="width: 100%; justify-content: center;">
      <i class="fa-solid fa-download"></i> Exportar PDF
    </a>
  </div>

  <div class="card" style="text-align: center; padding: 32px 24px;">
    <div style="background: rgba(16,185,129,0.15); color: #34d399; width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px;">
      <i class="fa-solid fa-file-excel"></i>
    </div>
    <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 8px;">Planilha Consolidada CSV</h3>
    <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">Tabelas brutas de colaboradores e frequências para auditoria em Excel.</p>
    <a href="index.php?route=reports&action=export&format=csv" class="btn btn-success" style="width: 100%; justify-content: center;">
      <i class="fa-solid fa-download"></i> Exportar CSV/Excel
    </a>
  </div>

  <div class="card" style="text-align: center; padding: 32px 24px;">
    <div style="background: rgba(56,189,248,0.15); color: #38bdf8; width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px;">
      <i class="fa-solid fa-code"></i>
    </div>
    <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 8px;">Estrutura de Dados JSON</h3>
    <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">Exportação de payload estruturado para integração com BI e sistemas externos.</p>
    <a href="index.php?route=reports&action=export&format=json" class="btn btn-outline" style="width: 100%; justify-content: center; color: #38bdf8; border-color: #38bdf8;">
      <i class="fa-solid fa-download"></i> Exportar JSON
    </a>
  </div>

</div>
