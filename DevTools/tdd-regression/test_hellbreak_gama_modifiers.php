<?php

// The six GAMA-deck cards whose abilities are pure VALUE MODIFIERS.
//
//   DOT_032 Mina Seward      While this card is damaged, it gets +2 combat and gains Vampire.
//   DOT_042 Countess Zaleska This card costs 1 blood less to play for each location you control.
//   DOT_044 Count Alucard    If you have initiative, this card costs 2 blood less to play.
//   DOT_068 Deputy Hendricks While this card is the only allied minion here, it gets +1 combat.
//   DOT_109 Rogue Shark      While an enemy minion is damaged, this card gets +1 combat.
//   DOT_122 Threat From Below While an enemy minion is damaged, this card gets +2 combat.
//
// 2026-09-16: all six were implemented long ago and never tested. A value modifier is the shape most
// likely to be silently wrong, because HellbreakApplyValueModifiers offers EVERY object in play as a
// source for EVERY question — so a modifier whose prereq does not pin "the thing being asked about is
// me" answers for the whole board. That failure is invisible from the card's own point of view: the
// card behaves correctly, and everything else quietly gains its bonus too.
//
// Every card here therefore gets three things: the positive, the NEGATIVE that proves its gate is
// load-bearing, and a SCOPE check that some other object does not receive the bonus.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

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
       $p1Deck, $p2Deck, $p1Crypt, $p2Crypt, $gLocations, $gTurnNumber, $gInitiativePlayer;
$gTurnNumber = 1;
$p1Monster = []; $p2Monster = []; $p1Assets = []; $p2Assets = [];
$p1Deck = []; $p2Deck = []; $p1Crypt = []; $p2Crypt = [];

$uid = 700;
$minion = function(string $cardID, int $player, int $slot = 1, int $damage = 0) use (&$uid) {
    $obj = new Characters($cardID . ' 2 ' . $damage . ' ' . $player . ' ' . $player . ' ' . $slot . ' ' . (++$uid) . ' - -', 'Characters', $player);
    $obj->UniqueID = $uid;
    $obj->Damage = $damage;
    return $obj;
};
$location = function(string $cardID, int $slot, int $controller) use (&$uid) {
    $obj = new Locations($cardID . ' ' . $slot . ' 1 ' . $controller . ' 0 0 3 ' . (++$uid), 'Locations', 0);
    $obj->Slot = $slot; $obj->Controller = $controller; $obj->UniqueID = $uid;
    return $obj;
};
// Combat is printed value + CombatModifier deltas, which is what the engine asks for.
$combatOf = fn($obj) => HellbreakCardCombatValue(strval($obj->CardID), $obj);

// ---------------------------------------------------------------------------
// DOT_032 Mina Seward — while DAMAGED: +2 combat and gains Vampire
// ---------------------------------------------------------------------------
$gLocations = [];
$healthy = $minion('DOT_032', 1, 1, 0);
$wounded = $minion('DOT_032', 1, 1, 2);
$p1Characters = [$healthy, $wounded]; $p2Characters = [];
$printed = intval(CardCombat('DOT_032'));

$check($combatOf($wounded) === $printed + 2, 'DOT_032 damaged: +2 combat');
$check($combatOf($healthy) === $printed,
    'DOT_032 NEGATIVE: undamaged copy gets nothing — the damage gate is load-bearing');
$check(HellbreakObjectHasTrait($wounded, 'Vampire', 1), 'DOT_032 damaged: gains Vampire');
$check(!HellbreakObjectHasTrait($healthy, 'Vampire', 1),
    'DOT_032 NEGATIVE: undamaged copy does NOT gain Vampire');
$check(!HellbreakObjectHasTrait($wounded, 'Undead', 1),
    'DOT_032 NEGATIVE: it grants Vampire only, not any trait asked for');

// SCOPE: a damaged Mina must not buff a different damaged minion.
$otherWounded = $minion('DOT_105', 1, 1, 2);
$p1Characters = [$wounded, $otherWounded];
$check($combatOf($otherWounded) === intval(CardCombat('DOT_105')),
    'DOT_032 SCOPE: another damaged minion is NOT buffed by her');

