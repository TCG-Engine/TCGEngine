<?php
require_once __DIR__ . '/SharedUI/Render/AssetVersion.php';   // _VersionAsset() — ?v=<filemtime> cache busting
// Mock (preview) card builder — LOCAL DEV ONLY.
//
// CardMocks.php is tracked source, so this page only makes sense where its output can be reviewed
// as a diff and committed. Import a preview card, review/correct every field, preview the art,
// then write the entry. Reprints get an Overrides.php mapping instead of a mock card.
//
//   http://localhost:3400/TCGEngine/zzPreviewTool.php?rootName=SWUSim
//
// See docs/superpowers/specs/2026-07-29-swusim-mock-card-builder-design.md
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

require_once __DIR__ . '/SWUSim/Mod/DevGate.php';
require_once __DIR__ . '/SWUSim/DevTools/PreviewImport.php';
require_once __DIR__ . '/AppCore/SWU/MockCardWriter.php';
require_once __DIR__ . '/SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/AppCore/SWU/Overrides.php';

function preview_tool_404(): void {
    http_response_code(404);
    echo "Not found";
    exit;
}

$rootName = $_GET['rootName'] ?? $_POST['rootName'] ?? '';
if ($rootName !== 'SWUSim') preview_tool_404();
if (!function_exists('SWUIsLocalDevRequest') || !SWUIsLocalDevRequest()) preview_tool_404();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Normalize the editable form payload back into a mock entry: comma lists become arrays, blank
// numerics drop out entirely (so the dictionaries hold null rather than 0), checkbox becomes bool.
function preview_tool_clean_mock(array $mock): array {
    foreach (['aspect', 'trait', 'leaderUnitTrait'] as $listField) {
        if (isset($mock[$listField]) && is_string($mock[$listField])) {
            $mock[$listField] = array_values(array_filter(array_map('trim', explode(',', $mock[$listField]))));
        }
    }
    foreach (['cost', 'power', 'hp', 'upgradePower', 'upgradeHp'] as $intField) {
        if (!isset($mock[$intField]) || $mock[$intField] === '' || $mock[$intField] === null) {
            unset($mock[$intField]);
            continue;
        }
        $mock[$intField] = intval($mock[$intField]);
    }
    $mock['unique'] = !empty($mock['unique']) && $mock['unique'] !== 'false';
    return $mock;
}

// Path to a PHP **CLI** binary. PHP_BINARY is EMPTY under apache2handler (and points at php-fpm
// under FPM), so using it directly makes exec() fail with 127 — resolve a real CLI binary instead.
function preview_tool_php_cli(): string {
    if (PHP_BINARY !== '' && is_executable(PHP_BINARY) && php_sapi_name() === 'cli') return PHP_BINARY;
    $out = []; $code = 0;
    exec('command -v php 2>/dev/null', $out, $code);
    if ($code === 0 && !empty($out[0]) && is_executable(trim($out[0]))) return trim($out[0]);
    foreach (['/usr/local/bin/php', '/usr/bin/php'] as $candidate) {
        if (is_executable($candidate)) return $candidate;
    }
    return '';
}

// Delete a mock's art. The mock_ prefix is what makes this safe: it can never match official art.
function preview_tool_delete_art(string $cardID): array {
    $removed = [];
    $targets = [['WebpImages', 'webp'], ['concat', 'webp'], ['crops', 'png']];
    foreach ($targets as [$dir, $ext]) {
        foreach ([$cardID, $cardID . '_back'] as $stem) {
            $suffix = ($dir === 'crops') ? '_cropped' : '';
            $f = __DIR__ . '/SWUSim/' . $dir . '/mock_' . $stem . $suffix . '.' . $ext;
            if (file_exists($f) && @unlink($f)) $removed[] = basename($f);
        }
    }
    return $removed;
}

