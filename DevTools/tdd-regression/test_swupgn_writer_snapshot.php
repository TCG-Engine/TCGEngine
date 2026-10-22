<?php
// SWU-PGN writer snapshot: the board as plain data (the diff the recorder runs between requests reads only this).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_snapshot.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnWriter/_include.php';
global $gameName, $playerID;
$gameName = 'swupgnsnap_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder();
CommonSetup($b, 'grw', 'brk', ['leaderDeployed' => true, 'baseDamage' => 4], []);
$b->WithActivePlayer(2)->WithCurrentRoundBeing(3)->WithInitiativePlayerBeing(2)->WithInitiativeClaimed();
foreach (['SOR_200', 'SOR_201', 'SOR_202'] as $c) $b->WithCardInHandForPlayer(1, $c);
foreach (['SOR_095', 'SOR_046', 'SOR_100'] as $c) $b->WithCardInDeckForPlayer(1, $c);
foreach (['SOR_101', 'SOR_102'] as $c) $b->WithCardInDiscardForPlayer(1, $c);
$b->FillResourcesForPlayer(1, 'SOR_095', 2, false);
$b->FillResourcesForPlayer(1, 'LAW_T01', 1);
$b->WithForceForPlayer(1);
// Ground unit 0 is the deployed leader (index resolution puts it first), so the vanilla unit is added after it.
$b->WithGroundUnitForPlayer(1, 'SOR_049', false, 2);
$b->WithSpaceUnitForPlayer(1, 'SOR_040');
$b->WithGroundUnitForPlayer(2, 'SOR_095');
$b->WithGroundUnitForPlayer(2, 'SOR_157');   // Cantina Braggart: Raid 2
$b->WithGlobalEffectForPlayer(2, 'SWU_BASECAPTIVE|SOR_083|1');
$g = new GameTestAdapter(); $g->loadState($b);

// Attach subcards on the live objects: a Shield, an Advantage, a printed upgrade, a captive, and a pilot.
$sub = fn($id, $extra = []) => array_merge(GameStateBuilder::Upgrade($id, 1), $extra);
$ground = &GetGroundArena(1);
$obi = null; foreach ($ground as $o) if ($o->CardID === 'SOR_049') $obi = $o;
$obi->Subcards = [$sub('SOR_T02'), $sub('ASH_T02'), $sub('SOR_120'), $sub('SOR_095', ['IsCaptive' => true, 'Owner' => 2])];
$space = &GetSpaceArena(1);
$space[0]->Subcards = [$sub('JTL_034', ['IsPilot' => true])];

// Tombstones: a removed hand card and a removed unit must not appear.
$hand = &GetHand(1); $hand[1]->removed = true;
$theirGround = &GetGroundArena(2); $deadUid = $theirGround[0]->UniqueID; $theirGround[0]->removed = true;

$s = SwuPgnSnapshot();
$p1 = $s['seats'][1]; $p2 = $s['seats'][2];

// Turn context.
SwuPgnTestEq($s['round'], 3, 'round = the turn number');
SwuPgnTestEq($s['active'], 2, 'active = the turn player');
SwuPgnTestEq($s['initiative'], 2, 'initiative = the seat holding it');
SwuPgnTestEq($s['initiativeTaken'], true, 'initiativeTaken reads the CLAIMED suffix');
SwuPgnTestEq($s['phase'], GetCurrentPhase(), 'phase = the engine phase');

// Ordered card zones.
SwuPgnTestEq($p1['hand'], ['SOR_200', 'SOR_202'], 'hand in zone order, tombstone excluded');
SwuPgnTestEq($p1['deck'], ['SOR_095', 'SOR_046', 'SOR_100'], 'deck top-first');
SwuPgnTestEq($p1['discard'], ['SOR_101', 'SOR_102'], 'discard in zone order');

// Resources + Force.
SwuPgnTestEq($p1['resources'], [
    ['cardID' => 'SOR_095', 'ready' => false, 'credit' => false],
    ['cardID' => 'SOR_095', 'ready' => false, 'credit' => false],
    ['cardID' => 'LAW_T01', 'ready' => true,  'credit' => true],
], 'resources carry ready + credit');
SwuPgnTestEq($p1['force'], true, 'the Force is with seat 1');
SwuPgnTestEq($p2['force'], false, 'the Force is not with seat 2');

