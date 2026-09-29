<?php
/** Saved-deck SWUSim launcher. Install as SWUDeck/Simulation.php in TCGEngine. */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/GamestateParser.php';
require_once __DIR__ . '/ZoneAccessors.php';
require_once __DIR__ . '/ZoneClasses.php';
require_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/SimulationRuntime.php';
require_once __DIR__ . '/SimulationJobs.php';

function simEscape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function simCardID($card): string {
    $id = (string)($card->CardID ?? '');
    if (function_exists('SWUNormalizeDictionaryKey')) $id = SWUNormalizeDictionaryKey($id);
    if (!preg_match('/^[A-Z0-9]{2,5}_(?:T[0-9]{2}|[0-9]{2,3})$/', $id)) {
        throw new RuntimeException('The saved deck contains an unmapped card. Refresh its card identities before simulating.');
    }
    return $id;
}

function simActiveCards(array $zone): array {
    $counts = [];
    foreach ($zone as $card) {
        if (!$card || (method_exists($card, 'Removed') && $card->Removed())) continue;
        $id = simCardID($card);
        $counts[$id] = ($counts[$id] ?? 0) + 1;
    }
    ksort($counts);
    return $counts;
}

$gameName = (string)($_GET['gameName'] ?? $_POST['gameName'] ?? '');
if (!ctype_digit($gameName) || !IsUserLoggedIn()) {
    http_response_code(403);
    exit('Sign in and choose a saved deck.');
}
if (CheckLoggedInUserModStrict() !== '') {
    http_response_code(403);
    exit('Deck simulation is currently available to developers only.');
}
$asset = LoadAssetData(1, $gameName);
if (!$asset || (string)$asset['assetOwner'] !== (string)LoggedInUser()) {
    http_response_code(403);
    exit('Only the deck owner can run simulations.');
}
if (empty($_SESSION['swu_simulation_csrf'])) $_SESSION['swu_simulation_csrf'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['swu_simulation_csrf'];
$error = '';
$result = null;
$opponents = [];
$owner = (string)LoggedInUser();
$jobPath = swuSimulationJobFile($owner, $gameName);
$job = swuSimulationReadJob($jobPath);
$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');
if ($action === 'status') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($job ?? ['status' => 'idle'], JSON_THROW_ON_ERROR);
    exit;
}
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) throw new RuntimeException('This form expired. Reload the page.');
        if ($action === 'replay') {
            $replayId = (string)($_POST['replayId'] ?? '');
            $url = swuSimulationImportReplay($replayId, $owner, $gameName);
            header('Location: ' . $url, true, 303);
            exit;
        }
        if ($action !== 'start') throw new RuntimeException('Choose a simulation action.');
        $opponent = (string)($_POST['opponent'] ?? '');
        $seed = trim((string)($_POST['seed'] ?? ''));
        if ($seed === '') $seed = 'run-' . bin2hex(random_bytes(6));
        $samples = filter_var($_POST['samples'] ?? null, FILTER_VALIDATE_INT);
        // ⚠ '-' belongs in the opponent class: fixture names are <leader-title>_<set>_<base-archetype> since
        // 2026-09-29 and every one of them contains hyphens. Must stay in step with the same class in
        // SimulationRuntime.php's swuSimulationOpponentPath(), which builds the file path from this id.
        if (!preg_match('/^[a-z0-9_-]+$/', $opponent) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $seed)
            || $samples === false || $samples < 1 || $samples > 20) {
            throw new RuntimeException('Choose an opponent and 1–20 games. If you set a seed, use letters, numbers, hyphens, or underscores.');
        }
        $GLOBALS['gameName'] = $gameName;
        ParseGamestate(__DIR__ . '/');
        $leader = simActiveCards(GetLeader(1));
        $base = simActiveCards(GetBase(1));
        $main = simActiveCards(GetMainDeck(1));
        if (count($leader) !== 1 || count($base) !== 1 || !$main) {
            throw new RuntimeException('Simulations currently require one leader, one base, and a main deck.');
        }
        $deck = [
            'leader' => ['id' => array_key_first($leader)],
            'base' => ['id' => array_key_first($base)],
            'deck' => [],
        ];
        foreach ($main as $id => $count) $deck['deck'][] = ['id' => $id, 'count' => $count];
        // Validate before closing the popup, while any error can still be shown in the form.
        swuSimulationOpponentPath($opponent);
        swuSimulationDeckText($deck);
        $lock = fopen($jobPath . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('A simulation is already running for this deck.');
        $job = ['status' => 'running', 'id' => bin2hex(random_bytes(12)), 'startedAt' => time()];
        swuSimulationWriteJob($jobPath, $job);
        ignore_user_abort(true);
        session_write_close();
        set_time_limit(30 + 125 * (int)ceil($samples / 4));
        try {
            $result = swuSimulationRun($deck, $opponent, $seed, $samples, $owner, $gameName);
            $job = ['status' => 'done', 'id' => $job['id'], 'startedAt' => $job['startedAt'], 'finishedAt' => time(), 'result' => $result];
        } catch (Throwable $exception) {
            $job = ['status' => 'failed', 'id' => $job['id'], 'startedAt' => $job['startedAt'], 'finishedAt' => time(), 'error' => $exception->getMessage()];
        } finally {
            swuSimulationWriteJob($jobPath, $job);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['status' => $job['status'], 'id' => $job['id']], JSON_THROW_ON_ERROR);
        exit;
    }
    $opponents = swuSimulationOpponents();
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'failed', 'error' => $error], JSON_THROW_ON_ERROR);
    exit;
}
if (($job['status'] ?? '') === 'done') $result = $job['result'] ?? null;
if (($job['status'] ?? '') === 'failed') $error = (string)($job['error'] ?? 'The simulation failed.');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Deck simulation</title>
<link rel="stylesheet" href="/TCGEngine/SWUDeck/Simulation.css?v=<?=simEscape((string)filemtime(__DIR__ . '/Simulation.css'))?>">
</head><body>
<main class="simulation-shell">
<?php if (empty($_GET['embedded'])): ?><a class="back-link" href="/TCGEngine/NextTurn.php?gameName=<?=simEscape($gameName)?>&amp;playerID=1&amp;folderPath=SWUDeck">← Back to deck</a><?php endif; ?>
<header class="intro">
  <h1>Test your deck <span>against the field.</span></h1>
  <p>Petranaki bots play both your saved deck and a selected opponent deck through simulated games. Keep working on your deck while they play, then return to review the results and replays.</p>
  <p class="bot-note">The bots use a general strategy and may not play every deck optimally. We may support deck-optimized bots in the future. Have feedback on their play? Share it in the <a href="https://discord.gg/8JgfUsNf2z" target="_blank" rel="noopener noreferrer">Petranaki Discord ↗</a>.</p>
