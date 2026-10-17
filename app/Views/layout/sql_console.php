<?php
$logs = !empty($_SESSION['sql_logs']) ? $_SESSION['sql_logs'] : ($GLOBALS['SQL_LOGS'] ?? []);
$latestLog = !empty($logs) ? end($logs) : null;
?>
<!-- Docked Live SQL & GoF Patterns Console -->
<style>
  @keyframes livePulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1.15); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }
  .live-dot {
    width: 9px; height: 9px; border-radius: 50%; background: #10b981;
    display: inline-block; animation: livePulse 1.8s infinite;
  }
  .sql-console-dock {
    position: fixed; bottom: 0; left: 270px; right: 0; z-index: 99999;
    background: #080c14; border-top: 2px solid #3b82f6;
    font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 12px;
    box-shadow: 0 -8px 25px rgba(0, 0, 0, 0.7);
    transition: all 0.25s ease;
  }
  @media (max-width: 900px) {
    .sql-console-dock { left: 0; }
  }
  .sql-log-line {
    margin-bottom: 7px; padding-bottom: 5px; border-bottom: 1px solid rgba(255,255,255,0.04);
    display: flex; flex-direction: column; gap: 2px;
  }
  .sql-select { color: #38bdf8; font-weight: 600; }
  .sql-insert { color: #34d399; font-weight: 700; }
  .sql-update { color: #fbbf24; font-weight: 700; }
  .sql-delete { color: #f87171; font-weight: 700; }
  .sql-other  { color: #c084fc; font-weight: 600; }
</style>

<div id="sqlConsole" class="sql-console-dock">
  
  <!-- Dock Header Bar -->
  <div style="background: #0d1527; padding: 8px 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.07); cursor: pointer;" onclick="toggleConsoleBody(event)">
    
    <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
      <span class="live-dot"></span>
      <span style="background: #2563eb; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 10px; letter-spacing: 0.8px; white-space: nowrap;">
        CONSOLE AO VIVO
      </span>
      <span style="color: #94a3b8; font-size: 11px; font-weight: 600; white-space: nowrap;">
        Backend &amp; GoF Patterns:
      </span>
      <span id="consoleLatestPreview" style="color: #38bdf8; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 50vw;">
        <?= $latestLog ? '[' . htmlspecialchars($latestLog['time']) . '] ' . htmlspecialchars($latestLog['sql']) : 'Aguardando operações no banco SQLite...' ?>
      </span>
    </div>

    <!-- Actions & Toggle -->
    <div style="display: flex; align-items: center; gap: 8px; margin-left: 12px;" onclick="event.stopPropagation()">
      <span id="logCountBadge" style="background: rgba(255,255,255,0.07); color: #cbd5e1; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); white-space: nowrap;">
        <i class="fa-solid fa-database"></i> <span id="logCountNumber"><?= count($logs) ?></span> eventos
      </span>

      <button type="button" onclick="pingLiveConsole(event)" title="Simular e verificar integridade relacional SQLite" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
        <i class="fa-solid fa-bolt"></i> Testar Query
      </button>

      <button type="button" onclick="clearLiveConsole(event)" title="Limpar histórico do console" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
        <i class="fa-solid fa-trash-can"></i> Limpar
      </button>

      <button type="button" onclick="toggleConsoleBody(event)" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
        <span id="toggleText">Recolher</span>
        <i id="consoleToggleIcon" class="fa-solid fa-chevron-down"></i>
      </button>
    </div>

  </div>

  <!-- Terminal Output Drawer -->
  <div id="consoleBody" style="padding: 12px 18px; max-height: 200px; height: 180px; overflow-y: auto; display: block; background: #040711;">
    <div id="consoleLogList">
      <?php if (empty($logs)): ?>
        <div id="emptyLogNotice" style="color: #64748b; font-style: italic; padding: 10px 0;">
          <i class="fa-solid fa-circle-notch fa-spin"></i> Inicializando log de eventos ao vivo...
        </div>
      <?php else: ?>
        <?php foreach ($logs as $log): ?>
          <?php
            $sqlUpper = strtoupper(trim($log['sql']));
            $colorClass = 'sql-other';
            if (str_starts_with($sqlUpper, 'SELECT')) $colorClass = 'sql-select';
            elseif (str_starts_with($sqlUpper, 'INSERT')) $colorClass = 'sql-insert';
            elseif (str_starts_with($sqlUpper, 'UPDATE')) $colorClass = 'sql-update';
            elseif (str_starts_with($sqlUpper, 'DELETE')) $colorClass = 'sql-delete';
          ?>
          <div class="sql-log-line">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
              <span style="color: #64748b; font-size: 11px;">[<?= htmlspecialchars($log['time']) ?>]</span>
              <?php if (!empty($log['pattern'])): ?>
                <span style="background: rgba(168,85,247,0.2); color: #c084fc; border: 1px solid rgba(168,85,247,0.35); padding: 1px 7px; border-radius: 4px; font-weight: 700; font-size: 10px;">
                  <?= htmlspecialchars($log['pattern']) ?>
                </span>
              <?php endif; ?>
              <span class="<?= $colorClass ?>"><?= htmlspecialchars($log['sql']) ?></span>
            </div>
            <?php if (!empty($log['params'])): ?>
              <div style="color: #a7f3d0; margin-left: 20px; font-size: 11px;">
                ↳ Parâmetros: <?= htmlspecialchars(json_encode($log['params'], JSON_UNESCAPED_UNICODE)) ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($log['pathway'])): ?>
              <div style="margin-left: 20px; font-size: 11px; display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                <span style="color: #94a3b8; font-weight: 700;"><i class="fa-solid fa-route" style="color: #38bdf8;"></i> Trilha:</span>
                <span style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.35); padding: 1px 7px; border-radius: 4px; font-size: 10px; font-weight: 500;">
                  <?= htmlspecialchars($log['pathway']) ?>
                </span>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
function toggleConsoleBody(e) {
  if (e) e.stopPropagation();
  const body = document.getElementById('consoleBody');
  const icon = document.getElementById('consoleToggleIcon');
  const text = document.getElementById('toggleText');
  
  if (body.style.display === 'none') {
    body.style.display = 'block';
    icon.className = 'fa-solid fa-chevron-down';
    text.innerText = 'Recolher';
    scrollConsoleToBottom();
  } else {
    body.style.display = 'none';
    icon.className = 'fa-solid fa-chevron-up';
    text.innerText = 'Expandir';
  }
}

function scrollConsoleToBottom() {
  const body = document.getElementById('consoleBody');
  if (body) {
    body.scrollTop = body.scrollHeight;
  }
}

function clearLiveConsole(e) {
  if (e) e.stopPropagation();
  fetch('index.php?route=clear_console')
    .then(r => r.json())
    .then(() => {
      document.getElementById('consoleLogList').innerHTML = '<div style="color: #64748b; font-style: italic; padding: 8px 0;"><i class="fa-solid fa-broom"></i> Console limpo pelo usuário. Registros zerados nesta sessão.</div>';
      document.getElementById('logCountNumber').innerText = '0';
      document.getElementById('consoleLatestPreview').innerText = 'Console limpo. Aguardando novas ações no sistema...';
    })
    .catch(err => console.error('Erro ao limpar console:', err));
}

function pingLiveConsole(e) {
  if (e) e.stopPropagation();
  fetch('index.php?route=ping_console')
    .then(r => r.json())
    .then(data => {
      if (data.status === 'ok') {
        renderLiveLogs(data.logs);
      }
    })
    .catch(err => console.error('Erro ao testar query:', err));
}

function renderLiveLogs(logs) {
  const container = document.getElementById('consoleLogList');
  if (!container || !logs) return;

  let html = '';
  logs.forEach(log => {
    let sqlUpper = log.sql.trim().toUpperCase();
    let colorClass = 'sql-other';
    if (sqlUpper.startsWith('SELECT')) colorClass = 'sql-select';
    else if (sqlUpper.startsWith('INSERT')) colorClass = 'sql-insert';
    else if (sqlUpper.startsWith('UPDATE')) colorClass = 'sql-update';
    else if (sqlUpper.startsWith('DELETE')) colorClass = 'sql-delete';

    let patternBadge = log.pattern ? `<span style="background: rgba(168,85,247,0.2); color: #c084fc; border: 1px solid rgba(168,85,247,0.35); padding: 1px 7px; border-radius: 4px; font-weight: 700; font-size: 10px;">${escapeHtml(log.pattern)}</span>` : '';
    let paramsHtml = (log.params && Object.keys(log.params).length > 0) ? `<div style="color: #a7f3d0; margin-left: 20px; font-size: 11px;">↳ Parâmetros: ${escapeHtml(JSON.stringify(log.params))}</div>` : '';
    let pathwayHtml = log.pathway ? `<div style="margin-left: 20px; font-size: 11px; display: flex; align-items: center; gap: 6px; margin-top: 2px;"><span style="color: #94a3b8; font-weight: 700;"><i class="fa-solid fa-route" style="color: #38bdf8;"></i> Trilha:</span><span style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.35); padding: 1px 7px; border-radius: 4px; font-size: 10px; font-weight: 500;">${escapeHtml(log.pathway)}</span></div>` : '';

    html += `
      <div class="sql-log-line">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
          <span style="color: #64748b; font-size: 11px;">[${escapeHtml(log.time)}]</span>
          ${patternBadge}
          <span class="${colorClass}">${escapeHtml(log.sql)}</span>
        </div>
        ${paramsHtml}
        ${pathwayHtml}
      </div>
    `;
  });

  container.innerHTML = html;
  document.getElementById('logCountNumber').innerText = logs.length;
  
  if (logs.length > 0) {
    const last = logs[logs.length - 1];
    document.getElementById('consoleLatestPreview').innerText = `[${last.time}] ${last.sql}`;
  }
  
  scrollConsoleToBottom();
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// Auto-scroll on initial load
document.addEventListener('DOMContentLoaded', function() {
  scrollConsoleToBottom();
});
</script>
