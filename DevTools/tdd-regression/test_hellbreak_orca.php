<?php

// DOT_092 Orca, Timeworn Trawler — the last GAMA card that needed the turn-effect system.
//
//   "Lurking Action — Pay 1 malice and exhaust this card: Attack with a character.
//    It gets +2 combat this attack. If it is a Sailor, gain 1 blood."
//
// 2026-09-16: ImplementationPlan carried "Orca's nested attack action" as an open item. Three things
// had to exist before it could be written, and only one was missing:
//   - a nested attack        → HellbreakChooseAttacker() was already a callable entry point
//   - a side-restricted gate → HellbreakMonsterSide() already existed, though no card used it
//   - "+2 combat THIS ATTACK"→ nothing. That is what the turn-effect system was built for.
//
// The gate is four conditions deep (lurking monster, ready card, payable malice, once per round) and
// each is tested alone, because a prereq that is false for the wrong reason looks identical to one
// that is false for the right one.

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
       $p1Deck, $p2Deck, $p1Crypt, $p2Crypt, $p1Hand, $p2Hand, $p1Malice, $p2Malice,
       $p1Blood, $gLocations, $gTurnNumber, $gCurrentPhase, $gActionSequence,
       $gTurnPlayer, $gSlumberPlayer, $p1DecisionQueue, $p2DecisionQueue, $gDecisionQueueVariables,
       $playerID;
// ⚠ GetZoneObject() resolves the "my"/"their" prefix of an mzID from global $playerID. A hand-built
// board that does not set it makes EVERY "myAssets-0" lookup return NULL, so a prereq reads false for
// a reason that has nothing to do with the card. Any test that resolves a seat-relative mzID needs it.
$playerID = 1;
$gTurnNumber = 1; $gCurrentPhase = 'HORROR'; $gActionSequence = 1;
$gTurnPlayer = 1; $gSlumberPlayer = 0;
$p1DecisionQueue = []; $p2DecisionQueue = []; $gDecisionQueueVariables = '';
$p1Crypt = []; $p2Crypt = []; $p1Hand = []; $p2Hand = []; $p1Deck = []; $p2Deck = [];
$p1Malice = 1; $p2Malice = 0; $p1Blood = 0;

$uid = 4000;
$minion = function(string $cardID, int $player, int $slot = 1) use (&$uid) {
    $obj = new Characters($cardID . ' 2 0 ' . $player . ' ' . $player . ' ' . $slot . ' ' . (++$uid) . ' - -', 'Characters', $player);
    $obj->UniqueID = $uid; $obj->LocationSlot = $slot;
    return $obj;
};
$monster = function(string $cardID, int $player, string $side) use (&$uid) {
    $obj = new Monster($cardID . ' 2 ' . $side . ' ' . $player . ' ' . $player . ' ' . (++$uid) . ' - -', 'Monster', $player);
    $obj->UniqueID = $uid; $obj->Side = $side;
    return $obj;
};
$loc = function(string $cardID, int $slot) use (&$uid) {
    $obj = new Locations($cardID . ' ' . $slot . ' 1 0 0 0 9 ' . (++$uid), 'Locations', 0);
    $obj->Slot = $slot; $obj->UniqueID = $uid;
    return $obj;
};

$orca = new Assets('DOT_092 2 1 1 4900 0 - -', 'Assets', 1);
$orca->UniqueID = 4900; $orca->Status = 2;
$gLocations = [$loc('DOT_020', 1), $loc('DOT_013', 2)];
$p1Monster = [$monster('DOT_006', 1, 'LURKING')];
$p2Monster = [$monster('DOT_001', 2, 'LURKING')];
$p1Characters = [$minion('DOT_110', 1)];   // Shark Spotter — a Sailor
$p2Characters = [$minion('DOT_105', 2)];   // something to attack
$p1Assets = [$orca]; $p2Assets = [];

$prereq = function(int $player = 1) use ($orca) {
    $fn = $GLOBALS['activateAbilityPrereqs']['DOT_092:0'] ?? null;
    return is_callable($fn) ? boolval($fn($player, 'myAssets-0', 0)) : null;
};

$check(isset($GLOBALS['activateAbilityAbilities']['DOT_092:0']), 'DOT_092 has a compiled ActivateAbility macro');
$check($prereq() === true, 'offered: lurking monster, ready Orca, 1 malice, a legal attacker');

// ---------------------------------------------------------------------------
// Each gate alone
// ---------------------------------------------------------------------------
$p1Monster = [$monster('DOT_006', 1, 'UNLEASHED')];
$check($prereq() === false, 'NEGATIVE: not offered while the monster is UNLEASHED — "Lurking Action"');
$p1Monster = [$monster('DOT_006', 1, 'LURKING')];
$check($prereq() === true, 'offered again once the monster is lurking');

$p1Malice = 0;
$check($prereq() === false, 'NEGATIVE: not offered without the 1 malice to pay');
$p1Malice = 1;

$orca->Status = 1; // exhausted
$check($prereq() === false, 'NEGATIVE: not offered while the card is already exhausted');
$orca->Status = 2;

$savedEnemies = $p2Characters;
$p2Characters = []; $p2Monster = [];
$check($prereq() === false, 'NEGATIVE: not offered with nothing to attack');
$p2Characters = $savedEnemies; $p2Monster = [$monster('DOT_001', 2, 'LURKING')];

HellbreakMarkObjectAbilityUsedThisRound($orca, 1, 0);
$check($prereq() === false, 'NEGATIVE: not offered twice in a round — "Limit once per round"');

// ---------------------------------------------------------------------------
// The +2 combat rides the attack, and is dated to THIS action
// ---------------------------------------------------------------------------
$gActionSequence = 9;
$attacker = $p1Characters[0];
$printed = intval(CardCombat('DOT_110'));
$check(HellbreakCardCombatValue('DOT_110', $attacker) === $printed, 'baseline: the attacker has its printed combat');

HellbreakAddTurnEffect($attacker, 'COMBAT', [2], HELLBREAK_DUR_ATTACK, 'DOT_092');
$check(HellbreakCardCombatValue('DOT_110', $attacker) === $printed + 2, 'the +2 applies during the attack');

// ⚠ The whole point of attack duration: it must NOT survive into the next action.
$gActionSequence = 10;
$check(HellbreakCardCombatValue('DOT_110', $attacker) === $printed,
    'the +2 is gone in the NEXT action — it was "this attack" only');
HellbreakExpireTurnEffects();
$check(count($attacker->TurnEffects) === 0, 'and the sweep clears it from the row');

// ---------------------------------------------------------------------------
// "If it is a Sailor" — the trait rider
// ---------------------------------------------------------------------------
$check(HellbreakObjectHasTrait($attacker, 'Sailor', 1),
    'Shark Spotter is a Sailor, so the blood rider applies to it');
$notSailor = $minion('DOT_105', 1); // Barracuda — Creature, Fish
$check(!HellbreakObjectHasTrait($notSailor, 'Sailor', 1),
    'NEGATIVE: a non-Sailor attacker does not earn the blood');

// The buff is unconditional; only the blood is gated on the trait.
$p1Characters = [$notSailor];
HellbreakAddTurnEffect($notSailor, 'COMBAT', [2], HELLBREAK_DUR_ATTACK, 'DOT_092');
$check(HellbreakCardCombatValue('DOT_105', $notSailor) === intval(CardCombat('DOT_105')) + 2,
    'a non-Sailor attacker still gets the +2 — only the blood is conditional');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
