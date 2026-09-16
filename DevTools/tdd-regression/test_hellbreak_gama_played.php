<?php

// The ten GAMA-deck cards whose abilities hang off the Played macro.
//
//   DOT_039 Ferocious Wolfpack   If you have initiative, deal 1 damage to a character here.
//   DOT_044 Count Alucard        Add 3 malice to this location.
//   DOT_052 Drain Life           Deal 1 damage to a minion. You may heal 1 damage from another minion.
//   DOT_053 Coven Feast          Deal damage to a minion equal to the number of Vampire cards you control.
//   DOT_076 Veteran Harpooner    You may deal 1 damage to a Creature minion here.
//   DOT_098 Narrow Escape        Exhaust a minion.
//   DOT_104 A Panic On Our Hands Exhaust up to 2 minions at the same location and move them.
//   DOT_119 Ravenous Predator    If an enemy minion is damaged, gain 1 blood or 1 malice.
//   DOT_128 Killer Whale         You may kill a damaged minion here.
//   DOT_159 Carriage Driver      You may choose another allied minion here. If you do, move it and this card.
//   DOT_180 Ancient Wisdom       Draw 2.
//
// 2026-09-16: implemented long ago, never tested. All ten were read against their printed text as
// part of this pass and all ten matched.
//
// WHAT IS TESTED HERE, and what is not. Most of these abilities raise an interactive decision, so
// their body cannot resolve without a live decision queue. Their PREREQ can: it is a pure function of
// board state, it is what decides whether the ability is offered at all, and it is where each card's
// printed condition lives ("if you have initiative", "if an enemy minion is damaged", "a Creature
// minion HERE"). A wrong prereq either hides a working ability or offers a fizzling one, and neither
// shows up anywhere else. The two abilities with no decision are invoked directly.
//
// Proving the ENGINE dispatches Played at the right moment is a separate job, done once for the macro
// family by an integration fixture rather than ten times here.

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
       $p1Deck, $p2Deck, $p1Crypt, $p2Crypt, $p1Hand, $p2Hand, $p1Blood, $p2Blood,
       $p1Malice, $p2Malice, $gLocations, $gTurnNumber, $gInitiativePlayer;
$gTurnNumber = 1;
$p1Assets = []; $p2Assets = []; $p1Crypt = []; $p2Crypt = [];
$p1Hand = []; $p2Hand = []; $p1Deck = []; $p2Deck = [];
$p1Blood = 0; $p2Blood = 0; $p1Malice = 0; $p2Malice = 0;
$p1Monster = []; $p2Monster = [];

$uid = 900;
$minion = function(string $cardID, int $player, int $slot = 1, int $damage = 0) use (&$uid) {
    $obj = new Characters($cardID . ' 2 ' . $damage . ' ' . $player . ' ' . $player . ' ' . $slot . ' ' . (++$uid) . ' - -', 'Characters', $player);
    $obj->UniqueID = $uid; $obj->Damage = $damage; $obj->LocationSlot = $slot;
    return $obj;
};
$loc = function(string $cardID, int $slot, int $controller = 0, int $threshold = 3) use (&$uid) {
    $obj = new Locations($cardID . ' ' . $slot . ' 1 ' . $controller . ' 0 0 ' . $threshold . ' ' . (++$uid), 'Locations', 0);
    $obj->Slot = $slot; $obj->Controller = $controller; $obj->UniqueID = $uid;
    return $obj;
};
$gLocations = [$loc('DOT_016', 1), $loc('DOT_015', 2)];

// The generated prereq takes ($player, $mzID, $fromZone, $locationSlot).
$prereq = function(string $cardID, int $player, string $mzID, int $slot) {
    $fn = $GLOBALS['playedPrereqs'][$cardID . ':0'] ?? null;
    if(!is_callable($fn)) return null;
    return boolval($fn($player, $mzID, 'Hand', $slot));
};
$hasAbility = fn(string $cardID) => isset($GLOBALS['playedAbilities'][$cardID . ':0']);

foreach(['DOT_039','DOT_044','DOT_052','DOT_053','DOT_076','DOT_098','DOT_104','DOT_119','DOT_128','DOT_159','DOT_180'] as $cardID) {
    $check($hasAbility($cardID), "{$cardID} has a compiled Played macro");
}

