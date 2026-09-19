<?php
$logs = $GLOBALS['SQL_LOGS'] ?? [];
?>
<!-- Live SQL & GoF Pattern Execution Console -->
<div id="sqlConsole" style="background: #090d16; border-top: 2px solid #3b82f6; margin-top: 30px; border-radius: 12px 12px 0 0; font-family: 'JetBrains Mono', monospace; font-size: 12px; box-shadow: 0 -4px 20px rgba(0,0,0,0.5);">
  
  <div style="background: #0f172a; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #1e293b; border-radius: 12px 12px 0 0;">
    <div style="display: flex; align-items: center; gap: 10px;">
      <span style="background: #3b82f6; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 10px;">CONSOLE AO VIVO</span>
      <span style="color: #94a3b8; font-weight: 600;">📟 Inspect do Backend em Tempo Real (SQL & GoF Patterns Executados)</span>
    </div>

    <button onclick="toggleConsoleBody()" style="background: transparent; border: none; color: #94a3b8; cursor: pointer; font-size: 14px;">
      <i id="consoleToggleIcon" class="fa-solid fa-chevron-up"></i>
    </button>
  </div>

  <div id="consoleBody" style="padding: 16px; max-height: 200px; overflow-y: auto; display: block;">
    <?php if (empty($logs)): ?>
      <div style="color: #64748b; font-style: italic;">Nenhuma query SQL direta executada nesta rota até o momento.</div>
    <?php else: ?>
      <?php foreach ($logs as $log): ?>
        <div style="margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 6px;">
          <span style="color: #64748b;">[<?= $log['time'] ?>]</span>
          <?php if (!empty($log['pattern'])): ?>
            <span style="background: rgba(168,85,247,0.2); color: #c084fc; border: 1px solid rgba(168,85,247,0.4); padding: 1px 6px; border-radius: 4px; font-weight: 700; font-size: 10px; margin-right: 6px;">
              <?= htmlspecialchars($log['pattern']) ?>
            </span>
          <?php endif; ?>
          <span style="color: #38bdf8; font-weight: 600;"><?= htmlspecialchars($log['sql']) ?></span>
          <?php if (!empty($log['params'])): ?>
            <div style="color: #a7f3d0; margin-left: 60px; font-size: 11px;">Params: <?= json_encode($log['params'], JSON_UNESCAPED_UNICODE) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<script>
function toggleConsoleBody() {
  const body = document.getElementById('consoleBody');
  const icon = document.getElementById('consoleToggleIcon');
  if (body.style.display === 'none') {
    body.style.display = 'block';
    icon.className = 'fa-solid fa-chevron-up';
  } else {
    body.style.display = 'none';
    icon.className = 'fa-solid fa-chevron-down';
  }
}
</script>
