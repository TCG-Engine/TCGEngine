<?php
// SWU-PGN writer keyframes (§11): what the engine's board says, in the terms the fold keeps.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_keyframe.php
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
$gameName = 'swupgnkf_' . getmypid();
@mkdir('./SWUSim/Games/' . $gameName, 0777, true);

$b = new GameStateBuilder();
CommonSetup($b, 'grw', 'brk', ['leaderDeployed' => true, 'resourceCount' => 2], []);
$b->WithActivePlayer(1);
foreach (['SOR_101', 'SOR_102', 'SOR_103'] as $c) $b->WithCardInDeckForPlayer(1, $c);
(new GameTestAdapter())->loadState($b);
SwuPgnRecForceForTest($gameName);
$leaderUnit = null; foreach (GetGroundArena(1) as $u) if (empty($u->removed) && intval($u->UniqueID) === intval(GetLeader(1)[0]->DeployedUniqueID)) $leaderUnit = $u;
$kf = fn() => SwuPgnKeyframe(SwuPgnSnapshot(), SwuPgnStateRead($gameName)['ledger'], ['round' => 1, 'phase' => 'action', 'initiativeTaken' => false]);

// A deployed leader is exhausted when its unit is (the fold tracks the unit's EXHAUST/READY on the leader, §12.2).
$leaderUnit->Status = 0; $l = &GetLeader(1); $l[0]->Ready = true; unset($l);
SwuPgnTestEq($kf()['players']['1']['leader']['exhausted'] ?? null, true, 'a deployed leader whose unit is exhausted reads exhausted');
$leaderUnit->Status = 1; $l = &GetLeader(1); $l[0]->Ready = false; unset($l);
SwuPgnTestEq($kf()['players']['1']['leader']['exhausted'] ?? null, false, 'and ready when its unit is ready, whatever the leader zone\'s flag says');
// Deployed as a Pilot (DeployedUniqueID 0, the leader is a subcard): an upgrade has no ready state, whatever the flag says.
$l = &GetLeader(1); $savedUid = $l[0]->DeployedUniqueID; $l[0]->DeployedUniqueID = 0; $l[0]->Ready = false; unset($l);
$leaderUnit->removed = true;
SwuPgnTestEq([SwuPgnSnapshot()['seats'][1]['leader']['deployedUid'], $kf()['players']['1']['leader']['exhausted'] ?? null], [null, false],
    'a pilot leader has no unit and reads ready');
// A pilot leader ejected to a unit of its own (Eject) keeps DeployedUniqueID 0: its unit is found by card.
$leaderUnit->removed = false; $leaderUnit->Status = 0;
SwuPgnTestEq([SwuPgnSnapshot()['seats'][1]['leader']['deployedUid'], $kf()['players']['1']['leader']['exhausted'] ?? null], [intval($leaderUnit->UniqueID), true],
    'a deployed leader with no unit id is the unit of its card');
$l = &GetLeader(1); $l[0]->DeployedUniqueID = $savedUid; $l[0]->Ready = true; unset($l); $leaderUnit->Status = 1;

// An upgrade played from the OPPONENT's discard onto your unit sits in the ledger under the seat that moved it: a unit's
// upgrades are found whichever seat's list holds them (UniqueIDs are game-wide).
$snap = SwuPgnSnapshot(); $lu = intval($leaderUnit->UniqueID);
$snap['seats'][1]['units'][$lu]['upgrades'][] = ['cardID' => 'SOR_120', 'uid' => null, 'isPilot' => false];
$L = SwuPgnStateRead($gameName)['ledger']; $L['zones'][2]["attached:$lu"] = ['SOR#120:2'];
$kfCards = SwuPgnKeyframe($snap, $L, ['round' => 1, 'phase' => 'action', 'initiativeTaken' => false])['players']['1']['cards'];
$mine = array_values(array_filter($kfCards, fn($c) => $c['id'] === SwuPgnLedgerByUid($L, $lu)))[0] ?? [];
SwuPgnTestEq($mine['upgrades'] ?? null, ['SOR#120:2'], 'a unit\'s upgrade is named from either seat\'s ledger list');

// Mid-search: the peeked cards are off the deck in the engine, but nothing left the deck in the file.
SwuPgnRecFrameBegin();
DecisionQueueController::AddDecision(1, 'TOPDECKSEARCH', 'SOR_101,SOR_102|SOR_101|count:1|SOR_101:1,SOR_102:1|cards|Take|top', 1);
$d = &GetDeck(1); foreach ($d as $i => $o) if (in_array($o->CardID, ['SOR_101', 'SOR_102'], true)) $d[$i]->removed = true; unset($d);
SwuPgnRecFrameCommit();
SwuPgnTestEq(array_column(SwuPgnEventsRead($gameName), 't'), [], 'staging the search writes nothing');
SwuPgnTestEq($kf()['players']['1']['deckSize'] ?? null, 3, 'deckSize counts cards a search is holding');
$Lh = SwuPgnStateRead($gameName)['ledger']; $Lh['limbo'][] = ['copy' => 'SOR#103', 'cardID' => 'SOR_103', 'seat' => 1, 'from' => 'deck', 'key' => 'deck', 'host' => null, 'deploy' => false];
SwuPgnTestEq(SwuPgnKeyframe(SwuPgnSnapshot(), $Lh, ['round' => 1, 'phase' => 'action', 'initiativeTaken' => false])['players']['1']['deckSize'] ?? null, 4,
    'and a card held out of the search until it is played (limbo)');

// What a base holds (SWU-PGN 1.0 base-host revision): an upgrade on it and a unit it captured, by the ledger's copy ids.
$kb = SwuPgnKeyframe(SwuPgnSnapshot(), $Lh, ['round' => 1, 'phase' => 'action', 'initiativeTaken' => false]);
SwuPgnTestEq([$kb['players']['1']['baseUpgrades'] ?? null, $kb['players']['2']['baseCaptured'] ?? null], [[], []], 'a bare base holds nothing — the fields are there, empty');
$base = &GetBase(1); $base[0]->Subcards = [['CardID' => 'HMW_206', 'Owner' => 1, 'Controller' => 1, 'TurnEffects' => [], 'IsPilot' => false]]; unset($base);
AddGlobalEffects(1, 'SWU_BASECAPTIVE|SOR_049|2');
$Lb = $Lh;
SwuPgnLedgerPut($Lb, 1, 'base', 'HMW#206');
SwuPgnLedgerPut($Lb, 1, 'capture:base@1', 'SOR#49:2');
$kb = SwuPgnKeyframe(SwuPgnSnapshot(), $Lb, ['round' => 1, 'phase' => 'action', 'initiativeTaken' => false]);
SwuPgnTestEq([$kb['players']['1']['baseUpgrades'] ?? null, $kb['players']['1']['baseCaptured'] ?? null, $kb['players']['2']['baseUpgrades'] ?? null],
    [['HMW#206'], ['SOR#49:2'], []], 'a base upgrade and a base captive are in that seat\'s baseUpgrades / baseCaptured');

exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $gameName));
SwuPgnTestFinish();