// ---------------------------------------------------------------------------
// DOT_039 Ferocious Wolfpack — gated on INITIATIVE and on a character being here
// ---------------------------------------------------------------------------
$p1Characters = [$minion('DOT_039', 1, 1)]; $p2Characters = [$minion('DOT_105', 2, 1)];
$gInitiativePlayer = 1;
$check($prereq('DOT_039', 1, 'myCharacters-0', 1) === true, 'DOT_039: offered with initiative and a character here');
$gInitiativePlayer = 2;
$check($prereq('DOT_039', 1, 'myCharacters-0', 1) === false,
    'DOT_039 NEGATIVE: WITHOUT initiative it is not offered — the initiative gate is load-bearing');
$gInitiativePlayer = 1;
$p2Characters = [$minion('DOT_105', 2, 2)]; // the only other character is at the OTHER location
$check($prereq('DOT_039', 1, 'myCharacters-0', 2) === true, 'DOT_039: "here" follows the slot it was played into');

// ---------------------------------------------------------------------------
// DOT_119 Ravenous Predator — gated on an ENEMY minion being damaged
// ---------------------------------------------------------------------------
$p1Characters = [$minion('DOT_119', 1, 1)];
$p2Characters = [$minion('DOT_105', 2, 1, 0)];
$check($prereq('DOT_119', 1, 'myCharacters-0', 1) === false,
    'DOT_119 NEGATIVE: an UNDAMAGED enemy minion does not satisfy it');
$p2Characters = [$minion('DOT_105', 2, 1, 1)];
$check($prereq('DOT_119', 1, 'myCharacters-0', 1) === true, 'DOT_119: a damaged enemy minion satisfies it');
$p1Characters = [$minion('DOT_119', 1, 1), $minion('DOT_105', 1, 1, 3)]; $p2Characters = [];
$check($prereq('DOT_119', 1, 'myCharacters-0', 1) === false,
    'DOT_119 NEGATIVE: a damaged ALLY is not an enemy minion');

// ---------------------------------------------------------------------------
// DOT_053 Coven Feast — gated on controlling a Vampire AND a minion existing
// ---------------------------------------------------------------------------
$p1Characters = []; $p2Characters = [$minion('DOT_105', 2, 1)];
$check($prereq('DOT_053', 1, 'myCrypt-0', 1) === false,
    'DOT_053 NEGATIVE: with no Vampire controlled it is not offered');
$p1Characters = [$minion('DOT_165', 1, 1)]; // Verona is a Vampire
$check($prereq('DOT_053', 1, 'myCrypt-0', 1) === true, 'DOT_053: controlling a Vampire offers it');
$p1Characters = [$minion('DOT_165', 1, 1)]; $p2Characters = [];
$check($prereq('DOT_053', 1, 'myCrypt-0', 1) === true, 'DOT_053: your own Vampire is itself a legal target');

// ---------------------------------------------------------------------------
// DOT_076 Veteran Harpooner — a CREATURE minion, and only HERE
// ---------------------------------------------------------------------------
$p1Characters = [$minion('DOT_076', 1, 1)]; $p2Characters = [$minion('DOT_027', 2, 1)]; // Renfield: Human
$check($prereq('DOT_076', 1, 'myCharacters-0', 1) === false,
    'DOT_076 NEGATIVE: a non-Creature minion here is not a legal target');
$p2Characters = [$minion('DOT_105', 2, 1)]; // Barracuda: Creature
$check($prereq('DOT_076', 1, 'myCharacters-0', 1) === true, 'DOT_076: a Creature minion here is offered');
$p2Characters = [$minion('DOT_105', 2, 2)];
$check($prereq('DOT_076', 1, 'myCharacters-0', 1) === false,
    'DOT_076 NEGATIVE: a Creature at the OTHER location is not "here"');

// ---------------------------------------------------------------------------
// DOT_128 Killer Whale — a DAMAGED minion, and only HERE
// ---------------------------------------------------------------------------
$p1Characters = [$minion('DOT_128', 1, 1)]; $p2Characters = [$minion('DOT_105', 2, 1, 0)];
$check($prereq('DOT_128', 1, 'myCharacters-0', 1) === false,
    'DOT_128 NEGATIVE: an undamaged minion here is not a legal target');
$p2Characters = [$minion('DOT_105', 2, 1, 2)];
$check($prereq('DOT_128', 1, 'myCharacters-0', 1) === true, 'DOT_128: a damaged minion here is offered');
$p2Characters = [$minion('DOT_105', 2, 2, 2)];
$check($prereq('DOT_128', 1, 'myCharacters-0', 1) === false,
    'DOT_128 NEGATIVE: a damaged minion at the OTHER location is not "here"');

// ---------------------------------------------------------------------------
// DOT_159 Carriage Driver — needs ANOTHER allied minion here, so one is not enough
// ---------------------------------------------------------------------------
$driver = $minion('DOT_159', 1, 1);
$p1Characters = [$driver]; $p2Characters = [];
$check($prereq('DOT_159', 1, 'myCharacters-0', 1) === false,
    'DOT_159 NEGATIVE: alone it is not offered — "another allied minion" excludes itself');