// ---------------------------------------------------------------------------
// DOT_109 / DOT_122 — while an ENEMY minion is damaged
// ---------------------------------------------------------------------------
foreach([['DOT_109', 1], ['DOT_122', 2]] as [$cardID, $bonus]) {
    $printed = intval(CardCombat($cardID));
    $mine = $minion($cardID, 1, 1, 0);

    $p1Characters = [$mine]; $p2Characters = [$minion('DOT_105', 2, 1, 0)];
    $check($combatOf($mine) === $printed,
        "{$cardID} NEGATIVE: an UNDAMAGED enemy minion grants nothing");

    $p2Characters = [$minion('DOT_105', 2, 1, 1)];
    $check($combatOf($mine) === $printed + $bonus, "{$cardID}: a damaged enemy minion grants +{$bonus}");

    // NEGATIVE: a damaged ALLY is not an enemy.
    $p1Characters = [$mine, $minion('DOT_105', 1, 1, 2)]; $p2Characters = [];
    $check($combatOf($mine) === $printed,
        "{$cardID} NEGATIVE: a damaged ALLY does not satisfy \"enemy minion\"");
}

// ---------------------------------------------------------------------------
// DOT_068 Deputy Hendricks — while the ONLY allied minion HERE
// ---------------------------------------------------------------------------
$printed = intval(CardCombat('DOT_068'));
$deputy = $minion('DOT_068', 1, 1, 0);
$p1Characters = [$deputy]; $p2Characters = [];
$check($combatOf($deputy) === $printed + 1, 'DOT_068: alone at its location, +1 combat');

$p1Characters = [$deputy, $minion('DOT_105', 1, 1, 0)];
$check($combatOf($deputy) === $printed,
    'DOT_068 NEGATIVE: a second ALLIED minion here removes the bonus');

// "here" is per location, and an enemy minion is not an allied one.
$p1Characters = [$deputy, $minion('DOT_105', 1, 2, 0)]; // ally at the OTHER location
$check($combatOf($deputy) === $printed + 1,
    'DOT_068: an ally at the OTHER location does not count — "here" is this location');
$p1Characters = [$deputy]; $p2Characters = [$minion('DOT_105', 2, 1, 0)];
$check($combatOf($deputy) === $printed + 1,
    'DOT_068 NEGATIVE: an ENEMY minion here does not count as an allied minion');

// ---------------------------------------------------------------------------
// DOT_042 Countess Zaleska — 1 blood less per location you control
// ---------------------------------------------------------------------------
$p1Characters = []; $p2Characters = [];
$inHand = new Hand('DOT_042', 'Hand', 1);
$printed = intval(CardCost('DOT_042'));

$gLocations = [$location('DOT_016', 1, 0), $location('DOT_015', 2, 0)];
$check(HellbreakCardPlayCost(1, 'DOT_042', $inHand) === $printed,
    'DOT_042: controlling no location costs full price');
$gLocations = [$location('DOT_016', 1, 1), $location('DOT_015', 2, 0)];
$check(HellbreakCardPlayCost(1, 'DOT_042', $inHand) === $printed - 1, 'DOT_042: one controlled location, -1');
$gLocations = [$location('DOT_016', 1, 1), $location('DOT_015', 2, 1)];
$check(HellbreakCardPlayCost(1, 'DOT_042', $inHand) === $printed - 2, 'DOT_042: two controlled locations, -2');

$gLocations = [$location('DOT_016', 1, 2), $location('DOT_015', 2, 2)];
$check(HellbreakCardPlayCost(1, 'DOT_042', $inHand) === $printed,
    'DOT_042 NEGATIVE: locations the OPPONENT controls do not discount her');

// ---------------------------------------------------------------------------
// DOT_044 Count Alucard — 2 blood less while you have initiative
// ---------------------------------------------------------------------------
$alucard = new Hand('DOT_044', 'Hand', 1);
$printed = intval(CardCost('DOT_044'));
$gInitiativePlayer = 1;
$check(HellbreakCardPlayCost(1, 'DOT_044', $alucard) === $printed - 2, 'DOT_044: with initiative, -2');
$gInitiativePlayer = 2;
$check(HellbreakCardPlayCost(1, 'DOT_044', $alucard) === $printed,
    'DOT_044 NEGATIVE: without initiative, full price');

// ---------------------------------------------------------------------------
// SCOPE, the shared failure: a cost modifier must discount ITS OWN card only.
// Both cost cards are put in play at once, in the state where each would discount.
// ---------------------------------------------------------------------------
$gInitiativePlayer = 1;
$gLocations = [$location('DOT_016', 1, 1), $location('DOT_015', 2, 1)];
$p1Characters = [$minion('DOT_042', 1), $minion('DOT_044', 1)];
$bystander = new Hand('DOT_025', 'Hand', 1);
$check(HellbreakCardPlayCost(1, 'DOT_025', $bystander) === intval(CardCost('DOT_025')),
    'SCOPE: with both cost-reducers IN PLAY and active, an unrelated card is NOT discounted');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
