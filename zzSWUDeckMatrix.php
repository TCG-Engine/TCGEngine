<?php
// zzSWUDeckMatrix.php — mod tool to re-fetch melee tournament data.
//
// Each tournament is re-imported by its own request: the largest event in the archive
// (1485 decks) takes ~6 minutes because the parser makes one HTTP call per decklist, so a
// single "do all 61" request would run for hours and die to a gateway timeout. The client
// drives the list sequentially instead.
//
// Also surfaces a data-integrity audit per tournament: the share of matchup pairings whose
// two recordings contradict each other. That can only happen through a win/loss attribution
// error, so a non-zero figure is a bug signal, not noise.

include_once './AccountFiles/AccountSessionAPI.php';
require_once './Database/ConnectionManager.php';

$error = CheckLoggedInUserMod();
if ($error !== '') {
    echo htmlspecialchars($error);
    exit;
}

// mysqli_query returns false on error; feeding that to mysqli_fetch_assoc() is a fatal, which
// would emit an HTML error page in place of the JSON the client expects. Fail loudly instead.
class MatrixQueryError extends Exception {}

function MatrixQuery($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if ($res === false) {
        throw new MatrixQueryError(mysqli_error($conn));
    }
    return $res;
}

// Per-tournament row counts plus (optionally) the pairing-consistency audit.
// Built from whole-table aggregates merged in PHP rather than correlated subqueries per
// tournament: the per-row form issued ~245 queries for 61 tournaments and took ~10s.
//
// The audit is OPT-IN and off by default. It self-joins meleetournamentmatchup, which carries
// no index on player/opponent — only PRIMARY(matchupID) — so the join is ~69k x 69k rows.
// MySQL 8+/9 hides that behind a hash join (~0.4s locally); MariaDB, which prod runs, falls
// back to a block nested loop and the request hangs. Applying migration
// 10_meleetournamentmatchup_indexes.sql makes it fast everywhere.
function MatrixTournamentRows($conn, $withAudit = false) {
    $decks = [];
    $res = MatrixQuery($conn, "
        SELECT tournamentId,
               COUNT(*) AS decks,
               SUM(CASE WHEN leader IS NULL OR leader = '' THEN 1 ELSE 0 END) AS noLeader
        FROM meleetournamentdeck GROUP BY tournamentId");
    while ($r = mysqli_fetch_assoc($res)) {
        $decks[(int)$r['tournamentId']] = ['decks' => (int)$r['decks'], 'noLeader' => (int)$r['noLeader']];
    }

    $matchups = [];
    $res = MatrixQuery($conn, "
        SELECT d.tournamentId, COUNT(*) AS matchups
        FROM meleetournamentmatchup m
        JOIN meleetournamentdeck d ON d.deckID = m.player
        GROUP BY d.tournamentId");
    while ($r = mysqli_fetch_assoc($res)) {
        $matchups[(int)$r['tournamentId']] = (int)$r['matchups'];
    }

    $audits = [];
    if ($withAudit) {
        $res = MatrixQuery($conn, "
            SELECT d.tournamentId,
                   COUNT(*) AS pairings,
                   SUM(CASE WHEN NOT (a.w = b.l AND a.l = b.w AND a.d = b.d)
                            THEN 1 ELSE 0 END) AS inconsistent
            FROM (SELECT player, opponent, SUM(wins) w, SUM(losses) l, SUM(draws) d
                    FROM meleetournamentmatchup GROUP BY player, opponent) a
            JOIN (SELECT player, opponent, SUM(wins) w, SUM(losses) l, SUM(draws) d
                    FROM meleetournamentmatchup GROUP BY player, opponent) b
              ON b.player = a.opponent AND b.opponent = a.player
            JOIN meleetournamentdeck d ON d.deckID = a.player
            WHERE a.player < a.opponent
            GROUP BY d.tournamentId");
        while ($r = mysqli_fetch_assoc($res)) {
            $p = (int)$r['pairings'];
            $i = (int)$r['inconsistent'];
            $audits[(int)$r['tournamentId']] = [
                'pairings' => $p,
                'inconsistent' => $i,
                'pct' => $p > 0 ? round(100 * $i / $p, 1) : 0.0,
            ];
        }
    }

    $rows = [];
    $res = MatrixQuery($conn, "
        SELECT tournamentID, tournamentLink, tournamentName, tournamentDate, roundId
        FROM meleetournament ORDER BY tournamentID DESC");
    while ($r = mysqli_fetch_assoc($res)) {
        $id = (int)$r['tournamentID'];
        $r['tournamentID'] = $id;
        $r['decks'] = $decks[$id]['decks'] ?? 0;
        $r['noLeader'] = $decks[$id]['noLeader'] ?? 0;
        $r['matchups'] = $matchups[$id] ?? 0;
        if ($withAudit) {
            $r['audit'] = $audits[$id] ?? ['pairings' => 0, 'inconsistent' => 0, 'pct' => 0.0];
        }
        $rows[] = $r;
    }
    return $rows;
}

// A pairing is the two matchup rows describing one meeting, from each side; they must mirror.
// Both queries AGGREGATE per (player, opponent) first: two decks can meet more than once
// (swiss then top cut), and a naive row-level self-join cross-matches round 3's result
// against round 8's, reporting phantom inconsistencies that are not attribution errors.
function MatrixAuditTournament($conn, $tournamentId) {
    $sql = "
        SELECT
          COUNT(*) AS pairings,
          SUM(CASE WHEN NOT (a.w = b.l AND a.l = b.w AND a.d = b.d)
                   THEN 1 ELSE 0 END) AS inconsistent
        FROM (SELECT player, opponent, SUM(wins) w, SUM(losses) l, SUM(draws) d
                FROM meleetournamentmatchup GROUP BY player, opponent) a
        JOIN (SELECT player, opponent, SUM(wins) w, SUM(losses) l, SUM(draws) d
                FROM meleetournamentmatchup GROUP BY player, opponent) b
          ON b.player = a.opponent AND b.opponent = a.player
        WHERE a.player < a.opponent
          AND a.player IN (SELECT deckID FROM meleetournamentdeck WHERE tournamentId = ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $tournamentId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $pairings = (int)($row['pairings'] ?? 0);
    $inconsistent = (int)($row['inconsistent'] ?? 0);
    return [
        'pairings' => $pairings,
        'inconsistent' => $inconsistent,
        'pct' => $pairings > 0 ? round(100 * $inconsistent / $pairings, 1) : 0.0,
    ];
}

// Remove a tournament and everything hanging off it. Matchups reference deck IDs, so they
// must go first or they are orphaned rather than deleted.
function MatrixDeleteTournament($conn, $tournamentId) {
    $deleted = ['matchups' => 0, 'decks' => 0, 'tournament' => 0];

    $sql = "DELETE FROM meleetournamentmatchup
             WHERE player   IN (SELECT deckID FROM (SELECT deckID FROM meleetournamentdeck WHERE tournamentId = ?) x)
                OR opponent IN (SELECT deckID FROM (SELECT deckID FROM meleetournamentdeck WHERE tournamentId = ?) y)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $tournamentId, $tournamentId);
    mysqli_stmt_execute($stmt);
    $deleted['matchups'] = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "DELETE FROM meleetournamentdeck WHERE tournamentId = ?");
    mysqli_stmt_bind_param($stmt, 'i', $tournamentId);
    mysqli_stmt_execute($stmt);
    $deleted['decks'] = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "DELETE FROM meleetournament WHERE tournamentID = ?");
    mysqli_stmt_bind_param($stmt, 'i', $tournamentId);
    mysqli_stmt_execute($stmt);
    $deleted['tournament'] = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    return $deleted;
}

// ---------------------------------------------------------------- JSON actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action !== '') {
    header('Content-Type: application/json');
    $conn = GetLocalMySQLConnection();
    if ($conn === false) {
        echo json_encode(['success' => false, 'message' => 'Error connecting to the database.']);
        exit;
    }

    // Any query failure becomes a JSON error rather than an HTML fatal the client can't parse.
    try {

    if ($action === 'list') {
        // audit=1 is opt-in: see the note on MatrixTournamentRows() for why it is not default.
        $withAudit = isset($_GET['audit']) && $_GET['audit'] === '1';
        echo json_encode([
            'success' => true,
            'audited' => $withAudit,
            'tournaments' => MatrixTournamentRows($conn, $withAudit),
        ]);
        exit;
    }

    if ($action === 'audit') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        echo json_encode(['success' => true, 'audit' => MatrixAuditTournament($conn, $id)]);
        exit;
    }

    // Delete only — no re-import. For clearing bad data ahead of a manual re-import.
    if ($action === 'delete') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = mysqli_prepare($conn, "SELECT tournamentLink, tournamentName FROM meleetournament WHERE tournamentID = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$row) {
            echo json_encode(['success' => false, 'message' => "No tournament with id $id."]);
            exit;
        }
        $deleted = MatrixDeleteTournament($conn, $id);
        echo json_encode([
            'success' => true,
            'id' => $id,
            'meleeId' => (int)$row['tournamentLink'],
            'name' => $row['tournamentName'],
            'deleted' => $deleted,
        ]);
        exit;
    }

    if ($action === 'refetch') {
        set_time_limit(1800);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        $stmt = mysqli_prepare($conn, "SELECT tournamentLink, tournamentName FROM meleetournament WHERE tournamentID = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$row) {
            echo json_encode(['success' => false, 'message' => "No tournament with id $id."]);
            exit;
        }
        $meleeId = (int)$row['tournamentLink'];
        if ($meleeId <= 0) {
            echo json_encode(['success' => false, 'message' => "Tournament $id has no melee link to re-fetch from."]);
            exit;
        }

        $deleted = MatrixDeleteTournament($conn, $id);

        // The parser must be loaded at FILE scope, never from inside a function or closure.
        // GeneratedCardDictionaries.php (pulled in by CardIdentifiers.php) assigns $titleData
        // and friends as top-level variables, and PHP scopes an include to the *calling*
        // function — loading it inside a closure makes them function-locals, so the `global
        // $titleData` in GetLeaderUUID() finds nothing and every leader/base lookup silently
        // returns null. The symptom is a "successful" import with every deck leaderless.
        //
        // It also needs Stats/ as the CWD, because it uses relative includes ("../Core/...").
        // The parser echoes stray warnings on some inputs; buffering keeps the JSON parseable.
        $failureReason = null;
        $diag = null;
        $newId = false;
        $stray = '';
        $prevCwd = getcwd();
        try {
            chdir(__DIR__ . '/Stats');
            ob_start();
            require_once __DIR__ . '/Stats/MeleeTournamentParserAPI.php';
            $newId = importMeleeTournamentById($meleeId, null, $failureReason, $diag);
            $stray = trim(ob_get_clean());
        } catch (Throwable $e) {
            $stray = trim((string)ob_get_clean());
            $failureReason = $e->getMessage();
            $newId = false;
        } finally {
            chdir($prevCwd);
        }

        if (!$newId) {
            echo json_encode([
                'success' => false,
                'oldId' => $id,
                'meleeId' => $meleeId,
                'deleted' => $deleted,
                'message' => $failureReason ?: 'Import failed.',
                'stray' => $stray !== '' ? mb_substr(strip_tags($stray), 0, 400) : null,
            ]);
            exit;
        }

        // Stats/MeleeTournamentParser.php runs `$conn->close()` at file scope, so requiring it
        // closes the shared mysqli handle out from under us — every read-back below would fail
        // with "mysqli object is already closed". Re-open before touching the DB again.
        $conn = GetLocalMySQLConnection();
        if ($conn === false) {
            echo json_encode(['success' => false, 'message' => 'Import finished but the DB connection could not be re-opened.']);
            exit;
        }

        $newId = (int)$newId;
        $after = null;
        foreach (MatrixTournamentRows($conn, false) as $r) {   // audit fetched separately below
            if ($r['tournamentID'] === $newId) { $after = $r; break; }
        }
        $audit = MatrixAuditTournament($conn, $newId);

        // Sanity gate. A card dictionary that failed to load produces a structurally valid
        // import in which every leader/base lookup returned null — rows are written, counts
        // look plausible, and nothing errors. Treat a wholly leaderless import as a failure
        // so it is never reported as success.
        $warnings = [];
        if ($after && $after['decks'] > 0 && $after['noLeader'] === $after['decks']) {
            echo json_encode([
                'success' => false,
                'oldId' => $id,
                'newId' => $newId,
                'meleeId' => $meleeId,
                'deleted' => $deleted,
                'after' => $after,
                'message' => "Imported {$after['decks']} decks but EVERY one is leaderless — "
                           . "the card dictionary almost certainly failed to load. Data is bad; re-run after fixing.",
                'stray' => $stray !== '' ? mb_substr(strip_tags($stray), 0, 400) : null,
            ]);
            exit;
        }
        if ($after && $after['decks'] > 0) {
            $ratio = $after['noLeader'] / $after['decks'];
            if ($ratio > 0.25) {
                $warnings[] = sprintf('%d of %d decks (%.0f%%) have no leader — unusually high.',
                    $after['noLeader'], $after['decks'], $ratio * 100);
            }
        }

        echo json_encode([
            'success' => true,
            'warnings' => $warnings,
            'oldId' => $id,
            'newId' => $newId,
            'meleeId' => $meleeId,
            'deleted' => $deleted,
            'after' => $after,
            'audit' => $audit,
            'stray' => $stray !== '' ? mb_substr(strip_tags($stray), 0, 400) : null,
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => "Unknown action '$action'."]);
    exit;

    } catch (MatrixQueryError $e) {
        echo json_encode(['success' => false, 'message' => 'SQL error: ' . $e->getMessage()]);
        exit;
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'where' => basename($e->getFile()) . ':' . $e->getLine(),
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SWUDeck Matrix — Tournament Re-fetch</title>
  <style>
    body { background:#1e1e1e; color:#d4d4d4; font-family:monospace; font-size:13px; padding:24px; margin:0; }
    h2 { color:#9cdcfe; margin:0 0 8px; }
    .sub { color:#888; margin:0 0 18px; }
    .warn { background:#3a2a12; border-left:3px solid #d19a1e; padding:10px 12px; margin:0 0 18px; color:#e6c98a; }
    button { background:#0e639c; color:#fff; border:0; padding:6px 12px; font-family:monospace; font-size:12px; cursor:pointer; }
    button:hover { background:#1177bb; }
    button:disabled { background:#444; color:#888; cursor:default; }
    button.danger { background:#8b2f2f; }
    button.danger:hover { background:#a83a3a; }
    .bar { margin-bottom:14px; display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    table { border-collapse:collapse; width:100%; }
    th, td { padding:5px 8px; text-align:left; border-bottom:1px solid #333; white-space:nowrap; }
    th { color:#9cdcfe; font-weight:bold; }
    td.name { white-space:normal; max-width:340px; color:#ccc; }
    .num { text-align:right; }
    .ok { color:#4ec94e; }
    .bad { color:#e74c3c; }
    .warnc { color:#d19a1e; }
    .muted { color:#777; }
    .spin { color:#9cdcfe; }
    #log { margin-top:18px; background:#111; padding:10px; max-height:260px; overflow:auto; white-space:pre-wrap; font-size:11px; color:#aaa; }
  </style>
</head>
<body>
<h2>SWUDeck Matrix — Tournament Re-fetch</h2>
<p class="sub">Re-imports melee tournament data. Each tournament is deleted and re-fetched from melee.gg; the largest event in the archive takes several minutes because the parser makes one request per decklist.</p>

<div class="warn">
  <strong>Re-fetching re-runs the current parser.</strong> The <em>inconsistent</em> column below counts
  matchup pairings whose two recordings contradict each other &mdash; only possible via a win/loss
  attribution error. Re-fetching does not fix those; it reproduces them. Fix the parser first if that
  is what you are chasing.
</div>

<div class="bar">
  <button id="reload">Reload list</button>
  <button id="audit">Run consistency audit</button>
  <button id="runAll">Re-fetch ALL</button>
  <button id="runSelected">Re-fetch selected</button>
  <button id="delSelected" class="danger">Delete selected</button>
  <button id="stop" disabled>Stop</button>
  <span id="status" class="muted"></span>
</div>

<table id="grid">
  <thead>
    <tr>
      <th><input type="checkbox" id="all"></th>
      <th>id</th><th>melee</th><th class="name">name</th>
      <th class="num">decks</th><th class="num">no&nbsp;leader</th><th class="num">matchups</th>
      <th class="num">pairings</th><th class="num">inconsistent</th>
      <th>state</th>
    </tr>
  </thead>
  <tbody><tr><td colspan="10" class="muted">loading…</td></tr></tbody>
</table>

<div id="log"></div>

<script src="/TCGEngine/Core/StyledDialog.js"></script>
<script>
const tbody  = document.querySelector('#grid tbody');
const statusEl = document.getElementById('status');
const logEl  = document.getElementById('log');
let rows = [];
let audited = false;
let stopRequested = false;
let running = false;

function log(msg) {
  logEl.textContent += msg + '\n';
  logEl.scrollTop = logEl.scrollHeight;
}

function setBusy(b) {
  running = b;
  document.getElementById('runAll').disabled = b;
  document.getElementById('runSelected').disabled = b;
  document.getElementById('delSelected').disabled = b;
  document.getElementById('reload').disabled = b;
  document.getElementById('stop').disabled = !b;
}

function render() {
  if (rows.length === 0) {
    tbody.innerHTML = '<tr><td colspan="10" class="muted">no tournaments</td></tr>';
    return;
  }
  tbody.innerHTML = '';
  rows.forEach(r => {
    const a = r.audit || { pairings: 0, inconsistent: 0, pct: 0 };
    const incClass = a.inconsistent > 0 ? 'bad' : 'ok';
    const tr = document.createElement('tr');
    tr.id = 'row-' + r.tournamentID;
    tr.innerHTML =
      `<td><input type="checkbox" class="pick" value="${r.tournamentID}"></td>` +
      `<td>${r.tournamentID}</td>` +
      `<td><a href="https://melee.gg/Tournament/View/${r.tournamentLink}" target="_blank" style="color:#9cdcfe">${r.tournamentLink}</a></td>` +
      `<td class="name">${r.tournamentName ? r.tournamentName.replace(/[<>&]/g, '') : ''}</td>` +
      `<td class="num">${r.decks}</td>` +
      `<td class="num ${r.noLeader > 0 ? 'warnc' : 'muted'}">${r.noLeader}</td>` +
      `<td class="num">${r.matchups}</td>` +
      `<td class="num muted">${audited ? a.pairings : '—'}</td>` +
      `<td class="num ${audited ? incClass : 'muted'}">${audited ? a.inconsistent + (a.pairings ? ' (' + a.pct + '%)' : '') : '—'}</td>` +
      `<td class="state muted">—</td>`;
    tbody.appendChild(tr);
  });
}

function setState(id, text, cls) {
  const tr = document.getElementById('row-' + id);
  if (!tr) return;
  const cell = tr.querySelector('.state');
  cell.textContent = text;
  cell.className = 'state ' + (cls || 'muted');
}

async function loadList(withAudit) {
  statusEl.textContent = withAudit ? 'running audit (heavy — may take a while)…' : 'loading…';
  document.getElementById('audit').disabled = true;
  try {
    const res = await fetch('zzSWUDeckMatrix.php?action=list' + (withAudit ? '&audit=1' : ''));
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch (e) {
      statusEl.textContent = 'server returned non-JSON (see log)';
      log('load failed, raw response: ' + text.slice(0, 500));
      return;
    }
    if (!data.success) {
      statusEl.textContent = data.message || 'failed to load';
      log('load failed: ' + (data.message || '') + (data.where ? ' @ ' + data.where : ''));
      return;
    }
    rows = data.tournaments;
    audited = !!data.audited;
    render();
    if (audited) {
      const totalInc = rows.reduce((s, r) => s + (r.audit ? r.audit.inconsistent : 0), 0);
      const totalPair = rows.reduce((s, r) => s + (r.audit ? r.audit.pairings : 0), 0);
      statusEl.textContent = `${rows.length} tournaments · ${totalInc} inconsistent pairings of ${totalPair}` +
        (totalPair ? ` (${(100 * totalInc / totalPair).toFixed(1)}%)` : '');
    } else {
      statusEl.textContent = `${rows.length} tournaments`;
    }
  } finally {
    document.getElementById('audit').disabled = false;
  }
}

async function refetch(id) {
  setState(id, 'fetching…', 'spin');
  const started = Date.now();
  try {
    const res = await fetch('zzSWUDeckMatrix.php?action=refetch&id=' + id);
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch (e) { setState(id, 'bad response', 'bad'); log(`[${id}] non-JSON response: ` + text.slice(0, 300)); return false; }

    const secs = ((Date.now() - started) / 1000).toFixed(0);
    if (!data.success) {
      setState(id, 'FAILED', 'bad');
      log(`[${id}] FAILED after ${secs}s — ${data.message}` + (data.stray ? ` | stray output: ${data.stray}` : ''));
      return false;
    }
    const a = data.audit || {};
    const warns = data.warnings || [];
    setState(id, `ok → id ${data.newId} (${secs}s)` + (warns.length ? ' ⚠' : ''), warns.length ? 'warnc' : 'ok');
    log(`[${id}] re-fetched in ${secs}s: removed ${data.deleted.decks} decks / ${data.deleted.matchups} matchups; ` +
        `new id ${data.newId} with ${data.after ? data.after.decks : '?'} decks, ` +
        `${a.inconsistent}/${a.pairings} inconsistent pairings (${a.pct}%)` +
        (data.stray ? ` | stray output: ${data.stray}` : ''));
    warns.forEach(w => log(`[${id}] WARNING: ${w}`));
    return true;
  } catch (e) {
    setState(id, 'error', 'bad');
    log(`[${id}] error: ${e.message}`);
    return false;
  }
}

async function deleteOne(id) {
  setState(id, 'deleting…', 'spin');
  try {
    const res = await fetch('zzSWUDeckMatrix.php?action=delete&id=' + id);
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch (e) { setState(id, 'bad response', 'bad'); log(`[${id}] non-JSON: ` + text.slice(0, 300)); return false; }
    if (!data.success) { setState(id, 'FAILED', 'bad'); log(`[${id}] delete failed — ${data.message}`); return false; }
    setState(id, 'deleted', 'warnc');
    log(`[${id}] deleted: ${data.deleted.decks} decks, ${data.deleted.matchups} matchups, ` +
        `${data.deleted.tournament} tournament row (melee ${data.meleeId})`);
    return true;
  } catch (e) { setState(id, 'error', 'bad'); log(`[${id}] error: ${e.message}`); return false; }
}

async function deleteSequential(ids) {
  if (ids.length === 0) { statusEl.textContent = 'nothing selected'; return; }
  const ok = await StyledConfirm(
    `DELETE ${ids.length} tournament(s)? Decks, matchups and the tournament rows are removed. ` +
    `Nothing is re-imported — you will need to import them again yourself.`,
    { title: 'Delete tournaments', danger: true, confirmLabel: 'Delete' });
  if (!ok) return;
  setBusy(true);
  let done = 0, failed = 0;
  for (const id of ids) {
    if (stopRequested) { log('stopped by user'); break; }
    statusEl.textContent = `deleting ${done + 1}/${ids.length} (id ${id})…`;
    if (!(await deleteOne(id))) failed++;
    done++;
  }
  setBusy(false);
  statusEl.textContent = `deleted ${done - failed} of ${ids.length}` + (failed ? `, ${failed} failed` : '');
  log(`--- delete finished: ${done - failed} removed, ${failed} failed ---`);
  await loadList(audited);
}

async function runSequential(ids) {
  if (ids.length === 0) { statusEl.textContent = 'nothing selected'; return; }
  const ok = await StyledConfirm(
    `Re-fetch ${ids.length} tournament(s)? Existing decks and matchups for each are deleted first.`,
    { title: 'Re-fetch tournaments', danger: true, confirmLabel: 'Re-fetch' });
  if (!ok) return;
  stopRequested = false;
  setBusy(true);
  let done = 0, failed = 0;
  for (const id of ids) {
    if (stopRequested) { log('stopped by user'); break; }
    statusEl.textContent = `re-fetching ${done + 1}/${ids.length} (id ${id})…`;
    const ok = await refetch(id);
    done++;
    if (!ok) failed++;
  }
  setBusy(false);
  statusEl.textContent = `finished: ${done} processed, ${failed} failed`;
  log(`--- finished: ${done} processed, ${failed} failed ---`);
  await loadList(audited);
}

document.getElementById('reload').addEventListener('click', () => loadList(false));
document.getElementById('audit').addEventListener('click', () => loadList(true));
document.getElementById('stop').addEventListener('click', () => {
  stopRequested = true;
  statusEl.textContent = 'stopping after current tournament…';
});
document.getElementById('all').addEventListener('change', e => {
  document.querySelectorAll('.pick').forEach(c => { c.checked = e.target.checked; });
});
document.getElementById('runAll').addEventListener('click', () => {
  runSequential(rows.map(r => r.tournamentID));
});
document.getElementById('delSelected').addEventListener('click', () => {
  deleteSequential([...document.querySelectorAll('.pick:checked')].map(c => parseInt(c.value, 10)));
});
document.getElementById('runSelected').addEventListener('click', () => {
  runSequential([...document.querySelectorAll('.pick:checked')].map(c => parseInt(c.value, 10)));
});

window.addEventListener('beforeunload', e => { if (running) { e.preventDefault(); e.returnValue = ''; } });

loadList(false);
</script>
</body>
</html>
