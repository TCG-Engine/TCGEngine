<?php

// The seven GAMA-deck cards with a Jumpscare clause.
//
//   DOT_034 Carpathian Wildcat  Pay 1 malice: Deal 2 damage to a minion.
//   DOT_053 Coven Feast         If you control a Vampire, deal 1 damage to a minion.
//   DOT_068 Deputy Hendricks    Prevent the next 1 damage dealt to your monster this phase.
//   DOT_076 Veteran Harpooner   Deal 1 damage to a Creature minion.
//   DOT_098 Narrow Escape       Pay 2 malice: Play this card for 0 blood.
//   DOT_112 Larry Vaughn        Deal 1 indirect damage to a player.
//   DOT_159 Carriage Driver     Move an allied minion.
//
// 2026-09-16: implemented long ago, never tested. All seven were read against their printed text in
// this pass and all seven matched.
//
// ⚠ A Jumpscare is NOT the card's main ability, and several of these cards word the two halves
// DIFFERENTLY. DOT_076's Played clause says "a Creature minion HERE" while its Jumpscare says just
// "a Creature minion" — so the Jumpscare reaches the whole board and the Played one does not. Sharing
// a target helper between the two halves is the obvious way to get this wrong, and nothing but a
// location-sensitive test would notice. Each card's two halves are pinned separately here and in
// test_hellbreak_gama_played.php.
//
// As in the Played batch: the prereq is the testable surface for an ability that raises a decision,
// and it carries the card's printed condition. Abilities with no decision are invoked directly.

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

global $p1Monster, $p2Monster, $p1Characters, $p2Characters, $p1Assets, $p2Assets,
       $p1Deck, $p2Deck, $p1Crypt, $p2Crypt, $p1Hand, $p2Hand,
       $gLocations, $gTurnNumber, $gCurrentPhase;
$gTurnNumber = 1;
$gCurrentPhase = 'HORROR';
$p1Assets = []; $p2Assets = []; $p1Crypt = []; $p2Crypt = [];
$p1Hand = []; $p2Hand = []; $p1Deck = []; $p2Deck = [];

$uid = 1100;
$minion = function(string $cardID, int $player, int $slot = 1, int $damage = 0) use (&$uid) {
    $obj = new Characters($cardID . ' 2 ' . $damage . ' ' . $player . ' ' . $player . ' ' . $slot . ' ' . (++$uid) . ' - -', 'Characters', $player);
    $obj->UniqueID = $uid; $obj->Damage = $damage; $obj->LocationSlot = $slot;
    return $obj;
};
$monster = function(string $cardID, int $player) use (&$uid) {
    $obj = new Monster($cardID . ' 2 LURKING ' . $player . ' ' . $player . ' ' . (++$uid) . ' - -', 'Monster', $player);
    $obj->UniqueID = $uid;
    return $obj;
};
$loc = function(string $cardID, int $slot) use (&$uid) {
    $obj = new Locations($cardID . ' ' . $slot . ' 1 0 0 0 9 ' . (++$uid), 'Locations', 0);
    $obj->Slot = $slot; $obj->UniqueID = $uid;
    return $obj;
};
$gLocations = [$loc('DOT_016', 1), $loc('DOT_015', 2)];
$p1Monster = [$monster('DOT_001', 1)];
$p2Monster = [$monster('DOT_006', 2)];

// The generated Jumpscare prereq takes ($player, $cardID, $owner).
$prereq = function(string $cardID, int $player) {
    $fn = $GLOBALS['jumpscareUsedPrereqs'][$cardID . ':0'] ?? null;
    if(!is_callable($fn)) return null;
    return boolval($fn($player, $cardID, $player));
};

foreach(['DOT_034','DOT_053','DOT_068','DOT_076','DOT_098','DOT_112','DOT_159'] as $cardID) {
    $check(isset($GLOBALS['jumpscareUsedAbilities'][$cardID . ':0']), "{$cardID} has a compiled Jumpscare macro");
}

// ---------------------------------------------------------------------------
// DOT_034 Carpathian Wildcat — any minion, anywhere
// ---------------------------------------------------------------------------
$p1Characters = []; $p2Characters = [];
$check($prereq('DOT_034', 1) === false, 'DOT_034 NEGATIVE: with no minion in play it is not offered');
$p2Characters = [$minion('DOT_105', 2, 2)];
$check($prereq('DOT_034', 1) === true, 'DOT_034: any minion at any location is a legal target');

