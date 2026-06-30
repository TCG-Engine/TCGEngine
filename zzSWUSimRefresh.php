<?php
include_once './AccountFiles/AccountSessionAPI.php';

$error = CheckLoggedInUserMod();
if ($error !== '') {
    echo htmlspecialchars($error);
    exit;
}

$withPreview = isset($_GET['withPreview']) ? '&withPreview=' . intval($_GET['withPreview']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SWUSim Refresh</title>
  <style>
    body { background:#1e1e1e; color:#d4d4d4; font-family:monospace; font-size:13px; padding:24px; margin:0; }
    h2   { color:#9cdcfe; margin:0 0 20px; }
    .step { display:flex; align-items:flex-start; gap:10px; margin-bottom:12px; }
    .icon { width:18px; flex-shrink:0; }
    .label { font-weight:bold; }
    .detail { margin-top:6px; }
    details summary { cursor:pointer; color:#888; font-size:12px; }
    details pre { margin:6px 0 0; padding:10px; background:#111; border-radius:4px; max-height:200px; overflow:auto; color:#aaa; font-size:11px; }
    #results { margin-top:24px; }
    .done  { color:#4ec94e; }
    .fail  { color:#e74c3c; }
    .wait  { color:#888; }
    .spin  { color:#9cdcfe; }
  </style>
</head>
<body>
<h2>SWUSim Refresh</h2>

<div id="steps">
  <div class="step" id="step-card">
    <span class="icon wait" id="icon-card">⬜</span>
    <div>
      <div class="label">Card Dictionary Generator</div>
      <div class="detail" id="detail-card"></div>
    </div>
  </div>
  <div class="step" id="step-game">
    <span class="icon wait" id="icon-game">⬜</span>
    <div>
      <div class="label">Game Code Generator</div>
      <div class="detail" id="detail-game"></div>
    </div>
  </div>
  <div class="step" id="step-kw">
    <span class="icon wait" id="icon-kw">⬜</span>
    <div>
      <div class="label">Keyword Processor</div>
      <div class="detail" id="detail-kw"></div>
    </div>
  </div>
  <div class="step" id="step-tests">
    <span class="icon wait" id="icon-tests">⬜</span>
    <div>
      <div class="label">Regression Tests</div>
      <div class="detail" id="detail-tests"></div>
    </div>
  </div>
</div>

<div id="results"></div>

<script>
async function runStep(id, url, showOutput) {
  const icon   = document.getElementById('icon-' + id);
  const detail = document.getElementById('detail-' + id);

  icon.textContent = '⏳';
  icon.className   = 'icon spin';

  try {
    const res  = await fetch(url, { credentials: 'same-origin' });
    const text = await res.text();

    icon.textContent = '✅';
    icon.className   = 'icon done';

    if (showOutput) {
      document.getElementById('results').innerHTML = text;
    } else if (text.trim()) {
      detail.innerHTML = `<details><summary>output</summary><pre>${escHtml(text)}</pre></details>`;
    }

    return text;
  } catch (err) {
    icon.textContent = '❌';
    icon.className   = 'icon fail';
    detail.textContent = err.message;
    throw err;
  }
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

(async () => {
  try {
    await runStep('card',  'zzCardCodeGenerator.php?rootName=SWUSim<?= $withPreview ?>', false);
    await runStep('game',  'zzGameCodeGenerator.php?rootName=SWUSim',               false);
    await runStep('kw',    'Data/ProcessKeywordsSWU.php',                            false);
    await runStep('tests', 'zzRegressionSWUSim.php',                                 true);
  } catch (err) {
    document.getElementById('results').textContent = 'Pipeline stopped: ' + err.message;
  }
})();
</script>
</body>
</html>
