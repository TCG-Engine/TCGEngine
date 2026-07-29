<?php
// Regression guard: in a 2-player game the mulligan YESNO must be the FRONT decision of each player's queue
// right after the game spawns. GetNextTurn renders the raw queue without running static decisions, and a
// mode-100 answer pops the FRONT decision — so if a static (e.g. a pregame-snapshot) sat ahead of the
// YESNO, the player's answer would pop the static and leave the YESNO to re-prompt (the reported bug).
// Also checks that answering advances cleanly to the starting-resource choice (no re-prompt).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_mulligan_reprompt.php
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
$deck = ['success'=>true,'leader'=>'JTL_002','base'=>'SEC_026','mainDeck'=>$main,'sideboard'=>[]];

$matchId = MatchCreate('SWUSim', 'premier', 'bo3', [
    1 => ['originalDeck' => $deck, 'authKey' => 'k1'],
    2 => ['originalDeck' => $deck, 'authKey' => 'k2'],
]);
MatchBeginSideboarding('SWUSim', $matchId, 1);
MatchSubmitSideboardDeck('SWUSim', $matchId, 1, $deck);
MatchSubmitSideboardDeck('SWUSim', $matchId, 2, $deck);
$next = (string)MatchMaybeSpawnAfterSideboard('SWUSim', $matchId);
global $gameName; $gameName = $next;
$check($next !== '', "pregame spawned ($next)");

// The FRONT non-removed decision of each seat's queue — exactly what GetNextTurn renders + mode-100 pops.
$front = function($p){ foreach (GetDecisionQueue($p) as $d) { if ($d !== null && empty($d->removed)) return $d; } return null; };
$isMulligan = function($d){ return $d !== null && $d->Type === 'YESNO' && strpos((string)$d->Param, 'mulligan') !== false; };

// No manual DQ processing — assert the RAW spawned queue (what the client receives).
$check($isMulligan($front(1)), 'P1 front decision is the mulligan YESNO right after spawn — got ' . ($front(1) ? $front(1)->Type.':'.$front(1)->Param : 'none'));
$check($isMulligan($front(2)), 'P2 front decision is the mulligan YESNO right after spawn — got ' . ($front(2) ? $front(2)->Type.':'.$front(2)->Param : 'none'));

// P1 answers first (mode-100 = PopDecision(front) + ExecuteStaticMethods). Must NOT re-prompt the mulligan.
$dq = new DecisionQueueController();
ob_start(); $dq->PopDecision(1); $dq->ExecuteStaticMethods(1, 'NO'); ob_end_clean();
$check(!$isMulligan($front(1)), 'after answering, P1 is NOT re-prompted the mulligan — got ' . ($front(1) ? $front(1)->Type.':'.$front(1)->Param : 'none'));
// P2 (second responder) is likewise still cleanly presented the mulligan at its front.
$check($isMulligan($front(2)), 'P2 (second responder) still has the mulligan at front (not popped by P1 action)');

@array_map('unlink', glob('./SWUSim/Games/' . $next . '/*') ?: []); @rmdir('./SWUSim/Games/' . $next);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
