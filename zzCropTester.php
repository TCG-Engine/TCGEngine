<?php
// ============================================================================
// zzCropTester.php — interactive crop tuner for generated card images.
//
//   Open:  http://localhost/TCGEngine/zzCropTester.php?app=AzukiSim
//
// Purpose: dial in the crop coordinates used by zzImageConverter.php (the
// concat/ square-art and crops/ tooltip-art pipelines) WITHOUT re-running the
// whole zzCardCodeGenerator. It reads an app's downloaded source webps,
// applies arbitrary crop sections live, overlays the crop
// rectangles on the source, compares against the currently-committed output,
// and prints the exact PHP snippet to paste back into zzImageConverter.php.
//
// Two roles in one file:
//   ?app=Root&render=1&card=ID&s=sx,sy,w,h&s=... -> streams the cropped image
//   (no params)                                -> the HTML control panel
// ============================================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

$ROOT = __DIR__;

// Build an allowlist from known schema roots that have downloaded source images.
$availableApps = [];
foreach (glob($ROOT . '/Schemas/*', GLOB_ONLYDIR) ?: [] as $schemaDirectory) {
    $candidate = basename($schemaDirectory);
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $candidate)) continue;
    if (!is_dir($ROOT . '/' . $candidate . '/WebpImages')) continue;
    $availableApps[] = $candidate;
}
sort($availableApps, SORT_NATURAL | SORT_FLAG_CASE);

$requestedApp = isset($_GET['app']) ? (string)$_GET['app'] : '';
$app = in_array($requestedApp, $availableApps, true)
    ? $requestedApp
    : (in_array('SWUSim', $availableApps, true) ? 'SWUSim' : ($availableApps[0] ?? ''));
if ($app === '') {
    http_response_code(404);
    echo 'No apps with generated source images were found.';
    exit;
}

$APP_ROOT   = $ROOT . '/' . $app;
$IMG_BASE   = $APP_ROOT . '/WebpImages/';
$APP_WEB    = rawurlencode($app) . '/';
$IMG_WEB    = $APP_WEB . 'WebpImages/';
$CONCAT_WEB = $APP_WEB . 'concat/';
$CROP_WEB   = $APP_WEB . 'crops/';

// ----------------------------------------------------------------------------
// Image endpoint: stack the requested sections vertically and stream the result.
// A "section" is sx,sy,w,h taken from the source; output is (max w) x (sum h).
// This single generalization reproduces every branch in zzImageConverter:
//   single-crop  = 1 section,  two-section = 2 sections,  art-crop = 1 section.
// ----------------------------------------------------------------------------
if (isset($_GET['render'])) {
    $card = isset($_GET['card']) && is_string($_GET['card']) ? $_GET['card'] : '';
    if ($card === '' || str_contains($card, '/') || str_contains($card, '\\') || str_contains($card, "\0")) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo 'Invalid card ID';
        exit;
    }
    $src  = $IMG_BASE . $card . '.webp';
    if ($card === '' || !is_file($src)) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo "No source image for '$card'";
        exit;
    }

    $sections = [];
    foreach (array_slice((array)($_GET['s'] ?? []), 0, 8) as $sstr) {
        if (!is_string($sstr)) continue;
        $p = array_map('intval', explode(',', $sstr));
        if (count($p) === 4 && $p[2] > 0 && $p[2] <= 4096 && $p[3] > 0 && $p[3] <= 4096) $sections[] = $p;
    }
    if (!$sections) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo "No valid sections";
        exit;
    }

    $fmt  = (($_GET['fmt'] ?? 'webp') === 'png') ? 'png' : 'webp';
    $outW = 0; $outH = 0;
    foreach ($sections as $p) { $outW = max($outW, $p[2]); $outH += $p[3]; }
    if ($outW > 4096 || $outH > 8192) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo 'Requested output is too large';
        exit;
    }

    // Imagick-only, matching the migrated zzImageConverter.php production pipeline
    // (XAMPP's GD lacks WebP; see newhost/harden-webp.sh).
    try { $imgsrc = new Imagick($src); }
    catch (Exception $e) { http_response_code(500); echo 'decode failed'; exit; }

    $out = new Imagick();
    $out->newImage($outW, $outH, new ImagickPixel('transparent'));

    $y = 0;
    foreach ($sections as $p) {
        list($sx, $sy, $w, $h) = $p;
        $piece = clone $imgsrc;
        $piece->cropImage($w, $h, $sx, $sy);
        $piece->setImagePage($w, $h, 0, 0);
        $out->compositeImage($piece, Imagick::COMPOSITE_COPY, 0, $y);
        $piece->clear(); $piece->destroy();
        $y += $h;
    }

    $resizeW = min(4096, max(0, (int)($_GET['ow'] ?? 0)));
    $resizeH = min(4096, max(0, (int)($_GET['oh'] ?? 0)));
    if ($resizeW > 0 && $resizeH > 0 && ($resizeW !== $outW || $resizeH !== $outH)) {
        $out->resizeImage($resizeW, $resizeH, Imagick::FILTER_LANCZOS, 1, false);
    }

    if ($fmt === 'png') { $out->setImageFormat('png'); header('Content-Type: image/png'); }
    else                { $out->setImageFormat('webp'); header('Content-Type: image/webp'); }
    echo $out->getImageBlob();
    $out->clear(); $out->destroy();
    $imgsrc->clear(); $imgsrc->destroy();
    exit;
}