</header>
<?php if ($error !== ''): ?><div class="error" role="alert"><?=simEscape($error)?></div><?php endif; ?>
<?php if ($opponents): ?>
<form class="simulation-form" method="post" id="simulationForm"><input type="hidden" name="gameName" value="<?=simEscape($gameName)?>"><input type="hidden" name="csrf" value="<?=simEscape($csrf)?>"><input type="hidden" name="action" value="start">
<div class="setup-label">01 / MATCHUP</div>
<div class="fields">
  <label class="field field-opponent"><span>Opponent deck</span><select name="opponent" required>
  <?php foreach ($opponents as $item): if (!is_array($item)) continue; ?>
  <option value="<?=simEscape($item['id'] ?? '')?>" <?=($item['id'] ?? '') === ($_POST['opponent'] ?? '') ? 'selected' : ''?>><?=simEscape($item['label'] ?? $item['id'] ?? '')?></option>
  <?php endforeach; ?></select></label>
  <label class="field field-games"><span>Games <em>(1–20)</em></span><input name="samples" type="number" required min="1" max="20" value="<?=simEscape($_POST['samples'] ?? '1')?>"></label>
</div>
<details class="advanced-options"><summary>Advanced: use a repeatable seed</summary><label class="field"><span>Seed</span><input name="seed" maxlength="64" pattern="[A-Za-z0-9_-]+" placeholder="Auto-generated when blank" value="<?=simEscape($_POST['seed'] ?? '')?>"></label><p>Use the same seed to repeat a run with the same random setup.</p></details>
<div class="form-footer"><button class="run-button" type="submit" <?=($job['status'] ?? '') === 'running' ? 'disabled' : ''?>><?=($job['status'] ?? '') === 'running' ? 'Simulation running…' : 'Run simulation →'?></button><p>Up to four games run at once. Larger batches may take several minutes.</p></div>
</form>
<?php else: ?><div class="error" role="status">No local meta opponents are available on this server yet.</div>
<?php endif; ?>
<?php if (($job['status'] ?? '') === 'running'): ?><div class="processing" role="status"><span class="processing-spinner"></span><div><strong>Simulation in progress</strong><p>You can keep editing your deck. The Simulate indicator will update when results are ready.</p></div></div><?php endif; ?>
<?php if ($result): $aggregate = $result['aggregate'] ?? []; ?>
<section class="results" aria-labelledby="results-heading"><div class="section-heading"><span>02 / RESULTS</span><h2 id="results-heading">Run complete</h2></div>
<div class="score-grid">
  <div class="score score-win"><strong><?=simEscape($aggregate['counts']['ours'] ?? 0)?></strong><span>Wins</span></div>
  <div class="score score-loss"><strong><?=simEscape($aggregate['counts']['opponent'] ?? 0)?></strong><span>Losses</span></div>
  <div class="score"><strong><?=simEscape($aggregate['counts']['incomplete'] ?? 0)?></strong><span>Incomplete</span></div>
  <div class="score score-rate"><strong><?=isset($aggregate['ourWinRate']) && $aggregate['ourWinRate'] !== null ? simEscape(round($aggregate['ourWinRate'] * 100, 1)) . '%' : '—'?></strong><span>Win rate</span></div>
