<!-- GoF Patterns & Academic Metrics Inspector Modal -->
<div id="patternModal" class="modal-backdrop">
  <div class="modal-box" style="max-width: 800px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="font-size: 20px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-microscope" style="color: #818cf8;"></i> Inspeção de Arquitetura & Padrões GoF
      </h3>
      <button onclick="togglePatternModal()" style="background: transparent; border: none; color: var(--text-muted); font-size: 20px; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
      
      <!-- GoF Patterns Card -->
      <div style="background: #0f172a; border: 1px solid var(--border-color); border-radius: 12px; padding: 16px;">
        <h4 style="font-size: 14px; font-weight: 700; color: #a7f3d0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-cubes"></i> 8 Padrões GoF em Uso
        </h4>
        <ul style="list-style: none; font-size: 13px; color: var(--text-muted); display: flex; flex-direction: column; gap: 8px;">
          <li><strong style="color: #fff;">1. Singleton:</strong> <code style="color: #38bdf8;">TenantContextManager</code>, <code style="color: #38bdf8;">DatabaseManager</code> & <code style="color: #38bdf8;">AuditLogger</code></li>
          <li><strong style="color: #fff;">2. Template Method:</strong> <code style="color: #fbbf24;">PayrollCalculatorTemplate</code> (CLT, PJ, Estágio), Importador de Ponto e Relatórios</li>
          <li><strong style="color: #fff;">3. Strategy:</strong> Horas Extras (50%, 100%, Banco de Horas), Dedução de Benefícios (VT 6%, Saúde) e Avaliação de Desempenho (OKRs, 360°, KPIs)</li>
        </ul>
      </div>

      <!-- SQLite Tables Card -->
      <div style="background: #0f172a; border: 1px solid var(--border-color); border-radius: 12px; padding: 16px;">
        <h4 style="font-size: 14px; font-weight: 700; color: #fde047; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-database"></i> SQLite (12 Tabelas Normalizadas)
        </h4>
        <div style="font-size: 12px; color: var(--text-muted); display: flex; flex-wrap: wrap; gap: 6px;">
          <span class="badge badge-info">tenants</span>
          <span class="badge badge-info">users</span>
          <span class="badge badge-info">departments</span>
          <span class="badge badge-info">roles</span>
          <span class="badge badge-info">employees</span>
          <span class="badge badge-info">time_logs</span>
          <span class="badge badge-info">time_adjustment_requests</span>
          <span class="badge badge-info">vacation_requests</span>
          <span class="badge badge-info">benefits</span>
          <span class="badge badge-info">equipment_aso</span>
          <span class="badge badge-info">insurance_policies</span>
          <span class="badge badge-info">audit_logs</span>
        </div>
      </div>

    </div>

    <!-- Academic Metrics Card -->
    <div style="background: #0f172a; border: 1px solid var(--border-color); border-radius: 12px; padding: 16px;">
      <h4 style="font-size: 14px; font-weight: 700; color: #c084fc; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-chart-pie"></i> Métricas da Disciplina (Medição e Análise)
      </h4>
      <div style="display: flex; gap: 20px; font-size: 13px; color: var(--text-muted);">
        <div>📊 <strong>UCP:</strong> UUCP = 178 | AUCP = 148,501 (2.970 hrs estimadas)</div>
        <div>🧮 <strong>APF:</strong> 132 Pontos de Função Não Ajustados (20 Arquivos de Dados)</div>
      </div>
    </div>

    <div style="margin-top: 20px; text-align: right;">
      <button onclick="togglePatternModal()" class="btn">Entendido</button>
    </div>

  </div>
</div>

<script>
function togglePatternModal() {
  const modal = document.getElementById('patternModal');
  modal.classList.toggle('active');
}
</script>
