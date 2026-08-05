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

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
