<?php
// Repro/guard: a between-games sideboard swap must reach the spawned game's deck. Drives the REAL SWUSim
// match flow (MatchSubmitSideboardDeck -> MatchMaybeSpawnAfterSideboard -> SWUSetupGame) with a deck that
// swaps 2x ASH_050 Morgan Elsbeth OUT for 2x SEC_186 Garindan IN, then inspects the spawned game's deck.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_sideboard_swap_reaches_game.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth','HTTPLibraries','NetworkingLibraries'] as $f) @include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/MatchFlow.php';   // registers SWUSim Match hooks (resolveLobbyDecks / setupGame / ...)

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Original 51-card main deck (includes 2x ASH_050 Morgan Elsbeth) + a 10-card sideboard (incl. 2x SEC_186).
$mk = function(array $spec){ $o=[]; foreach($spec as $id=>$n){ for($i=0;$i<$n;$i++) $o[]=$id; } return $o; };
$origMain = $mk(['JTL_039'=>3,'ASH_194'=>2,'JTL_060'=>3,'SEC_195'=>2,'ASH_247'=>3,'LAW_217'=>2,'LOF_059'=>3,'ASH_052'=>2,
  'JTL_221'=>3,'LAW_118'=>3,'JTL_043'=>3,'ASH_192'=>2,'ASH_048'=>3,'JTL_032'=>3,'JTL_033'=>3,'LOF_213'=>3,'ASH_193'=>3,'ASH_195'=>3,'ASH_050'=>2]);
$origSide = $mk(['LAW_133'=>2,'SEC_078'=>2,'SEC_179'=>2,'SEC_193'=>2,'SEC_186'=>2]);
$origDeck = ['success'=>true,'leader'=>'JTL_002','base'=>'SEC_026','mainDeck'=>$origMain,'sideboard'=>$origSide];

// Sideboarded deck: ASH_050 -> sideboard, SEC_186 -> main (the swap the user made).
$swapMain = $mk(['JTL_039'=>3,'ASH_194'=>2,'JTL_060'=>3,'SEC_195'=>2,'ASH_247'=>3,'LAW_217'=>2,'LOF_059'=>3,'ASH_052'=>2,
  'JTL_221'=>3,'LAW_118'=>3,'JTL_043'=>3,'ASH_192'=>2,'ASH_048'=>3,'JTL_032'=>3,'JTL_033'=>3,'LOF_213'=>3,'ASH_193'=>3,'ASH_195'=>3,'SEC_186'=>2]);
$swapSide = $mk(['LAW_133'=>2,'SEC_078'=>2,'SEC_179'=>2,'SEC_193'=>2,'ASH_050'=>2]);
$swapDeck = ['success'=>true,'leader'=>'JTL_002','base'=>'SEC_026','mainDeck'=>$swapMain,'sideboard'=>$swapSide];

$matchId = MatchCreate('SWUSim', 'premier', 'bo3', [
    1 => ['originalDeck' => $origDeck, 'authKey' => 'k1'],
    2 => ['originalDeck' => $origDeck, 'authKey' => 'k2'],
]);
$check(is_string($matchId) && $matchId !== '', "match created ($matchId)");

MatchBeginSideboarding('SWUSim', $matchId, 1);
MatchSubmitSideboardDeck('SWUSim', $matchId, 1, $swapDeck);   // P1 swaps Elsbeth -> Garindan
MatchSubmitSideboardDeck('SWUSim', $matchId, 2, $origDeck);   // P2 keeps
$m = MatchRead('SWUSim', $matchId);
$check(MatchSideboardBothReady($m), 'both seats ready');

$next = MatchMaybeSpawnAfterSideboard('SWUSim', $matchId);   // spawns the game via SWUSetupGame (in-memory too)
$next = (string)$next;
$check($next !== '', "next game spawned ($next)");

// After the spawn, the in-memory gamestate IS the spawned game. P1's cards = deck + opening hand = the
// full 51-card sideboarded main deck.
global $gameName; $gameName = $next;
$p1 = [];
foreach (array_merge(GetDeck(1), GetHand(1)) as $c) { if (empty($c->removed)) { $id=$c->CardID; $p1[$id]=($p1[$id]??0)+1; } }
$total = array_sum($p1);
$check($total === 51, "P1 has all 51 main-deck cards (deck+hand), got $total");
$check(($p1['ASH_050'] ?? 0) === 0, 'ASH_050 Morgan Elsbeth is GONE from P1 game deck (swapped out) — got ' . ($p1['ASH_050'] ?? 0));
$check(($p1['SEC_186'] ?? 0) === 2, 'SEC_186 Garindan is IN P1 game deck (swapped in) — got ' . ($p1['SEC_186'] ?? 0));

// cleanup spawned game dir + match file (best-effort)
@array_map('unlink', glob('./SWUSim/Games/' . $next . '/*') ?: []); @rmdir('./SWUSim/Games/' . $next);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