// ----------------------------------------------------------------------------
// Control panel: gather real sample cards per type from the card dictionary.
// ----------------------------------------------------------------------------
@include_once $APP_ROOT . '/GeneratedCode/GeneratedCardDictionaries.php';

// Dictionary variable names differ between apps. Normalize common names, then
// fall back to image filenames when an app has no compatible dictionary.
$cardTypes = isset($typeData) && is_array($typeData) ? $typeData
    : (isset($categoryData) && is_array($categoryData) ? $categoryData : []);
$cardTitles = isset($titleData) && is_array($titleData) ? $titleData
    : (isset($nameData) && is_array($nameData) ? $nameData : []);

$samplesByType = [];               // type => [cardID, ...]
$titlesById    = [];               // cardID => display title
if ($cardTypes) {
    foreach ($cardTypes as $id => $t) {
        $t = (string)$t;
        if (count($samplesByType[$t] ?? []) >= 32) continue;
        if (is_file($IMG_BASE . $id . '.webp')) {
            $samplesByType[$t][] = $id;
            $titlesById[$id] = isset($cardTitles[$id]) ? (string)$cardTitles[$id] : $id;
        }
    }
    // Synthesize LeaderUnit (_back) samples from Leader cards.
    foreach (($samplesByType['Leader'] ?? []) as $id) {
        if (count($samplesByType['LeaderUnit'] ?? []) >= 16) break;
        if (is_file($IMG_BASE . $id . '_back.webp')) {
            $samplesByType['LeaderUnit'][] = $id . '_back';
            $titlesById[$id . '_back'] = (isset($cardTitles[$id]) ? $cardTitles[$id] : $id) . ' (unit side)';
        }
    }
}

$allSamples = [];
foreach (glob($IMG_BASE . '*.webp') ?: [] as $imagePath) {
    if (count($allSamples) >= 500) break;
    $id = pathinfo($imagePath, PATHINFO_FILENAME);
    $allSamples[$id] = isset($cardTitles[$id]) ? (string)$cardTitles[$id] : $id;
}