// Base.
$base1 = GetBase(1)[0];
SwuPgnTestEq($p1['base'], ['cardID' => $base1->CardID, 'damage' => 4, 'maxHp' => SWUBaseMaxHp($base1->CardID),
    'epicUsed' => false, 'captives' => [], 'upgrades' => []], 'base: damage, max HP, epic flag, its upgrades');
SwuPgnTestEq($p2['base']['captives'], ['SOR_083'], 'a base captive (GlobalEffects flag) is listed on the captor base');

// Leader (deployed).
$leader1 = GetLeader(1)[0];
SwuPgnTestEq($p1['leader']['cardID'], $leader1->CardID, 'leader cardID');
SwuPgnTestEq($p1['leader']['deployed'], true, 'leader deployed');
SwuPgnTestEq($p1['leader']['deployedUid'], intval($leader1->DeployedUniqueID), 'deployedUid = DeployedUniqueID');
SwuPgnTestCheck(isset($p1['units'][$p1['leader']['deployedUid']]), 'the deployed leader unit is in units under that uid');
SwuPgnTestEq($p2['leader']['deployed'], false, 'an undeployed leader');

// Units.
$u = $p1['units'][intval($obi->UniqueID)] ?? null;
SwuPgnTestCheck($u !== null, 'units are keyed by UniqueID');
SwuPgnTestEq([$u['cardID'], $u['arena'], $u['damage'], $u['ready'], $u['owner'], $u['controller']],
    ['SOR_049', 'ground', 2, false, 1, 1], 'unit: card, arena, damage, exhausted, owner, controller');
SwuPgnTestEq($u['tokens'], ['shield' => 1, 'experience' => 0, 'advantage' => 1, 'weakness' => 0], 'token upgrades are counted as tokens');
SwuPgnTestEq($u['upgrades'], [['cardID' => 'SOR_120', 'uid' => null, 'isPilot' => false]], 'printed upgrades only (no tokens, no captives)');
SwuPgnTestEq($u['captives'], ['SOR_095'], 'a guarded captive');
SwuPgnTestEq($u['power'], ObjectCurrentPower($obi), 'power = ObjectCurrentPower of the live (buffed) unit');
SwuPgnTestEq($u['hp'], ObjectCurrentHP($obi), 'hp = ObjectCurrentHP of the live (buffed) unit');
SwuPgnTestCheck($u['power'] > 4, 'the buff is visible in power', (string)$u['power']);
$v = $p1['units'][intval($space[0]->UniqueID)];
SwuPgnTestEq($v['arena'], 'space', 'a space unit');
SwuPgnTestEq($v['upgrades'], [['cardID' => 'JTL_034', 'uid' => null, 'isPilot' => true]], 'a pilot is an upgrade flagged isPilot');
SwuPgnTestCheck(!isset($p2['units'][intval($deadUid)]) && count($p2['units']) === 1, 'a tombstoned unit is excluded');

// Keywords: sorted, lower-case, numeric ones carry their value.
$kw = $u['keywords'];
$sorted = $kw; sort($sorted);
SwuPgnTestEq($kw, $sorted, 'keywords are sorted');
SwuPgnTestCheck(in_array('sentinel', $kw, true), 'a printed boolean keyword (Obi-Wan: Sentinel)', SwuPgnTestDump($kw));
SwuPgnTestCheck(!preg_grep('/^raid/', $kw), 'no Raid on a unit without it');
$braggart = array_values($p2['units'])[0];
SwuPgnTestCheck(in_array('raid 2', $braggart['keywords'], true), 'a numeric keyword reads "raid 2"', SwuPgnTestDump($braggart['keywords']));

// A deck search's peeked cards ride in its pending decision; they are staged ("temp"), not gone.
DecisionQueueController::AddDecision(2, 'TOPDECKSEARCH', 'SOR_101,SOR_102|SOR_101|count:1|SOR_101:1,SOR_102:1|cards|Take|top', 1);
SwuPgnTestEq(SwuPgnSnapshot()['seats'][2]['temp'], ['SOR_101', 'SOR_102'], 'peeked search cards are staged in temp');
SwuPgnTestEq($s['seats'][1]['temp'], [], 'no staging when nothing is pending');

// The snapshot is plain data: it survives a JSON round trip unchanged.
SwuPgnTestEq(json_decode(json_encode($s), true), $s, 'the snapshot is JSON-plain');

exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $gameName) . ' ' . escapeshellarg('./Games/' . $gameName));
SwuPgnTestFinish();