// ---------------------------------------------------------------------------
// DOT_053 Coven Feast — gated on controlling a Vampire
// ---------------------------------------------------------------------------
// ⚠ "A Vampire you control" includes YOUR MONSTER. Dracula's own traits are Undead and Vampire, so
// in the GAMA Dracula deck this Jumpscare is live from turn one with no minions on the board at all.
// The negative therefore needs a non-Vampire monster, which is the only way the gate is ever false.
$p1Monster = [$monster('DOT_006', 1)]; // Jaws: Creature, Shark
$p1Characters = []; $p2Characters = [$minion('DOT_105', 2, 1)];
$check($prereq('DOT_053', 1) === false, 'DOT_053 NEGATIVE: no Vampire controlled at all, not offered');
$p1Characters = [$minion('DOT_165', 1, 1)];
$check($prereq('DOT_053', 1) === true, 'DOT_053: controlling a Vampire MINION offers it');
// NEGATIVE: the Vampire must be YOURS.
$p1Characters = []; $p2Characters = [$minion('DOT_165', 2, 1)];
$check($prereq('DOT_053', 1) === false, 'DOT_053 NEGATIVE: an ENEMY Vampire does not satisfy "you control a Vampire"');
// And the finding itself, pinned: the monster alone satisfies the gate.
$p1Monster = [$monster('DOT_001', 1)]; // Dracula: Undead, Vampire
$p1Characters = []; $p2Characters = [$minion('DOT_105', 2, 1)];
$check($prereq('DOT_053', 1) === true,
    'DOT_053: a Vampire MONSTER alone satisfies it — always live in a Dracula deck');

// ---------------------------------------------------------------------------
// DOT_076 Veteran Harpooner — a Creature minion, and NOT restricted to "here"
// ---------------------------------------------------------------------------
$p1Characters = []; $p2Characters = [$minion('DOT_027', 2, 1)]; // Renfield: Human
$check($prereq('DOT_076', 1) === false, 'DOT_076 NEGATIVE: a non-Creature minion is not a legal target');
$p2Characters = [$minion('DOT_105', 2, 1)];
$check($prereq('DOT_076', 1) === true, 'DOT_076: a Creature minion is a legal target');
// ⚠ THE HALF-VS-HALF CHECK. The Played clause says "here"; the Jumpscare does not.
$p2Characters = [$minion('DOT_105', 2, 2)];
$check($prereq('DOT_076', 1) === true,
    'DOT_076: the JUMPSCARE reaches a Creature at EITHER location — unlike its Played half, it has no "here"');

// ---------------------------------------------------------------------------
// DOT_159 Carriage Driver — an ALLIED minion
// ---------------------------------------------------------------------------
$p1Characters = []; $p2Characters = [$minion('DOT_151', 2, 1)];
$check($prereq('DOT_159', 1) === false, 'DOT_159 NEGATIVE: only an ENEMY minion in play, not offered');
$p1Characters = [$minion('DOT_151', 1, 1)];
$check($prereq('DOT_159', 1) === true, 'DOT_159: an allied minion offers it');
// Its Played half needs ANOTHER allied minion beside itself; the Jumpscare needs only one.
$check($prereq('DOT_159', 1) === true,
    'DOT_159: one allied minion is enough for the Jumpscare (its Played half needs a second)');

// ---------------------------------------------------------------------------
// DOT_112 Larry Vaughn — indirect damage to a player, so both monsters must be live
// ---------------------------------------------------------------------------
$check($prereq('DOT_112', 1) === true, 'DOT_112: offered while both monsters are in play');
$p2Monster = [];
$check($prereq('DOT_112', 1) === false, 'DOT_112 NEGATIVE: not offered when a monster has left play');
$p2Monster = [$monster('DOT_006', 2)];

// ---------------------------------------------------------------------------
// DOT_068 Deputy Hendricks — no decision, so invoke it and read the effect
// ---------------------------------------------------------------------------
DecisionQueueController::StoreVariable('HellbreakMonsterDamagePreventionP1', null);
DecisionQueueController::StoreVariable('cardID', 'DOT_068');
DecisionQueueController::StoreVariable('owner', 1);
$GLOBALS['jumpscareUsedAbilities']['DOT_068:0'](1);
$stored = DecisionQueueController::GetVariable('HellbreakMonsterDamagePreventionP1');
$check(is_array($stored) && intval($stored['amount'] ?? 0) === 1,
    'DOT_068 registers exactly 1 point of monster-damage prevention');
$check(is_array($stored) && strval($stored['phase'] ?? '') === strval(GetCurrentPhase()),
    'DOT_068 stamps the CURRENT PHASE — "this phase" is what makes it expire');
$opponent = DecisionQueueController::GetVariable('HellbreakMonsterDamagePreventionP2');
$check(!is_array($opponent) || intval($opponent['amount'] ?? 0) === 0,
    'DOT_068 NEGATIVE: the OPPONENT gets no prevention — it protects "your monster"');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
