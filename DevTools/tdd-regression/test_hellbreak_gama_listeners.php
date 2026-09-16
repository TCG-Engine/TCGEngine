<?php

// The last five implemented GAMA-deck cards: two location listeners, a combat listener, a scheme
// ability and an activated location ability.
//
//   DOT_013 Amity Harbor      Take Control — Deal 2 damage to a monster.
//   DOT_020 North Beach       Action — Pay 1 malice: Deal 1 damage to a minion here. Limit once per round per player.
//   DOT_040 Lucy Weston       When you take control of this location, you may deal 2 damage to a minion here.
//   DOT_080 Roughtail Stingray When this card deals combat damage to a minion, kill that minion.
//   DOT_112 Larry Vaughn      Scheme — You may pay 1 malice. If you do, deal 1 indirect damage to a player.
//
// 2026-09-16: implemented long ago, never tested. All five were read against their printed text in
// this pass and all five matched.
//
// ⚠ THE FAILURE THIS FILE EXISTS FOR: a listener declares the ZONES it is live in, and
// DispatchMacroListeners only runs it for an object actually sitting in one of them. Get the zone
// wrong and the card NEVER FIRES — no error, no wrong answer, just silence. It is the one defect that
// a body-level test cannot see, because the body is perfect and simply never runs. The zone of each
// listener is therefore asserted directly, against what the card IS: Amity Harbor is a Location and
// lives in Locations; Lucy Weston and the Stingray are minions and live in Characters.

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
       $gLocations, $gTurnNumber, $gCurrentPhase, $p1DecisionQueue, $p2DecisionQueue,
       $gDecisionQueueVariables;
$gTurnNumber = 1; $gCurrentPhase = 'HORROR';
// A kill dispatches MinionKilled, which QUEUES a decision — so both queues must exist or
// AddDecision fatals on a null. Any test that actually removes a card from play needs these.
$p1DecisionQueue = []; $p2DecisionQueue = []; $gDecisionQueueVariables = '';
$p1Assets = []; $p2Assets = []; $p1Crypt = []; $p2Crypt = [];
$p1Hand = []; $p2Hand = []; $p1Deck = []; $p2Deck = [];
$p1Malice = 0; $p2Malice = 0;

$uid = 1300;
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
$loc = function(string $cardID, int $slot, int $controller = 0) use (&$uid) {
    $obj = new Locations($cardID . ' ' . $slot . ' 1 ' . $controller . ' 0 0 9 ' . (++$uid), 'Locations', 0);
    $obj->Slot = $slot; $obj->Controller = $controller; $obj->UniqueID = $uid;
    return $obj;
};
$p1Monster = [$monster('DOT_001', 1)];
$p2Monster = [$monster('DOT_006', 2)];
$gLocations = [$loc('DOT_013', 1), $loc('DOT_020', 2)];

// ---------------------------------------------------------------------------
// 1. Listener registration — the silent-failure axis
// ---------------------------------------------------------------------------
$zonesOf = fn(string $macro, string $cardID) => $GLOBALS['macroListenerZones'][$macro][$cardID . ':0'] ?? null;

$check($zonesOf('LocationTaken', 'DOT_013') === ['Locations'],
    'DOT_013 listens from Locations — it IS a location, and would never fire from anywhere else');
$check($zonesOf('LocationTaken', 'DOT_040') === ['Characters'],
    'DOT_040 listens from Characters — she is a minion on the board');
$check($zonesOf('DamageDealt', 'DOT_080') === ['Characters'],
    'DOT_080 listens from Characters');

// A listener registered under the wrong MACRO is the same silent failure by another route.
$check(isset($GLOBALS['macroListenerAbilities']['LocationTaken']['DOT_013:0']), 'DOT_013 is registered on LocationTaken');
$check(isset($GLOBALS['macroListenerAbilities']['LocationTaken']['DOT_040:0']), 'DOT_040 is registered on LocationTaken');
$check(isset($GLOBALS['macroListenerAbilities']['DamageDealt']['DOT_080:0']), 'DOT_080 is registered on DamageDealt');
$check(!isset($GLOBALS['macroListenerAbilities']['MinionKilled']['DOT_080:0']),
    'DOT_080 NEGATIVE: it is a DamageDealt listener, not a MinionKilled one');

// ---------------------------------------------------------------------------
// 2. DOT_080 Roughtail Stingray — invoke the listener for real
// ---------------------------------------------------------------------------
$stingray = $minion('DOT_080', 1, 1);
$victim = $minion('DOT_105', 2, 1, 1);
$p1Characters = [$stingray]; $p2Characters = [$victim];
$p2Crypt = [];

