<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 php DevTools/tdd-regression/test_swu_stats_ingress.php
//
// Stats ingress translates every incoming card identifier to SET_NNN before anything is written.
// Karabast sends FFG UIDs by contract and the tables are SET_NNN-keyed, so without this each
// submission re-fragments the key space the migration just merged.
//
// READ-ONLY: this exercises the normaliser directly on payload arrays. It never POSTs, so it cannot
// pollute the prod-clone stats tables — two other tests in this directory do POST, which is how 21
// junk rows accumulated in completedgame.
//
// Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §2
header('Content-Type: text/plain');
require_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../../AppCore/SWU/StatsIngress.php';

$checks = [];
$LUKE = UUIDLookup('SOR_005');    // a real leader's FFG UID
$BASE = UUIDLookup('SOR_029');
$CARD = UUIDLookup('SOR_033');

$checks['fixture: UUIDs resolved'] = $LUKE && $BASE && $CARD;

function payload($over = []) {
    global $LUKE, $BASE, $CARD;
    return array_merge([
        'gameName' => '999', 'winHero' => $LUKE, 'loseHero' => $LUKE,
        'player1' => json_encode(['leader' => $LUKE, 'base' => $BASE,
            'cardResults' => [['cardId' => $CARD, 'timesIncluded' => 3]]]),
        'player2' => json_encode(['leader' => $LUKE, 'base' => 'Green', 'cardResults' => []]),
    ], $over);
}

// ── The happy path: UUIDs in, SET_NNN out, nothing skipped ───────────────────
$d = payload();
$r = SWUStatsIngressNormalize($d);
$p1 = json_decode($d['player1'], true);
$p2 = json_decode($d['player2'], true);

$checks['winHero -> SET_NNN']        = $d['winHero'] === 'SOR_005';
$checks['leader -> SET_NNN']         = $p1['leader'] === 'SOR_005';
$checks['base -> SET_NNN']           = $p1['base'] === 'SOR_029';
$checks['cardId -> SET_NNN']         = $p1['cardResults'][0]['cardId'] === 'SOR_033';
$checks['non-id card fields survive']= $p1['cardResults'][0]['timesIncluded'] === 3;
$checks['nothing skipped']           = !$r['skipCompletedGame'] && !$r['skipPlayer'][1] && !$r['skipPlayer'][2];
$checks['player stays a JSON string']= is_string($d['player1']);

// ── Class 2: a base COLOUR is legitimate data and must survive verbatim ──────
$checks['base colour preserved verbatim'] = $p2['base'] === 'Green';

// ── Either shape accepted: a client already sending SET_NNN keeps working ────
$d = payload(['winHero' => 'SOR_005',
    'player1' => json_encode(['leader' => 'SOR_005', 'base' => 'SOR_029',
        'cardResults' => [['cardId' => 'SOR_033']]])]);
$r = SWUStatsIngressNormalize($d);
$p1 = json_decode($d['player1'], true);
$checks['SET_NNN input passes through'] = $p1['leader'] === 'SOR_005' && $p1['cardResults'][0]['cardId'] === 'SOR_033';
$checks['SET_NNN input skips nothing']  = !$r['skipPlayer'][1];

// ── Reprints fold to their canonical printing, so rows aggregate ─────────────
$d = payload(['player1' => json_encode(['leader' => $LUKE, 'base' => $BASE,
    'cardResults' => [['cardId' => 'SHD_030']]])]);
SWUStatsIngressNormalize($d);
$p1 = json_decode($d['player1'], true);
$checks['reprint folds (SHD_030 -> SOR_033)'] = $p1['cardResults'][0]['cardId'] === 'SOR_033';

// ── Class 3 granularity: ONE bad card drops only that card ──────────────────
$d = payload(['player1' => json_encode(['leader' => $LUKE, 'base' => $BASE,
    'cardResults' => [['cardId' => 'zzzzzzz001'], ['cardId' => $CARD]]])]);
$r = SWUStatsIngressNormalize($d);
$p1 = json_decode($d['player1'], true);
$checks['bad card is dropped']            = count($p1['cardResults']) === 1;
$checks['the good card survives']         = $p1['cardResults'][0]['cardId'] === 'SOR_033';
$checks['dropping a card does NOT skip the player'] = $r['skipPlayer'][1] === false;
$checks['the drop is counted']            = $r['droppedCards'] === 1;

// ── Class 3 granularity: a bad LEADER skips that player, not the other ──────
// leaderID is a PRIMARY KEY component, so a partial row cannot be keyed at all.
$d = payload(['player1' => json_encode(['leader' => 'zzzzzzz001', 'base' => $BASE, 'cardResults' => []])]);
$r = SWUStatsIngressNormalize($d);
$checks['bad leader skips that player']   = $r['skipPlayer'][1] === true;
$checks['...but not the other player']    = $r['skipPlayer'][2] === false;
$checks['...and not the completedgame row'] = $r['skipCompletedGame'] === false;

