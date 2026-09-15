<?php
// Regression guard: a player on a base that forbids the mulligan (JTL_028 Nabat Village) must still spawn with an
// INTERACTIVE decision at the front of their queue. QueuePregameSetup (SWUSim/CreateGame.php) queues each seat's
// mulligan YESNO (block 10) and then a static PushPregameSnapshot + ChooseStartingResource (block 50). Nabat Village
// skips the mulligan, so the static became the FRONT decision — and GetNextTurn renders the raw queue without running
// statics, so nothing ever processed it: the game stalled in setup. FOUND 2026-09-15 by the bot fixture smoke
// (normal_armorer_nabat in seat 1: "stalled after 20 consecutive no-op steps", gap "CUSTOM PushPregameSnapshot|1").
// With Nabat in seat 2 it hid: seat 1's actions drained seat 2's statics (ProcessGoldfishAutomation).
// Sibling of DevTools/tdd-regression/test_mulligan_reprompt.php (the same "no static in front" rule).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_nabat_pregame_front_decision.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth','HTTPLibraries','NetworkingLibraries'] as $f) @include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/MatchFlow.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$mk = function(array $spec){ $o=[]; foreach($spec as $id=>$n){ for($i=0;$i<$n;$i++) $o[]=$id; } return $o; };
$main = $mk(['SOR_095'=>8,'SOR_046'=>8,'JTL_039'=>8,'SOR_100'=>8,'SOR_101'=>8,'SOR_102'=>10]);  // 50
$nabat  = ['success'=>true,'leader'=>'JTL_002','base'=>'JTL_028','mainDeck'=>$main,'sideboard'=>[]];   // Nabat Village
$normal = ['success'=>true,'leader'=>'JTL_002','base'=>'SEC_026','mainDeck'=>$main,'sideboard'=>[]];

$front = function($p){ foreach (GetDecisionQueue($p) as $d) { if ($d !== null && empty($d->removed)) return $d; } return null; };
$desc = fn($d) => $d ? $d->Type . ':' . $d->Param : 'none';
$spawn = function (array $deck1, array $deck2) {
    $matchId = MatchCreate('SWUSim', 'premier', 'bo3', [1 => ['originalDeck' => $deck1, 'authKey' => 'k1'], 2 => ['originalDeck' => $deck2, 'authKey' => 'k2']]);
    MatchBeginSideboarding('SWUSim', $matchId, 1);
    MatchSubmitSideboardDeck('SWUSim', $matchId, 1, $deck1);
    MatchSubmitSideboardDeck('SWUSim', $matchId, 2, $deck2);
    $next = (string)MatchMaybeSpawnAfterSideboard('SWUSim', $matchId);
    global $gameName; $gameName = $next;
    return $next;
};
$interactive = fn($d) => $d !== null && in_array($d->Type, ['YESNO', 'MZCHOOSE', 'MZMAYCHOOSE', 'MZMULTICHOOSE', 'OPTIONCHOOSE'], true);

// Nabat Village in seat 1 (no mulligan): the front decision is the starting-resource pick, never a static.
$g = $spawn($nabat, $normal);
$check($g !== '', "pregame spawned ($g)");
$check($interactive($front(1)) && $front(1)->Type === 'MZMULTICHOOSE', 'P1 (Nabat, no mulligan) front decision is the resource pick — got ' . $desc($front(1)));
$check($front(2) !== null && $front(2)->Type === 'YESNO' && strpos((string)$front(2)->Param, 'mulligan') !== false, 'P2 front decision is still its mulligan — got ' . $desc($front(2)));
@array_map('unlink', glob('./SWUSim/Games/' . $g . '/*') ?: []); @rmdir('./SWUSim/Games/' . $g);

// Nabat Village in seat 2: likewise.
$g = $spawn($normal, $nabat);
$check($interactive($front(2)) && $front(2)->Type === 'MZMULTICHOOSE', 'P2 (Nabat) front decision is the resource pick — got ' . $desc($front(2)));
$check($front(1) !== null && $front(1)->Type === 'YESNO', 'P1 front decision is its mulligan — got ' . $desc($front(1)));
@array_map('unlink', glob('./SWUSim/Games/' . $g . '/*') ?: []); @rmdir('./SWUSim/Games/' . $g);

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