// Scenarios mirror the actual branches in zzImageConverter.php. Each carries
// its production-default sections so the panel opens reproducing production.
$swuScenarios = [
    'concat_unit' => [
        'label'    => 'concat · Unit / LeaderUnit (single crop)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 450,
        'types'    => ['Unit', 'LeaderUnit'],
        'sections' => [[14, 14, 420, 420]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatSingleCrop',
    ],
    'concat_event' => [
        'label'    => 'concat · Event (two-section)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 450,
        'types'    => ['Event'],
        'sections' => [[0, 20, 450, 140], [0, 310, 450, 260]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatTwoSection',
    ],
    'concat_upgrade' => [
        'label'    => 'concat · Upgrade / Token (two-section)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 450,
        'types'    => ['Upgrade', 'Token Upgrade', 'Token Unit'],
        'sections' => [[0, 14, 450, 342], [0, 516, 450, 80]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatTwoSection',
    ],
    'concat_landscape' => [
        'label'    => 'concat · Leader / Base (landscape)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 628,
        'types'    => ['Leader', 'Base'],
        'sections' => [[104, 15, 420, 420]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatSingleCrop',
    ],
    'crop_event' => [
        'label'    => 'crops · Event (art thumbnail)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 450,
        'types'    => ['Event'],
        'sections' => [[50, 326, 350, 246]],
        'snippet'  => 'cropImage',
    ],
    'crop_default' => [
        'label'    => 'crops · default (art thumbnail)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 450,
        'types'    => ['Unit', 'Upgrade'],
        'sections' => [[50, 100, 350, 270]],
        'snippet'  => 'cropImage',
    ],
    'crop_leader' => [
        'label'    => 'crops · Leader (portrait)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 628,
        'types'    => ['Leader'],
        'sections' => [[10, 60, 200, 350]],
        'snippet'  => 'cropImage',
    ],
    'crop_base' => [
        'label'    => 'crops · Base (identity banner)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 628,
        'types'    => ['Base'],
        'sections' => [[34, 125, 560, 175]],
        'snippet'  => 'cropImage',
    ],
];

$genericScenarios = [
    'concat_default' => [
        'label'    => 'concat · default (two-section)',
        'pipeline' => 'concat', 'fmt' => 'webp', 'srcW' => 450,
        'types'    => [],
        'sections' => [[0, 15, 450, 400], [0, 595, 450, 10]], 'outW' => 450, 'outH' => 450,
        'snippet'  => '_concatTwoSection',
    ],
    'crop_default' => [
        'label'    => 'crops · default (art thumbnail)',
        'pipeline' => 'crop', 'fmt' => 'png', 'srcW' => 450,
        'types'    => [],
        'sections' => [[50, 100, 350, 270]],
        'snippet'  => 'cropImage',
    ],
];
$scenarios = ($app === 'SWUSim' || $app === 'SWUDeck') ? $swuScenarios : $genericScenarios;

// Build a JS-friendly bundle: per scenario, its defaults + an actual sample pool.
$jsScenarios = [];
foreach ($scenarios as $key => $s) {
    $pool = [];
    foreach ($s['types'] as $t) {
        foreach (($samplesByType[$t] ?? []) as $id) {
            $pool[$id] = ($titlesById[$id] ?? $id);
        }
    }
    if (!$pool) $pool = $allSamples;
    $s['samples'] = $pool;
    $jsScenarios[$key] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($app, ENT_QUOTES, 'UTF-8') ?> Crop Tester</title>
<style>
  :root { --bg:#11151c; --panel:#1b2430; --line:#2d3a4a; --txt:#dfe7ef; --muted:#8aa0b5; --accent:#4fa3ff; }
  * { box-sizing: border-box; }
  body { margin:0; background:var(--bg); color:var(--txt); font:14px/1.5 system-ui,sans-serif; }
  header { padding:14px 20px; border-bottom:1px solid var(--line); background:var(--panel); }
  header h1 { margin:0; font-size:17px; }
  header p { margin:4px 0 0; color:var(--muted); font-size:12px; }
  .wrap { display:grid; grid-template-columns:320px 1fr; gap:0; min-height:calc(100vh - 60px); }
  .controls { padding:18px 20px; border-right:1px solid var(--line); background:var(--panel); }
  .stage { padding:18px 24px; overflow:auto; }
  label.fld { display:block; margin:0 0 4px; color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.4px; }
  select, input[type=text] { width:100%; padding:7px 9px; margin-bottom:14px; background:#0e1218; color:var(--txt);
    border:1px solid var(--line); border-radius:6px; font:13px monospace; }
  .sections { margin-bottom:12px; }
  .sec-row { display:grid; grid-template-columns:18px repeat(4,1fr) 26px; gap:5px; align-items:center; margin-bottom:6px; }
  .sec-row input { width:100%; padding:5px 4px; background:#0e1218; color:var(--txt); border:1px solid var(--line);
    border-radius:5px; font:12px monospace; text-align:center; margin:0; }
  .sec-row .tag { color:var(--muted); font:11px monospace; text-align:center; }
  .sec-head { display:grid; grid-template-columns:18px repeat(4,1fr) 26px; gap:5px; color:var(--muted);
    font:10px/1.2 monospace; text-transform:uppercase; margin-bottom:4px; }
  .sec-head span { text-align:center; }
  button { cursor:pointer; border:1px solid var(--line); background:#22303f; color:var(--txt);
    padding:6px 10px; border-radius:6px; font-size:12px; }
  button:hover { border-color:var(--accent); }
  button.x { background:#3a2230; padding:2px 6px; }
  .btnrow { display:flex; gap:8px; margin:6px 0 16px; }
  .out { color:var(--muted); font:12px monospace; }
  .panels { display:flex; gap:26px; flex-wrap:wrap; align-items:flex-start; }
  .card-col h3 { margin:0 0 8px; font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.4px; }
  .src-box { position:relative; display:inline-block; line-height:0; border:1px solid var(--line); border-radius:4px; overflow:hidden; }
  .src-box img { display:block; }
  .rect { position:absolute; border:2px solid var(--accent); background:rgba(79,163,255,.14);
    box-shadow:0 0 0 1px #000 inset; pointer-events:none; }
  .rect .num { position:absolute; top:-1px; left:-1px; background:var(--accent); color:#001; font:bold 11px monospace; padding:0 4px; }
  .preview img { display:block; border:1px solid var(--line); border-radius:4px; background:
    repeating-conic-gradient(#222 0% 25%, #2b2b2b 0% 50%) 50%/16px 16px; }
  .dims { color:var(--muted); font:11px monospace; margin-top:6px; }
  pre.snip { background:#0b0f14; border:1px solid var(--line); border-radius:6px; padding:12px;
    color:#a9d6a0; font:12px/1.5 monospace; white-space:pre-wrap; margin-top:20px; max-width:760px; }
  a { color:var(--accent); }
</style>
</head>
<body>
<header>
  <h1><?= htmlspecialchars($app, ENT_QUOTES, 'UTF-8') ?> Crop Tester</h1>
  <p>Reads local <code><?= htmlspecialchars($app, ENT_QUOTES, 'UTF-8') ?>/WebpImages/</code>. Tune the crop sections, compare against the committed output, copy the snippet back into <code>zzImageConverter.php</code>, then regenerate with <code>overwriteImages=1</code>.</p>
</header>
<div class="wrap">
  <div class="controls">
    <label class="fld">Application</label>
    <select id="app">
      <?php foreach ($availableApps as $availableApp): ?>
        <option value="<?= htmlspecialchars($availableApp, ENT_QUOTES, 'UTF-8') ?>"<?= $availableApp === $app ? ' selected' : '' ?>><?= htmlspecialchars($availableApp, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>

    <label class="fld">Scenario (zzImageConverter branch)</label>
    <select id="scenario"></select>

    <label class="fld">Sample card</label>
    <select id="card"></select>

    <label class="fld">Or any cardID</label>
    <input type="text" id="cardManual" placeholder="e.g. JTL_012 or SOR_005_back">

    <label class="fld">Crop sections — sx, sy, w, h (stacked top→bottom)</label>
    <div class="sec-head"><span>#</span><span>sx</span><span>sy</span><span>w</span><span>h</span><span></span></div>
    <div class="sections" id="sections"></div>
    <div class="btnrow">
      <button id="addSec">+ section</button>
      <button id="resetSec">reset to default</button>
    </div>
    <div class="out" id="outdims"></div>
  </div>

  <div class="stage">
    <div class="panels">
      <div class="card-col">
        <h3>Source + crop overlay</h3>
        <div class="src-box" id="srcBox"><img id="srcImg" alt="source"></div>
        <div class="dims" id="srcDims"></div>
      </div>
      <div class="card-col preview">
        <h3>New output (live)</h3>
        <img id="prevImg" alt="preview">
        <div class="dims" id="prevDims"></div>
      </div>
      <div class="card-col preview">
        <h3>Committed (current)</h3>
        <img id="curImg" alt="current">
        <div class="dims">on disk</div>
      </div>
    </div>
    <pre class="snip" id="snippet"></pre>
  </div>
</div>

<script>
const SCEN = <?php echo json_encode($jsScenarios, JSON_UNESCAPED_SLASHES); ?>;
const CONCAT_WEB = <?php echo json_encode($CONCAT_WEB); ?>;
const CROP_WEB   = <?php echo json_encode($CROP_WEB); ?>;
const ACTIVE_APP = <?php echo json_encode($app); ?>;

const $ = id => document.getElementById(id);
let curSections = [];

// Populate scenario dropdown.
for (const [k, s] of Object.entries(SCEN)) {
  const o = document.createElement('option'); o.value = k; o.textContent = s.label;
  $('scenario').appendChild(o);
}

function loadScenario() {
  const s = SCEN[$('scenario').value];
  // cards
  $('card').innerHTML = '';
  const ids = Object.keys(s.samples);
  if (!ids.length) {
    const o = document.createElement('option'); o.textContent = '(no local samples)'; o.value = '';
    $('card').appendChild(o);
  }
  for (const id of ids) {
    const o = document.createElement('option'); o.value = id; o.textContent = id + ' — ' + s.samples[id];
    $('card').appendChild(o);
  }
  resetSections();
}

function resetSections() {
  const s = SCEN[$('scenario').value];
  curSections = s.sections.map(r => r.slice());
  renderSectionInputs();
  refresh();
}

function renderSectionInputs() {
  const box = $('sections'); box.innerHTML = '';
  curSections.forEach((r, i) => {
    const row = document.createElement('div'); row.className = 'sec-row';
    const tag = document.createElement('div'); tag.className = 'tag'; tag.textContent = i + 1; row.appendChild(tag);
    r.forEach((v, j) => {
      const inp = document.createElement('input'); inp.type = 'number'; inp.value = v;
      inp.oninput = () => { curSections[i][j] = parseInt(inp.value || '0', 10); refresh(); };
      row.appendChild(inp);
    });
    const del = document.createElement('button'); del.className = 'x'; del.textContent = '×';
    del.onclick = () => { curSections.splice(i, 1); renderSectionInputs(); refresh(); };
    row.appendChild(del);
    box.appendChild(row);
  });
}

function activeCard() {
  return $('cardManual').value.trim() || $('card').value;
}

function sectionParams() {
  // NB: must be s[] — PHP keeps only the last value of a repeated bare key.
  return curSections.map(r => 's%5B%5D=' + r.join(',')).join('&');
}

function refresh() {
  const s = SCEN[$('scenario').value];
  const card = activeCard();
  if (!card) return;

  // Source image.
  $('srcImg').src = '<?php echo $IMG_WEB; ?>' + encodeURIComponent(card) + '.webp';

  // Live preview.
  const fmt = s.fmt;
  let resize = '';
  if (s.outW && s.outH) resize = '&ow=' + s.outW + '&oh=' + s.outH;
  $('prevImg').src = '?app=' + encodeURIComponent(ACTIVE_APP) + '&render=1&card=' + encodeURIComponent(card) + '&fmt=' + fmt + resize + '&' + sectionParams() + '&_=' + Date.now();

  // Committed file for comparison.
  if (s.pipeline === 'concat') $('curImg').src = CONCAT_WEB + encodeURIComponent(card) + '.webp?_=' + Date.now();
  else                         $('curImg').src = CROP_WEB + encodeURIComponent(card) + '_cropped.png?_=' + Date.now();

  // Output dims.
  let outW = 0, outH = 0;
  curSections.forEach(r => { outW = Math.max(outW, r[2]); outH += r[3]; });
  if (s.outW && s.outH) { outW = s.outW; outH = s.outH; }
  $('outdims').textContent = 'output ' + outW + '×' + outH + (curSections.length > 1 ? ' (' + curSections.length + ' stacked)' : '');
  $('prevDims').textContent = outW + '×' + outH;

  drawOverlay();
  writeSnippet(s, card);
}

function drawOverlay() {
  const img = $('srcImg'), box = $('srcBox');
  // remove old rects
  box.querySelectorAll('.rect').forEach(e => e.remove());
  if (!img.naturalWidth) return;
  const scale = img.clientWidth / img.naturalWidth;   // uniform (aspect preserved)
  $('srcDims').textContent = img.naturalWidth + '×' + img.naturalHeight + ' source  ·  shown @ ' + (scale * 100).toFixed(0) + '%';
  curSections.forEach((r, i) => {
    const d = document.createElement('div'); d.className = 'rect';
    d.style.left = (r[0] * scale) + 'px';
    d.style.top = (r[1] * scale) + 'px';
    d.style.width = (r[2] * scale) + 'px';
    d.style.height = (r[3] * scale) + 'px';
    const n = document.createElement('span'); n.className = 'num'; n.textContent = i + 1; d.appendChild(n);
    box.appendChild(d);
  });
}

function writeSnippet(s, card) {
  let code;
  if (s.snippet === '_concatSingleCrop') {
    const r = curSections[0];
    const output = s.outW && s.outH ? `, ${s.outW}, ${s.outH}` : '';
    code = `// concat single-crop\n_concatSingleCrop($filename, $concatFilename, $cardID, ${r[0]}, ${r[1]}, ${r[2]}, ${r[3]}${output});`;
  } else if (s.snippet === '_concatTwoSection') {
    const a = curSections[0], b = curSections[1] || [0,0,0,0];
    const output = s.outW && s.outH ? `, outW:${s.outW}, outH:${s.outH}` : '';
    code = `// concat two-section\n_concatTwoSection($filename, $concatFilename, $cardID,\n    topSrcY:${a[1]}, topH:${a[3]}, botSrcY:${b[1]}, botH:${b[3]}${output});`;
    if (curSections.length !== 2)
      code = `// NOTE: _concatTwoSection expects exactly 2 sections; you have ${curSections.length}.\n` + code;
  } else { // art crop
    const r = curSections[0];
    code = `// crops/ art thumbnail (Imagick)\n$image->cropImage(${r[2]}, ${r[3]}, ${r[0]}, ${r[1]});`;
  }
  $('snippet').textContent = code;
}

$('scenario').onchange = loadScenario;
$('app').onchange = () => {
  const url = new URL(window.location.href);
  url.search = '';
  url.searchParams.set('app', $('app').value);
  window.location.href = url;
};
$('card').onchange = () => { $('cardManual').value = ''; refresh(); };
$('cardManual').oninput = refresh;
$('addSec').onclick = () => { curSections.push([0, 0, SCEN[$('scenario').value].srcW || 450, 50]); renderSectionInputs(); refresh(); };
$('resetSec').onclick = resetSections;
$('srcImg').onload = drawOverlay;
window.addEventListener('resize', drawOverlay);

loadScenario();
</script>
</body>
</html>