// ── Class 3: a bad hero skips the completedgame row ─────────────────────────
// WinningHero/LosingHero is exactly where the unreadable history accumulated.
$d = payload(['winHero' => 'zzzzzzz001']);
$r = SWUStatsIngressNormalize($d);
$checks['bad hero skips completedgame']   = $r['skipCompletedGame'] === true;
$checks['bad hero is NOT written verbatim'] = $d['winHero'] === 'zzzzzzz001';  // untouched, but skipped

// ── A blank identifier is class 3, not a silent empty-string write ──────────
$d = payload(['winHero' => '']);
$r = SWUStatsIngressNormalize($d);
$checks['blank hero skips completedgame'] = $r['skipCompletedGame'] === true;

// ── The Palpatine asset hash resolves rather than being dropped ─────────────
$d = payload(['winHero' => 'ad86d54e97']);
$r = SWUStatsIngressNormalize($d);
$checks['leader-unit asset hash resolves'] = $d['winHero'] === 'TWI_017' && !$r['skipCompletedGame'];

// ── Tokens: Karabast submits them, and they must now resolve ────────────────
$d = payload(['player1' => json_encode(['leader' => $LUKE, 'base' => $BASE,
    'cardResults' => [['cardId' => '8752877738']]])]);   // Shield token
$r = SWUStatsIngressNormalize($d);
$p1 = json_decode($d['player1'], true);
$checks['token UUID resolves (SOR_T02)'] = ($p1['cardResults'][0]['cardId'] ?? '') === 'SOR_T02';
$checks['token is not dropped']          = $r['droppedCards'] === 0;

// ── An array payload (internal callers) round-trips as an array ─────────────
$d = payload(['player1' => ['leader' => $LUKE, 'base' => $BASE, 'cardResults' => []]]);
SWUStatsIngressNormalize($d);
$checks['array payload stays an array'] = is_array($d['player1']) && $d['player1']['leader'] === 'SOR_005';

// ── Manual submissions: same shapes accepted, but unresolvable is REJECTED ───
// SubmitManualGameResult writes carddeckstats.cardID, opponentdeckstats.leaderID and
// opponentnamedbasestats.leaderID/baseID — all PK components in re-keyed tables — and normalised
// none of them until 2026-08-06. Note the field is `cardID`, not the engine payload's `cardId`.
function manualPayload($over = []) {
    global $LUKE, $BASE, $CARD;
    return json_encode(array_merge([
        'opposingHero' => $LUKE,
        'opposingBase' => $BASE,
        'cardResults'  => [['cardID' => $CARD, 'played' => 2, 'resourced' => 1]],
    ], $over));
}

$m = SWUStatsIngressNormalizeManual(manualPayload());
$mp = json_decode($m['player'] ?? '', true);
$checks['manual: accepted']              = $m['ok'] === true;
$checks['manual: hero -> SET_NNN']       = ($mp['opposingHero'] ?? null) === 'SOR_005';
$checks['manual: base -> SET_NNN']       = ($mp['opposingBase'] ?? null) === 'SOR_029';
$checks['manual: cardID -> SET_NNN']     = ($mp['cardResults'][0]['cardID'] ?? null) === 'SOR_033';
$checks['manual: non-id fields survive'] = ($mp['cardResults'][0]['played'] ?? null) === 2;
$checks['manual: returns a JSON string'] = is_string($m['player'] ?? null);

// SET_NNN in, SET_NNN out — Petranaki sends this shape.
$m = SWUStatsIngressNormalizeManual(manualPayload([
    'opposingHero' => 'SOR_005', 'opposingBase' => 'SOR_029',
    'cardResults'  => [['cardID' => 'SOR_033']]]));
$mp = json_decode($m['player'], true);
$checks['manual: SET_NNN passes through'] = $m['ok'] === true
    && $mp['opposingHero'] === 'SOR_005' && $mp['cardResults'][0]['cardID'] === 'SOR_033';

// Reprints still fold, so manual rows aggregate with engine rows.
$m = SWUStatsIngressNormalizeManual(manualPayload(['cardResults' => [['cardID' => 'SHD_030']]]));
$checks['manual: reprint folds'] = json_decode($m['player'], true)['cardResults'][0]['cardID'] === 'SOR_033';

// A base COLOUR is legitimate data and must NOT be rejected.
$m = SWUStatsIngressNormalizeManual(manualPayload(['opposingBase' => 'Green']));
$checks['manual: base colour accepted']  = $m['ok'] === true;
$checks['manual: base colour verbatim']  = json_decode($m['player'], true)['opposingBase'] === 'Green';