$fire = function(string $damageType) use (&$p1Characters, &$p2Characters) {
    DecisionQueueController::StoreVariable('listenerMZ', 'myCharacters-0');
    DecisionQueueController::StoreVariable('listenerCardID', 'DOT_080');
    DecisionQueueController::StoreVariable('eventMacro', 'DamageDealt');
    DecisionQueueController::StoreVariable('eventPlayer', 1);
    DecisionQueueController::StoreVariable('eventParams', []);
    DecisionQueueController::StoreVariable('event_sourceMZ', 'myCharacters-0');
    DecisionQueueController::StoreVariable('event_targetMZ', 'theirCharacters-0');
    DecisionQueueController::StoreVariable('event_amount', 1);
    DecisionQueueController::StoreVariable('event_damageType', $damageType);
    $GLOBALS['macroListenerAbilities']['DamageDealt']['DOT_080:0'](1);
};

$fire('COMBAT');
$check(count($p2Characters) === 0, 'DOT_080: combat damage to a minion KILLS it outright');
$check(count($p2Crypt) === 1 && strval($p2Crypt[0]->CardID ?? '') === 'DOT_105',
    'DOT_080: the killed minion goes to its OWNER\'s crypt');

// NEGATIVE: the damage type gate. "deals COMBAT damage" excludes ability and indirect damage —
// without this gate the Stingray would kill anything it ever pinged.
$p2Characters = [$minion('DOT_105', 2, 1, 1)]; $p2Crypt = [];
$fire('ABILITY');
$check(count($p2Characters) === 1,
    'DOT_080 NEGATIVE: ABILITY damage does not kill — "combat damage" is load-bearing');
$p2Characters = [$minion('DOT_105', 2, 1, 1)]; $p2Crypt = [];
$fire('INDIRECT');
$check(count($p2Characters) === 1, 'DOT_080 NEGATIVE: INDIRECT damage does not kill either');

// NEGATIVE: the SOURCE must be this card. Another minion dealing combat damage must not trigger it.
$p1Characters = [$stingray, $minion('DOT_151', 1, 1)];
$p2Characters = [$minion('DOT_105', 2, 1, 1)]; $p2Crypt = [];
DecisionQueueController::StoreVariable('listenerMZ', 'myCharacters-0');
DecisionQueueController::StoreVariable('listenerCardID', 'DOT_080');
DecisionQueueController::StoreVariable('eventPlayer', 1);
DecisionQueueController::StoreVariable('eventParams', []);
DecisionQueueController::StoreVariable('event_sourceMZ', 'myCharacters-1'); // the OTHER minion
DecisionQueueController::StoreVariable('event_targetMZ', 'theirCharacters-0');
DecisionQueueController::StoreVariable('event_amount', 1);
DecisionQueueController::StoreVariable('event_damageType', 'COMBAT');
$GLOBALS['macroListenerAbilities']['DamageDealt']['DOT_080:0'](1);
$check(count($p2Characters) === 1,
    'DOT_080 NEGATIVE: combat damage from ANOTHER minion does not kill — "this card" is load-bearing');

// ---------------------------------------------------------------------------
// 3. DOT_020 North Beach — malice cost, once per round per player, minions HERE
// ---------------------------------------------------------------------------
$prereq020 = function(int $player, string $mzID, int $abilityIndex = 0) {
    $fn = $GLOBALS['activateAbilityPrereqs']['DOT_020:0'] ?? null;
    return is_callable($fn) ? boolval($fn($player, $mzID, $abilityIndex)) : null;
};
$gLocations = [$loc('DOT_013', 1), $loc('DOT_020', 2)];
$p1Characters = []; $p2Characters = [$minion('DOT_105', 2, 2)];
$p1Malice = 1;
$check($prereq020(1, 'Locations-1') === true, 'DOT_020: offered with malice and a minion here');
$p1Malice = 0;
$check($prereq020(1, 'Locations-1') === false,
    'DOT_020 NEGATIVE: without malice it is not offered — the cost gate is load-bearing');
$p1Malice = 1;
$p2Characters = [$minion('DOT_105', 2, 1)]; // minion at the OTHER location
$check($prereq020(1, 'Locations-1') === false,
    'DOT_020 NEGATIVE: a minion at the OTHER location is not "here"');

// ---------------------------------------------------------------------------
// 4. DOT_112 Larry Vaughn — Scheme half needs payable malice and both monsters
// ---------------------------------------------------------------------------
$prereq112 = function(int $player, string $mzID, int $slot) {
    $fn = $GLOBALS['schemeStartedPrereqs']['DOT_112:0'] ?? null;
    return is_callable($fn) ? boolval($fn($player, $mzID, $slot)) : null;
};
$p1Characters = [$minion('DOT_112', 1, 1)];
$p1Malice = 1;
$check($prereq112(1, 'myCharacters-0', 1) === true, 'DOT_112: Scheme offered with malice and both monsters');
$p1Malice = 0;
$check($prereq112(1, 'myCharacters-0', 1) === false,
    'DOT_112 NEGATIVE: Scheme not offered without malice to pay');
// ⚠ Its Jumpscare half has NO malice cost, so it stays available when the Scheme half cannot fire.
$jump112 = $GLOBALS['jumpscareUsedPrereqs']['DOT_112:0'] ?? null;
$check(is_callable($jump112) && boolval($jump112(1, 'DOT_112', 1)) === true,
    'DOT_112: the JUMPSCARE half is still offered at 0 malice — only the Scheme half costs one');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
