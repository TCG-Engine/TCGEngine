<?php

// An EXHAUSTED character cannot be declared as an attacker.
//
// 2026-09-16: HellbreakLegalAttackers checked readiness for the MONSTER but not for MINIONS, and
// HellbreakAttackTargetsForRef — the only other gate — never looks at Status. So an exhausted minion
// stayed a legal attacker and could attack again and again in one Horror phase. Declaring an attack
// sets Status = 1, but a minion that is already 1 was never refused.
//
// Found by playing the tutorial through: after the Wolf attacked and exhausted, "Attack" was still on
// offer. It is not a tutorial bug — it broke combat in every Hellbreak game.
//
// Rules of Play p6, Declare Attacker: "exhaust a READY character you control." The fix is one guard,
// so this file pins both halves of it: a ready minion IS offered (so the guard is not too strict) and
// an exhausted one is NOT (so it exists at all), for minions and the monster alike.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

include_once './Core/DecisionQueueController.php';
include_once './HellbreakSim/ZoneClasses.php';
include_once './HellbreakSim/ZoneAccessors.php';
include_once './HellbreakSim/GamestateParser.php';
include_once './HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './HellbreakSim/Custom/GameLogic.php';
include_once './HellbreakSim/GeneratedCode/GeneratedMacroCode.php';
include_once './HellbreakSim/Custom/CardLogic.php';
include_once './HellbreakSim/Custom/CombatLogic.php';

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

global $p1Monster, $p2Monster, $p1Characters, $p2Characters, $gLocations, $gTurnNumber,
       $gCurrentPhase, $gTurnPlayer, $gSlumberPlayer, $playerID,
       $p1DecisionQueue, $p2DecisionQueue, $gDecisionQueueVariables;
$playerID = 1;
// A declared attack queues decisions, so the queues must exist — otherwise an attacker that wrongly
// gets through the gate fatals instead of failing the assertion that caught it.
$p1DecisionQueue = []; $p2DecisionQueue = []; $gDecisionQueueVariables = '';
$gTurnNumber = 1; $gCurrentPhase = 'HORROR'; $gTurnPlayer = 1; $gSlumberPlayer = 0;

$uid = 7000;
$minion = function(string $cardID, int $player, int $status, int $slot = 1) use (&$uid) {
    $obj = new Characters($cardID . ' ' . $status . ' 0 ' . $player . ' ' . $player . ' ' . $slot . ' ' . (++$uid) . ' - -', 'Characters', $player);
    $obj->UniqueID = $uid; $obj->Status = $status; $obj->LocationSlot = $slot;
    return $obj;
};
$monster = function(string $cardID, int $player, int $status) use (&$uid) {
    $obj = new Monster($cardID . ' ' . $status . ' LURKING ' . $player . ' ' . $player . ' ' . (++$uid) . ' - -', 'Monster', $player);
    $obj->UniqueID = $uid; $obj->Status = $status; $obj->Side = 'LURKING';
    return $obj;
};
$loc = function(string $cardID, int $slot) use (&$uid) {
    $obj = new Locations($cardID . ' ' . $slot . ' 1 0 0 0 9 ' . (++$uid), 'Locations', 0);
    $obj->Slot = $slot; $obj->UniqueID = $uid;
    return $obj;
};
const READY = 2;
const EXHAUSTED = 1;

$gLocations = [$loc('DOT_015', 1), $loc('DOT_020', 2)];
$p2Monster = [$monster('DOT_006', 2, READY)];
$p2Characters = [];

// ---------------------------------------------------------------------------
// Minions
// ---------------------------------------------------------------------------
$p1Monster = [$monster('DOT_001', 1, EXHAUSTED)]; // keep the monster out of the minion checks
$p1Characters = [$minion('DOT_028', 1, READY)];
$check(in_array('myCharacters-0', HellbreakLegalAttackers(1), true),
    'a READY minion is a legal attacker — the guard must not be too strict');

$p1Characters = [$minion('DOT_028', 1, EXHAUSTED)];
$check(!in_array('myCharacters-0', HellbreakLegalAttackers(1), true),
    'an EXHAUSTED minion is NOT a legal attacker (it was, which let one minion attack every action)');

// Mixed board: only the ready one is offered, and the index is the right one.
$p1Characters = [$minion('DOT_028', 1, EXHAUSTED), $minion('DOT_025', 1, READY)];
$attackers = HellbreakLegalAttackers(1);
$check(!in_array('myCharacters-0', $attackers, true) && in_array('myCharacters-1', $attackers, true),
    'on a mixed board only the ready minion is offered');

// ---------------------------------------------------------------------------
// The monster — already correct; pinned so the fix cannot regress it
// ---------------------------------------------------------------------------
$p1Characters = [];
$p1Monster = [$monster('DOT_001', 1, READY)];
$check(in_array('myMonster-0', HellbreakLegalAttackers(1), true), 'a READY monster is a legal attacker');
$p1Monster = [$monster('DOT_001', 1, EXHAUSTED)];
$check(!in_array('myMonster-0', HellbreakLegalAttackers(1), true), 'an EXHAUSTED monster is not');

// ---------------------------------------------------------------------------
// The attack flow itself refuses an exhausted attacker, not just the offer
// ---------------------------------------------------------------------------
$p1Monster = [$monster('DOT_001', 1, EXHAUSTED)];
$p1Characters = [$minion('DOT_028', 1, EXHAUSTED)];
$check(HellbreakChooseAttacker(1, 'myCharacters-0') === false,
    'declaring an exhausted minion as the attacker is refused, even if something asks directly');

// A minion ENEMY-controlled must never appear, ready or not.
$p1Characters = [];
$p2Characters = [$minion('DOT_105', 2, READY)];
$check(!in_array('theirCharacters-0', HellbreakLegalAttackers(1), true), 'an enemy minion is never your attacker');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