</div>
<div class="run-meta">WIN RATE COUNTS COMPLETED GAMES ONLY <span>·</span> DECK HASH <?=simEscape(substr((string)($result['deckHash'] ?? ''), 0, 12))?></div>
<div class="sample-list"><?php foreach (($result['samples'] ?? []) as $sample): if (!is_array($sample)) continue; $completed = ($sample['status'] ?? '') === 'completed'; $won = $completed && (int)($sample['winner'] ?? 0) === 2; ?>
  <article class="sample"><div class="sample-main"><span class="sample-outcome <?=$completed ? ($won ? 'is-win' : 'is-loss') : 'is-incomplete'?>"><?=$completed ? ($won ? 'WIN' : 'LOSS') : simEscape(strtoupper((string)($sample['status'] ?? 'INCOMPLETE')))?></span><div><strong><?=simEscape($sample['seed'] ?? '')?></strong><?php if ($completed): ?><small><?=simEscape($sample['rounds'] ?? '?')?> rounds</small><?php endif; ?></div></div>
  <?php if (!empty($sample['replayId'])): ?><form method="post" target="_blank" class="replay-form"><input type="hidden" name="gameName" value="<?=simEscape($gameName)?>"><input type="hidden" name="csrf" value="<?=simEscape($csrf)?>"><input type="hidden" name="action" value="replay"><input type="hidden" name="replayId" value="<?=simEscape($sample['replayId'])?>"><button type="submit">View replay ↗</button></form><?php endif; ?>
  <?php if (!empty($sample['engineError'])): ?><small class="engine-error"><?=simEscape($sample['engineError'])?></small><?php endif; ?></article>
<?php endforeach; ?></div></section>
<?php endif; ?>
<p class="disclaimer">Simulated games do not change SWUStats match records.</p>
</main>
<script>
if (window.parent !== window) document.addEventListener('keydown', function (event) { if (event.key === 'Escape') window.parent.postMessage('swu-simulation-close', window.location.origin); });
var simulationForm = document.getElementById('simulationForm');
if (simulationForm) simulationForm.addEventListener('submit', function (event) {
  event.preventDefault();
  var button = this.querySelector('.run-button');
  button.disabled = true;
  button.textContent = 'Starting simulation…';
  var form = this;
  var previousJobID = <?=json_encode((string)($job['id'] ?? ''))?>;
  var backgrounded = false;
  fetch(window.location.href, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
    .then(function (response) { return response.json(); })
    .then(function (data) { if (data.status === 'failed') throw new Error(data.error || 'Simulation failed.'); })
    .catch(function (error) { if (!backgrounded) { clearInterval(poll); alert(error.message); button.disabled = false; button.textContent = 'Run simulation →'; } });
  var checks = 0;
  var poll = setInterval(function () {
    fetch('Simulation.php?gameName=<?=simEscape($gameName)?>&action=status', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.id && data.id !== previousJobID && (data.status === 'running' || data.status === 'done')) {
          clearInterval(poll);
          backgrounded = true;
          if (window.parent !== window) window.parent.postMessage('swu-simulation-started', window.location.origin);
          else window.location.reload();
        } else if (data.id && data.id !== previousJobID && data.status === 'failed') { clearInterval(poll); alert(data.error || 'Simulation failed.'); button.disabled = false; button.textContent = 'Run simulation →'; }
      });
    if (++checks > 60) { clearInterval(poll); button.disabled = false; button.textContent = 'Run simulation →'; }
  }, 500);
});
</script>
</body></html>
