<?php
// TDD guard: a mulligan must redraw the player's OWN starting hand size, not a hardcoded 6.
//
// Bug report (2026-08-04): "playing a deck with Colossus, I start with 5 cards, but when I mulligan it
// gives me 6." QueuePregameSetup draws max(0, 6 + SWUStartingHandModifier($baseID)) for the OPENING
// hand, but MulliganDecision redrew a flat 6 — so a hand-size base was honoured on the first hand and
// silently discarded on the mulligan.
//
// Drives the REAL MulliganDecision handler (same approach as test_mulligan_determinism.php); the schema
// suite cannot cover this because its Option-B setup bypasses the pregame mulligan queue entirely.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off \
//     DevTools/tdd-regression/test_mulligan_hand_size.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
global $gameName, $playerID, $customDQHandlers;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Mulligan with $baseID seated, holding $openingHand cards; returns the resulting hand size.
$mulliganHandSize = function (string $baseID, int $openingHand) {
    global $gameName, $playerID, $customDQHandlers;
    $gameName = 'mhs_' . getmypid() . '_' . substr(md5($baseID . $openingHand), 0, 6);
    $playerID = 1;
    @mkdir('./Games/' . $gameName, 0777, true);

    $b = new GameStateBuilder();
    // Seat the base via CommonSetup's 'baseCardID' opt, NOT WithBaseForPlayer — the latter APPENDS, so
    // the zone would hold the code's base at index 0 and the override at index 1, and every reader
    // (production included) looks at index 0.
    CommonSetup($b, 'grw', 'brk', $baseID === '' ? [] : ['baseCardID' => $baseID], []);
    $b->WithActivePlayer(1);
    // Opening hand as pregame would have dealt it, plus a deck deep enough for any redraw (max is
    // Nabat Village's 9).
    for ($i = 0; $i < $openingHand; $i++) $b->WithCardInHandForPlayer(1, 'SOR_200');
    for ($i = 0; $i < 20; $i++)           $b->WithCardInDeckForPlayer(1, 'SOR_095');

    $g = new GameTestAdapter(); $g->loadState($b);
    ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
    SetSWUVar('RNG_SEED', 'fixed-seed-for-mulligan-hand-size');

    $mulligan = $customDQHandlers['MulliganDecision'] ?? null;
    if (!is_callable($mulligan)) return -1;
    ob_start(); $mulligan(1, ['1'], 'YES'); ob_end_clean();

    $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; }
    return $n;
};

// ── Baseline: an ordinary base redraws 6 ─────────────────────────────────────
$check($mulliganHandSize('', 6) === 6, 'ordinary base: mulligan redraws 6');

// ── THE BUG: Colossus (JTL_021, -1) opens at 5 and must REDRAW 5, not 6 ──────
$colossus = $mulliganHandSize('JTL_021', 5);
$check($colossus === 5, "Colossus: mulligan redraws 5 (got $colossus)");

// ── The other direction: Nabat Village (JTL_028, +3) opens at 9 ──────────────
// NOTE: JTL_028 also suppresses the mulligan in production (CreateGame skips the YESNO for it), so this
// asserts the handler's arithmetic rather than a reachable in-game flow — it is the guard that stops a
// future fix hardcoding 6 again in the other direction.
$nabat = $mulliganHandSize('JTL_028', 9);
$check($nabat === 9, "Nabat Village: mulligan redraws 9 (got $nabat)");

echo ($fails === 0 ? "PASS (3 checks)\n" : "FAIL: $fails check(s)\n");