$p1Characters = [$driver, $minion('DOT_151', 1, 1)];
$check($prereq('DOT_159', 1, 'myCharacters-0', 1) === true, 'DOT_159: a second allied minion here offers it');
$p1Characters = [$driver]; $p2Characters = [$minion('DOT_151', 2, 1)];
$check($prereq('DOT_159', 1, 'myCharacters-0', 1) === false,
    'DOT_159 NEGATIVE: an ENEMY minion here is not "another ALLIED minion"');

// ---------------------------------------------------------------------------
// DOT_098 Narrow Escape — any minion anywhere
// ---------------------------------------------------------------------------
$p1Characters = []; $p2Characters = [];
$check($prereq('DOT_098', 1, 'myCrypt-0', 1) === false, 'DOT_098 NEGATIVE: no minions in play, not offered');
$p2Characters = [$minion('DOT_105', 2, 2)];
$check($prereq('DOT_098', 1, 'myCrypt-0', 1) === true,
    'DOT_098: an enemy minion at either location is a legal target (the text names no side or place)');

// ---------------------------------------------------------------------------
// DOT_052 Drain Life — "Deal 1 damage to a minion. You may heal 1 damage from ANOTHER minion."
//
// The heal pool excludes the minion just damaged, by UniqueID captured BEFORE the damage lands.
// That exclusion is the whole of "another": without it the card damages a minion and immediately
// heals the same one, doing nothing at all.
// ---------------------------------------------------------------------------
$p1Characters = []; $p2Characters = [];
$check($prereq('DOT_052', 1, 'myCrypt-0', 1) === false, 'DOT_052 NEGATIVE: no minion in play, not offered');
$p2Characters = [$minion('DOT_105', 2, 1)];
$check($prereq('DOT_052', 1, 'myCrypt-0', 1) === true, 'DOT_052: any minion is a legal damage target');

// The "another" exclusion, read straight from the helper the ability uses.
$struck = $minion('DOT_105', 2, 1, 2);
$other  = $minion('DOT_151', 1, 1, 1);
$p1Characters = [$other]; $p2Characters = [$struck];
$all = HellbreakDamagedMinionTargets(1, 0, 0, '', 0);
$excluded = HellbreakDamagedMinionTargets(1, 0, 0, '', intval($struck->UniqueID));
$check(count($all) === 2, 'DOT_052: both damaged minions are heal candidates before the exclusion');
$check(count($excluded) === 1,
    'DOT_052: excluding the struck minion by UniqueID leaves exactly the OTHER one — "another minion"');
$check(count(HellbreakDamagedMinionTargets(1, 0, 0, '', intval($other->UniqueID))) === 1,
    'DOT_052: the exclusion follows the UniqueID given, not a fixed side');

// ---------------------------------------------------------------------------
// The two abilities with no decision: invoke them and read the result
// ---------------------------------------------------------------------------
$p1Deck = [new Deck('DOT_151', 'Deck', 1), new Deck('DOT_152', 'Deck', 1), new Deck('DOT_025', 'Deck', 1)];
$p1Hand = [];
$GLOBALS['playedAbilities']['DOT_180:0'](1);
$check(count($p1Hand) === 2, 'DOT_180 Ancient Wisdom draws exactly 2');
$check(count($p1Deck) === 1, 'DOT_180: the two cards came off the deck');

// A high threshold keeps this measuring the malice ADDITION. At the printed threshold of 3 the
// third point immediately takes the location, which cascades into the scheme barrier and needs a
// live decision queue — correct game behaviour, but a different test.
$gLocations = [$loc('DOT_016', 1, 0, 9), $loc('DOT_015', 2, 0, 9)];
DecisionQueueController::StoreVariable('mzID', 'myCharacters-0');
DecisionQueueController::StoreVariable('fromZone', 'Hand');
DecisionQueueController::StoreVariable('locationSlot', 1);
$before = intval($gLocations[0]->MaliceP1 ?? 0);
$GLOBALS['playedAbilities']['DOT_044:0'](1);
$after = intval($gLocations[0]->MaliceP1 ?? 0);
$check($after === $before + 3, "DOT_044 Count Alucard adds exactly 3 malice to the location played into (got " . ($after - $before) . ")");
$check(intval($gLocations[1]->MaliceP1 ?? 0) === 0,
    'DOT_044 NEGATIVE: the OTHER location gets nothing — "this location" is the one it entered');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