// THE REGRESSION THAT MATTERS: unresolvable is rejected outright, not written and not dropped.
$m = SWUStatsIngressNormalizeManual(manualPayload(['opposingHero' => 'zzzzzzz001']));
$checks['manual: bad hero rejected']     = $m['ok'] === false && $m['field'] === 'opposingHero';
$checks['manual: rejection names value'] = ($m['value'] ?? null) === 'zzzzzzz001';
$checks['manual: rejection has no player payload'] = !isset($m['player']);

$m = SWUStatsIngressNormalizeManual(manualPayload(['cardResults' => [['cardID' => 'zzzzzzz001']]]));
$checks['manual: bad card rejected']     = $m['ok'] === false && $m['field'] === 'cardResults[0].cardID';

$m = SWUStatsIngressNormalizeManual(manualPayload(['opposingBase' => 'zzzzzzz001']));
$checks['manual: bad base rejected']     = $m['ok'] === false && $m['field'] === 'opposingBase';

// Tokens resolve here too — Karabast and the UI both surface them.
$m = SWUStatsIngressNormalizeManual(manualPayload(['cardResults' => [['cardID' => '8752877738']]]));
$checks['manual: token resolves'] = $m['ok'] === true
    && json_decode($m['player'], true)['cardResults'][0]['cardID'] === 'SOR_T02';

// The endpoint must actually use the gate, and use it BEFORE the write. SaveDeckStats commits the
// deck's own stats before it ever reads the opponent leader, so a check placed inside it would
// leave half a submission behind — assert on order, not merely on presence.
//
// Comments are stripped first. The require_once line NAMES the function in a trailing comment, so
// a raw substring scan reports the gate as present even when the call has been removed — which it
// did, silently, until this was fixed.
$epRaw = file_get_contents(__DIR__ . '/../../APIs/SubmitManualGameResult.php');
$ep = false;
if ($epRaw !== false) {
    $ep = '';
    foreach (token_get_all($epRaw) as $t) {
        if (is_array($t)) {
            if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) continue;
            $ep .= $t[1];
        } else $ep .= $t;
    }
}
$posGate = $ep === false ? false : strpos($ep, 'SWUStatsIngressNormalizeManual(');
$posSave = $ep === false ? false : strpos($ep, 'SaveDeckStats($deckID');
$checks['endpoint: calls the gate']       = $posGate !== false;
$checks['endpoint: gate precedes write']  = $posGate !== false && $posSave !== false && $posGate < $posSave;
$checks['endpoint: rejects with 400']     = $ep !== false && strpos($ep, 'http_response_code(400)') !== false;

// ── SubmitGameResult is strict too (changed 2026-08-06, breaking, by explicit decision) ──
// The LIBRARY still reports drops at the narrowest granularity — that reporting is what the
// endpoint now refuses on, and it is what makes the 400's `details` specific. So the checks above
// asserting skipPlayer/droppedCards still describe the library correctly; what changed is that the
// endpoint no longer proceeds when any of them is set.
$engRaw = file_get_contents(__DIR__ . '/../../APIs/SubmitGameResult.php');
$eng = false;
if ($engRaw !== false) {
    $eng = '';
    foreach (token_get_all($engRaw) as $t) {
        if (is_array($t)) {
            if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) continue;
            $eng .= $t[1];
        } else $eng .= $t;
    }
}
$posNorm = $eng === false ? false : strpos($eng, 'SWUStatsIngressNormalize($data)');
$posGate2 = $eng === false ? false : strpos($eng, "\$swuIngress['skipCompletedGame']");
$checks['engine: normalizes']            = $posNorm !== false;
$checks['engine: refuses on any skip']   = $posGate2 !== false;
$checks['engine: gate follows normalize']= $posNorm !== false && $posGate2 !== false && $posNorm < $posGate2;
// All four signals must be covered — refusing on only some would let a partial write through.
foreach (["skipCompletedGame", "skipPlayer'][1]", "skipPlayer'][2]", "droppedCards"] as $sig) {
    $checks["engine: gate covers $sig"] = $eng !== false && strpos($eng, $sig) !== false;
}
// The gate must precede every write. SaveDeckStats is the first of them.
$posEngSave = $eng === false ? false : strpos($eng, 'SaveDeckStats(');
$checks['engine: gate precedes write'] = $posGate2 !== false
    && ($posEngSave === false || $posGate2 < $posEngSave);

// The contract change is documented — a consumer must be able to discover it without reading PHP.
$apiDoc = file_get_contents(__DIR__ . '/../../Stats/APIs.php');
$checks['docs: 400 documented'] = $apiDoc !== false
    && strpos($apiDoc, 'Unrecognized Card Identifiers (HTTP 400)') !== false;
$checks['docs: both shapes documented'] = $apiDoc !== false
    && strpos($apiDoc, 'either format') !== false;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
