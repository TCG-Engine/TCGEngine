<?php
// SWU-PGN shadow mode (what the schema runner does under SWUPGN_SHADOW=1): a direct board write is caught as an
// announcement gap against its card, the fold still matches the live board, and an announced play leaves no gap.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_shadow.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation')) { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation')) { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions', 'Cards', 'CommonSetup', 'GameStateBuilder', 'GameTestAdapter'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
global $gameName;
$out = sys_get_temp_dir() . '/swupgn-shadow-selftest-' . getmypid() . '.jsonl';
putenv("SWUPGN_SHADOW_OUT=$out");
$lines = fn() => array_map(fn($l) => json_decode($l, true), is_file($out) ? file($out, FILE_IGNORE_NEW_LINES) : []);
$game = function (string $tag, string $seatOrder = '') {
    global $gameName;
    $gameName = 'swupgnshadow_' . $tag . '_' . getmypid();
    @mkdir('./SWUSim/Games/' . $gameName, 0777, true);
    $b = new GameStateBuilder();
    CommonSetup($b, 'grw', 'brk', ['resourceCount' => 4], ['resourceCount' => 4]);
    $b->WithActivePlayer(1)->WithCardInHandForPlayer(1, 'SOR_095')->WithGroundUnitForPlayer(2, 'SOR_046');
    if ($seatOrder !== '') $b->WithSeatOrder($seatOrder)->WithLiveSeats($seatOrder);
    foreach (['SOR_101', 'SOR_102'] as $c) { $b->WithCardInDeckForPlayer(1, $c); $b->WithCardInDeckForPlayer(2, $c); }
    $a = new GameTestAdapter(); $a->loadState($b);
    return $a;
};

// A direct board write (no announcement): 2 damage straight onto P2's unit.
$a = $game('gap');
SwuPgnShadowBegin('selftest::DirectWrite');
SwuPgnShadowAction(function () { $u = &GetGroundArena(2); $u[0]->Damage = intval($u[0]->Damage) + 2; });
SwuPgnShadowEnd();
$l = $lines()[0] ?? [];
SwuPgnTestEq([$l['test'] ?? null, $l['actions'] ?? null, array_key_exists('error', $l) ? $l['error'] : 'absent'], ['selftest::DirectWrite', 1, null], 'one report line per test');
SwuPgnTestEq($l['gapCards'] ?? null, ['SOR_046' => 1], 'the direct write is a gap against its card');
SwuPgnTestCheck(($l['mismatchCount'] ?? null) === 0 && ($l['recorderErrors'] ?? null) === 0, 'and the fold still matches the live board', json_encode($l['mismatches'] ?? []));
SwuPgnTestCheck(!is_dir(SwuPgnRecDir($gameName)), 'the shadow recording is removed afterwards');

// An announced play: no gap.
$a = $game('play');
SwuPgnShadowBegin('selftest::Play');
SwuPgnShadowAction(fn() => $a->playCardFromHand(1, 0));
SwuPgnShadowEnd();
$l = $lines()[1] ?? [];
SwuPgnTestEq([$l['gaps'] ?? null, $l['mismatchCount'] ?? null, $l['actions'] ?? null], [0, 0, 1], 'an announced play: no gap, no mismatch');
SwuPgnTestCheck(($l['recorderMs'] ?? -1) >= 0, 'the recorder\'s own time is reported', strval($l['recorderMs'] ?? ''));

// A fold that does NOT match is reported: drop the play's records (keep only the opening keyframe), as a recorder that
// silently lost them would.
$a = $game('mismatch');
SwuPgnShadowBegin('selftest::Mismatch');
SwuPgnShadowAction(fn() => $a->playCardFromHand(1, 0));
SwuPgnEventsTruncate($gameName, 1);
SwuPgnShadowEnd();
$l = $lines()[2] ?? [];
SwuPgnTestCheck(($l['mismatchCount'] ?? 0) > 0, 'a fold that disagrees with the live board is reported', json_encode($l['mismatches'] ?? []));

// A test game that ENDS: the game's own finish checks files built from a recording that began mid-game (no DECKS, no
// opening keyframe), which cannot check clean — reported apart, not as a recorder throw, and off the real health log.
$healthBefore = is_file('./SWUSim/Games/swupgn-health.jsonl') ? filesize('./SWUSim/Games/swupgn-health.jsonl') : 0;
$a = $game('end');
SwuPgnShadowBegin('selftest::GameEnds');
// A round boundary the recording crossed: its keyframe, which the unseeded recording's own fold cannot match.
SwuPgnEventsAppend($gameName, [_SwuPgnShadowKeyframeEvent('R1.A.0z', 'ROUND_END', 1)]);
SwuPgnShadowAction(fn() => SWUDeclareGameWinner(1));
SwuPgnShadowEnd();
$l = $lines()[3] ?? [];
SwuPgnTestEq([$l['test'] ?? null, $l['recorderErrors'] ?? null, $l['finishCheckFailures'] ?? null], ['selftest::GameEnds', 0, 1],
    'a game that ends: its finish check is reported apart, not as a throw');
SwuPgnTestEq(is_file('./SWUSim/Games/swupgn-health.jsonl') ? filesize('./SWUSim/Games/swupgn-health.jsonl') : 0, $healthBefore, 'and the real health log is untouched');

// A 3-4 seat game is never recorded (the writer is two-seat PvP only): it is reported as skipped, nothing more.
$a = $game('multiseat', '1,2,3');
SwuPgnShadowBegin('selftest::ThreeSeat');
SwuPgnShadowAction(fn() => $a->playCardFromHand(1, 0));
SwuPgnShadowEnd();
$l = $lines()[4] ?? [];
SwuPgnTestEq([$l['test'] ?? null, $l['skipped'] ?? null, $l['mismatchCount'] ?? null, $l['validateErrors'] ?? null, is_dir(SwuPgnRecDir($gameName))],
    ['selftest::ThreeSeat', 'multi-seat', null, null, false], 'a 3-seat game is skipped, not recorded');

foreach (glob('./SWUSim/Games/swupgnshadow_*_' . getmypid()) as $d) exec('rm -rf ' . escapeshellarg($d));
@unlink($out);
SwuPgnTestFinish();