// ── JSON endpoints ────────────────────────────────────────────────────────────
if ($action !== '') {
    header('Content-Type: application/json');

    if ($action === 'import') {
        $parsed = SWUPreviewParseLink((string)($_POST['link'] ?? ''));
        if ($parsed === null) {
            echo json_encode(['ok' => false, 'error' => 'Could not read a SET/number from that link.']);
            exit;
        }
        $rec = SWUPreviewFetchCard($parsed['set'], $parsed['number']);
        if ($rec === null || ($rec['cardName'] ?? '') === '') {
            echo json_encode(['ok' => false, 'error' => 'No data for ' . $parsed['set'] . '/' . $parsed['number']
                . ' — it may not be previewed yet.']);
            exit;
        }
        $cardID = $parsed['set'] . '_' . $parsed['number'];
        $class  = SWUPreviewClassify($rec);
        echo json_encode([
            'ok' => true,
            'cardID' => $cardID,
            'kind' => $class['kind'],
            'canonical' => $class['canonical'],
            'canonicalKnown' => $class['canonical'] !== null && IsSWUCardID($class['canonical']),
            'name' => (string)($rec['cardName'] ?? ''),
            'mock' => SWUPreviewToMock($rec),
            'alreadyMocked' => isset(SWULoadMockCards()[$cardID]),
            'alreadyOverridden' => CardIDOverride($cardID) !== $cardID,
            'alreadyOfficial' => IsSWUCardID($cardID) && !isset(SWULoadMockCards()[$cardID]),
        ]);
        exit;
    }

    if ($action === 'create') {
        $cardID = (string)($_POST['cardID'] ?? '');
        $mock = json_decode((string)($_POST['mock'] ?? ''), true);
        if (!is_array($mock)) { echo json_encode(['ok' => false, 'error' => 'Malformed card data.']); exit; }
        $ok = SWUWriteMockCard($cardID, preview_tool_clean_mock($mock));
        echo json_encode($ok
            ? ['ok' => true, 'message' => $cardID . ' written to CardMocks.php. Regenerate to make it playable.']
            : ['ok' => false, 'error' => 'Write failed — check that ' . $cardID
                . ' is a valid SET_NNN id and CardMocks.php is writable.']);
        exit;
    }

    if ($action === 'override') {
        $cardID    = (string)($_POST['cardID'] ?? '');
        $canonical = (string)($_POST['canonical'] ?? '');
        $name      = (string)($_POST['name'] ?? '');
        if ($cardID === '' || $canonical === '') {
            echo json_encode(['ok' => false, 'error' => 'Missing card id or canonical id.']);
            exit;
        }
        // Never map onto a printing the dictionaries don't know — that would silently produce a
        // deck-import target with no card behind it.
        if (!IsSWUCardID($canonical)) {
            echo json_encode(['ok' => false, 'error' => $canonical
                . ' is not a known CardID — regenerate the dictionaries first.']);
            exit;
        }
        $ok = SWUWriteReprintOverride($cardID, $canonical, $name);
        echo json_encode($ok
            ? ['ok' => true, 'message' => $cardID . ' -> ' . $canonical . ' added to Overrides.php.']
            : ['ok' => false, 'error' => 'Not written — the mapping already exists, or Overrides.php '
                . 'has an unexpected shape.']);
        exit;
    }

    if ($action === 'list') {
        $rows = [];
        foreach (SWULoadMockCards() as $cardID => $m) {
            $rows[] = [
                'cardID' => $cardID,
                'title'  => (string)($m['title'] ?? ''),
                'type'   => (string)($m['type'] ?? ''),
                'set'    => (string)($m['set'] ?? ''),
                // Official data present for this ID means the mock is inert and removable. The
                // generator reports the same thing in its log on every run.
                'superseded' => SWUMockIsSuperseded($cardID),
            ];
        }
        echo json_encode(['ok' => true, 'rows' => $rows]);
        exit;
    }

    if ($action === 'edit') {
        $cardID = (string)($_POST['cardID'] ?? $_GET['cardID'] ?? '');
        $entries = SWULoadMockCards();
        if (!isset($entries[$cardID])) {
            echo json_encode(['ok' => false, 'error' => 'No mock for ' . $cardID]);
            exit;
        }
        echo json_encode([
            'ok' => true, 'cardID' => $cardID, 'kind' => 'new', 'canonical' => null,
            'canonicalKnown' => false,
            'name' => (string)($entries[$cardID]['title'] ?? ''),
            'mock' => $entries[$cardID],
            'alreadyMocked' => true, 'alreadyOverridden' => false, 'alreadyOfficial' => false,
        ]);
        exit;
    }

    if ($action === 'delete') {
        $cardID = (string)($_POST['cardID'] ?? '');
        $ok = SWUDeleteMockCard($cardID);
        $art = $ok ? preview_tool_delete_art($cardID) : [];
        echo json_encode($ok
            ? ['ok' => true, 'message' => $cardID . ' removed'
                . (count($art) > 0 ? ' (' . count($art) . ' art file(s) deleted)' : '')
                . '. Regenerate to drop it from the dictionaries.']
            : ['ok' => false, 'error' => 'No mock entry for ' . $cardID]);
        exit;
    }

    if ($action === 'setlist') {
        $set = strtoupper((string)($_POST['set'] ?? $_GET['set'] ?? ''));
        if ($set === '') { echo json_encode(['ok' => false, 'error' => 'No set given.']); exit; }
        $mocked = SWULoadMockCards();
        $rows = [];
        foreach (SWUPreviewFetchSetList($set) as $p) {
            $cardID = $set . '_' . SWUPreviewPadNumber($set, (string)$p['cardNumber']);
            if (isset($mocked[$cardID]))                 $state = 'mocked';
            elseif (CardIDOverride($cardID) !== $cardID) $state = 'overridden';
            elseif (IsSWUCardID($cardID))                $state = 'official';
            else                                         $state = 'todo';
            $rows[] = ['cardID' => $cardID, 'name' => $p['cardName'], 'state' => $state];
        }
        echo json_encode(['ok' => true, 'rows' => $rows, 'count' => count($rows)]);
        exit;
    }

    if ($action === 'regen') {
        // The same chain zzSWUSimRefresh.php orchestrates: dictionaries -> keywords. Ability stubs
        // are written by the dictionary generator itself.
        $php  = preview_tool_php_cli();
        if ($php === '') {
            echo json_encode(['ok' => false, 'error' => 'No PHP CLI binary found — run the '
                . 'generators from a shell instead.']);
            exit;
        }
        $root = __DIR__;
        $steps = [
            'dictionaries' => escapeshellarg($php) . ' -d xdebug.mode=off '
                . escapeshellarg($root . '/zzCardCodeGenerator.php') . ' rootName=SWUSim',
            'keywords'     => escapeshellarg($php) . ' -d xdebug.mode=off '
                . escapeshellarg($root . '/Data/ProcessKeywordsSWU.php'),
        ];
        $log = [];
        foreach ($steps as $label => $cmd) {
            $out = []; $code = 0;
            exec('cd ' . escapeshellarg($root) . ' && ' . $cmd . ' 2>&1', $out, $code);
            $log[] = '=== ' . $label . ' (exit ' . $code . ') ===';
            $log[] = implode("\n", array_slice($out, -20));
            if ($code !== 0) {
                echo json_encode(['ok' => false, 'error' => 'Step "' . $label . '" failed.',
                                  'log' => implode("\n", $log)]);
                exit;
            }
        }
        echo json_encode(['ok' => true, 'message' => 'Regenerated — mocks are now playable.',
                          'log' => implode("\n", $log)]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Unknown action: ' . $action]);
    exit;
}

$existing = SWULoadMockCards();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>SWUSim Preview Card Tool</title>
<script src="<?php echo _VersionAsset('/TCGEngine/Core/StyledDialog.js'); ?>"></script>
<style>
  body { font-family: system-ui, sans-serif; margin: 0; background: #1e1e1e; color: #ddd; }
  header { padding: 12px 20px; background: #252526; border-bottom: 1px solid #3c3c3c;
           display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  main { display: flex; gap: 20px; padding: 20px; align-items: flex-start; }
  #form { flex: 1 1 auto; min-width: 0; max-width: 760px; }
  #art { flex: 0 0 260px; }
  #art img { width: 100%; border-radius: 8px; margin-bottom: 10px; background: #111; }
  label { display: block; margin: 10px 0 3px; font-size: 12px; color: #9cdcfe; }
  input[type=text], textarea { width: 100%; box-sizing: border-box; padding: 6px;
    background: #333; color: #eee; border: 1px solid #555; border-radius: 4px; font: inherit; }
  textarea { min-height: 66px; resize: vertical; }
  button { padding: 8px 14px; background: #0e639c; color: #fff; border: 0; border-radius: 4px;
    cursor: pointer; font: inherit; }
  button.secondary { background: #444; }
  .row { display: flex; gap: 10px; }
  .row > div { flex: 1; min-width: 0; }
  .status { padding: 10px; border-radius: 4px; margin: 12px 0; display: none; }
  .status.ok { background: #1e3a1e; color: #b5e8b5; display: block; }
  .status.err { background: #4a1e1e; color: #f0b5b5; display: block; }
  .badge { padding: 2px 8px; border-radius: 10px; font-size: 12px; background: #444; }
  .badge.reprint { background: #6a4a12; }
  .badge.todo { background: #1e3a5a; }
  .badge.mocked { background: #1e3a1e; }
  fieldset { border: 1px solid #3c3c3c; border-radius: 6px; margin-top: 16px; }
  legend { color: #9cdcfe; font-size: 12px; }
  table { border-collapse: collapse; width: 100%; margin-top: 10px; font-size: 14px; }
  td, th { padding: 5px 8px; border-bottom: 1px solid #333; text-align: left; }
  pre { white-space: pre-wrap; background: #111; padding: 12px; border-radius: 6px;
        max-height: 340px; overflow: auto; font-size: 12px; }
</style></head><body>
<header>
  <strong>SWUSim Preview Card Tool</strong>
  <span class="badge"><?= count($existing) ?> mock(s) defined</span>
  <button class="secondary" onclick="loadList()">Existing mocks</button>
  <input type="text" id="setcode" placeholder="HMW" style="width:74px">
  <button class="secondary" onclick="loadSetList()">List previewed cards</button>
  <!-- Filters the LAST pull client-side (the rows are already in hand), so toggling never refetches. -->
  <label style="font-size:12px; display:inline-flex; align-items:center; gap:4px; cursor:pointer"
         title="Show only cards with no mock entry and no CardIDOverride — i.e. nothing represents them yet">
    <input type="checkbox" id="needsEntryOnly" onchange="onNeedsEntryToggle()"> Needs Entry only</label>
  <button onclick="doRegen()">Regenerate</button>
</header>
<main>
  <div id="form">
    <label>Preview card link</label>
    <div class="row">
      <div><input type="text" id="link" placeholder="https://swudb.com/card/HMW/004"
                  onkeydown="if(event.key==='Enter')doImport()"></div>
      <div style="flex:0 0 auto"><button onclick="doImport()">Import</button></div>
    </div>
    <div id="status" class="status"></div>
    <div id="editor"></div>
  </div>
  <div id="art"></div>
</main>
<script>
var current = null;

function show(msg, ok) {
  var el = document.getElementById('status');
  el.className = 'status ' + (ok ? 'ok' : 'err');
  el.textContent = msg;
}

function esc(s) { return String(s === null || s === undefined ? '' : s).replace(/[<>&"]/g, function (c) {
  return ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;' })[c];
}); }

function post(action, extra) {
  var body = new FormData();
  body.append('action', action);
  body.append('rootName', 'SWUSim');
  Object.keys(extra || {}).forEach(function (k) { body.append(k, extra[k]); });
  return fetch(location.pathname + '?rootName=SWUSim', { method: 'POST', body: body })
    .then(function (r) { return r.json(); });
}

function field(key, label, value, type) {
  var v = (value === null || value === undefined) ? '' : value;
  if (type === 'textarea') {
    return '<label>' + label + '</label><textarea data-key="' + key + '">' + esc(v) + '</textarea>';
  }
  if (type === 'checkbox') {
    return '<label>' + label + '</label><input type="checkbox" data-key="' + key + '"' +
           (v ? ' checked' : '') + '>';
  }
  return '<label>' + label + '</label><input type="text" data-key="' + key + '" value="' + esc(v) + '">';
}

function renderArt(m) {
  var html = '';
  if (m.imageUrl) html += '<img src="' + esc(m.imageUrl) + '" alt="front">';
  if (m.imageUrlBack) html += '<img src="' + esc(m.imageUrlBack) + '" alt="back">';
  document.getElementById('art').innerHTML = html;
}

function doImport() {
  var link = document.getElementById('link').value;
  post('import', { link: link }).then(function (d) {
    if (!d.ok) { show(d.error, false); return; }
    current = d;
    if (d.alreadyOfficial) {
      show(d.cardID + ' already exists as official data — no mock needed.', false);
      renderArt(d.mock);
      return;
    }
    if (d.kind === 'reprint') { renderReprint(d); return; }
    renderEditor(d);
    show('Imported ' + d.cardID + (d.alreadyMocked ? ' (already mocked — Create Mock overwrites it)' : ''), true);
  }).catch(function (e) { show('Import failed: ' + e, false); });
}

function renderReprint(d) {
  var known = d.canonicalKnown;
  document.getElementById('editor').innerHTML =
    '<p><span class="badge reprint">reprint</span> ' + esc(d.cardID) + ' &mdash; ' + esc(d.name) + '</p>' +
    '<p>This is a reprint of <strong>' + esc(d.canonical) + '</strong>. The sim already plays that ' +
    'card&rsquo;s implementation, so it needs an override mapping, not a mock card.</p>' +
    (d.alreadyOverridden ? '<p><span class="badge">already overridden</span></p>' : '') +
    (known ? '' : '<p><span class="badge reprint">' + esc(d.canonical) +
      ' is not in the dictionaries — regenerate first</span></p>') +
    '<button onclick="createOverride()"' + (known ? '' : ' disabled') + '>Add override ' +
    esc(d.cardID) + ' &rarr; ' + esc(d.canonical) + '</button>';
  renderArt(d.mock);
  show('Imported ' + d.cardID + ' — classified as a reprint of ' + d.canonical, true);
}

function renderEditor(d) {
  var m = d.mock;
  var html = '<p><span class="badge">' + (d.alreadyMocked ? 'editing' : 'new card') + '</span> ' +
    esc(d.cardID) + '</p>' +
    field('title', 'Title', m.title) +
    field('subtitle', 'Subtitle', m.subtitle) +
    '<div class="row"><div>' + field('type', 'Type', m.type) + '</div>' +
    '<div>' + field('arena', 'Arena', m.arena) + '</div>' +
    '<div>' + field('rarity', 'Rarity', m.rarity) + '</div>' +
    '<div>' + field('set', 'Set', m.set) + '</div></div>' +
    '<div class="row"><div>' + field('cost', 'Cost', m.cost) + '</div>' +
    '<div>' + field('power', 'Power', m.power) + '</div>' +
    '<div>' + field('hp', 'HP', m.hp) + '</div>' +
    '<div>' + field('upgradePower', 'Upg. Power', m.upgradePower) + '</div>' +
    '<div>' + field('upgradeHp', 'Upg. HP', m.upgradeHp) + '</div></div>' +
    field('aspect', 'Aspects (comma separated)', (m.aspect || []).join(', ')) +
    field('trait', 'Traits (comma separated)', (m.trait || []).join(', ')) +
    field('text', 'Card text', m.text, 'textarea') +
    field('epicAction', 'Epic Action', m.epicAction, 'textarea') +
    field('deployText', 'Deploy text (leader unit side)', m.deployText, 'textarea') +
    field('unique', 'Unique', m.unique, 'checkbox') +
    field('imageUrl', 'Front art URL', m.imageUrl) +
    field('imageUrlBack', 'Back art URL', m.imageUrlBack);

  if (m.type === 'Leader') {
    html += '<fieldset><legend>Deployed leader-unit side &mdash; NOT in the source data, read these ' +
      'off the back art</legend>' +
      field('leaderUnitTitle', 'Leader unit title', m.leaderUnitTitle) +
      field('leaderUnitSubtitle', 'Leader unit subtitle', m.leaderUnitSubtitle) +
      field('leaderUnitTrait', 'Leader unit traits (comma separated)', (m.leaderUnitTrait || []).join(', ')) +
      field('leaderUnitArena', 'Leader unit arena (Ground | Space)', m.leaderUnitArena) +
      field('leaderUnitType', 'Leader unit type', m.leaderUnitType) +
      '</fieldset>';
  }

  html += '<p style="margin-top:16px"><button onclick="createMock()">Create Mock</button></p>';
  document.getElementById('editor').innerHTML = html;
  renderArt(m);
}

function collect() {
  var out = {};
  document.querySelectorAll('#editor [data-key]').forEach(function (el) {
    out[el.getAttribute('data-key')] = (el.type === 'checkbox') ? el.checked : el.value;
  });
  return out;
}

function createMock() {
  post('create', { cardID: current.cardID, mock: JSON.stringify(collect()) })
    .then(function (d) { show(d.ok ? d.message : d.error, d.ok); })
    .catch(function (e) { show('Create failed: ' + e, false); });
}

function createOverride() {
  post('override', { cardID: current.cardID, canonical: current.canonical, name: current.name })
    .then(function (d) { show(d.ok ? d.message : d.error, d.ok); })
    .catch(function (e) { show('Override failed: ' + e, false); });
}

function loadList() {
  post('list', {}).then(function (d) {
    if (!d.ok) { show(d.error, false); return; }
    if (!d.rows.length) {
      document.getElementById('editor').innerHTML = '<p>No mocks defined yet.</p>';
      return;
    }
    var html = '<h3>Mocks in CardMocks.php</h3><table><tr><th>CardID</th><th>Title</th>' +
      '<th>Type</th><th></th><th></th></tr>';
    d.rows.forEach(function (r) {
      html += '<tr><td>' + esc(r.cardID) + '</td><td>' + esc(r.title) + '</td><td>' + esc(r.type) + '</td>' +
        '<td>' + (r.superseded ? '<span class="badge reprint">superseded &mdash; safe to delete</span>' : '') + '</td>' +
        '<td><button class="secondary" onclick="editMock(\'' + esc(r.cardID) + '\')">Edit</button> ' +
        '<button class="secondary" onclick="deleteMock(\'' + esc(r.cardID) + '\')">Delete</button></td></tr>';
    });
    document.getElementById('editor').innerHTML = html + '</table>';
  });
}

function editMock(cardID) {
  post('edit', { cardID: cardID }).then(function (d) {
    if (!d.ok) { show(d.error, false); return; }
    current = d;
    renderEditor(d);
    show('Editing ' + cardID, true);
  });
}

function deleteMock(cardID) {
  StyledConfirm('Delete mock ' + cardID + ' and its mock_ art files?',
                { danger: true, confirmLabel: 'Delete' }).then(function (ok) {
    if (!ok) return;
    post('delete', { cardID: cardID }).then(function (d) {
      show(d.ok ? d.message : d.error, d.ok);
      if (d.ok) loadList();
    });
  });
}

// Last set-list pull, kept so the "Needs Entry only" checkbox can re-filter without refetching.
var setList = null;   // { set: 'HMW', rows: [...] }

// "Needs Entry" = nothing represents this card yet: no mock entry AND no CardIDOverride alias.
// 'official' deliberately still shows — a previewed card whose real data has since landed is rare on a
// live preview set, and silently hiding a whole state is worse than one extra visible row.
function setListNeedsEntry(r) {
  return r.state !== 'mocked' && r.state !== 'overridden';
}

function loadSetList() {
  var set = document.getElementById('setcode').value;
  show('Fetching ' + set + ' previewed cards…', true);
  post('setlist', { set: set }).then(function (d) {
    if (!d.ok) { setList = null; show(d.error, false); return; }
    setList = { set: set, rows: d.rows || [] };
    renderSetList();
  });
}

// Toggling the checkbox must only re-render when the set list is what's ON SCREEN. #editor is shared —
// the mock edit form and the existing-mocks table render there too — so an unguarded re-render would
// replace an in-progress edit with a stale list.
function onNeedsEntryToggle() {
  if (document.getElementById('setListPanel')) renderSetList();
}

function renderSetList() {
  if (!setList) return;                       // nothing pulled yet — the checkbox is a no-op
  var onlyNeeds = document.getElementById('needsEntryOnly').checked;
  var total = setList.rows.length;
  var rows = onlyNeeds ? setList.rows.filter(setListNeedsEntry) : setList.rows;

  var heading = onlyNeeds
    ? esc(setList.set) + ' &mdash; showing ' + rows.length + ' of ' + total + ' previewed card(s)'
    : esc(setList.set) + ' &mdash; ' + total + ' previewed card(s)';
  var html = '<div id="setListPanel"><h3>' + heading + '</h3>';

  if (rows.length === 0) {
    html += '<p>' + (onlyNeeds && total > 0
      ? 'Every previewed card in this set is already mocked or overridden.'
      : 'No previewed cards found.') + '</p>';
    document.getElementById('editor').innerHTML = html + '</div>';
  } else {
    html += '<table><tr><th>CardID</th><th>Name</th><th>State</th><th></th></tr>';
    rows.forEach(function (r) {
      html += '<tr><td>' + esc(r.cardID) + '</td><td>' + esc(r.name) + '</td>' +
        '<td><span class="badge ' + esc(r.state) + '">' + esc(r.state) + '</span></td>' +
        '<td><button class="secondary" onclick="importCard(\'' + esc(r.cardID) + '\')">Import</button></td></tr>';
    });
    document.getElementById('editor').innerHTML = html + '</table></div>';
  }
  show('Listed ' + rows.length + (onlyNeeds ? ' of ' + total : '') + ' previewed card(s) in ' + setList.set, true);
}

function importCard(cardID) {
  var parts = cardID.split('_');
  document.getElementById('link').value = parts[0] + '/' + parts[1];
  doImport();
}

function doRegen() {
  show('Regenerating — this takes a minute…', true);
  post('regen', {}).then(function (d) {
    show(d.ok ? d.message : d.error, d.ok);
    document.getElementById('editor').innerHTML = '<pre>' + esc(d.log || '') + '</pre>';
  }).catch(function (e) { show('Regen failed: ' + e, false); });
}
</script>
</body></html>
