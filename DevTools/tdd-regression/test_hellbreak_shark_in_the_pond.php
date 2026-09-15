<?php

// DOT_136 Shark in the Pond — the last unimplemented GAMA card.
//
//   "Lurking Action — Terrify an enemy minion unless its controller says 'it's a prank.'
//    If they do, play a non-unique Shark minion from your hand for free at the same location."
//
// 2026-09-16: this is the set's only card where the OPPONENT makes a decision inside your ability,
// and it is a choice of poisons — refuse and your minion is terrified, call the prank and a free
// Shark lands beside it. The branch is the card, so both sides of it are pinned here.
//
// Two pieces had to be built: the opponent decision (the await DSL turned out to support it — its
// playerVar feeds straight into AddDecision, so `await $opponent.YesNo(...)` queues for the other
// seat) and playing a real card from HAND for free. The existing free-play helpers only play from
// the health stack, and HellbreakPlayCard always computes and charges a cost.

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
       $p1Deck, $p2Deck, $p1Crypt, $p2Crypt, $p1Hand, $p2Hand, $p1Blood, $p1Malice,
       $gLocations, $gTurnNumber, $gCurrentPhase, $gActionSequence, $gTurnPlayer,
       $p1DecisionQueue, $p2DecisionQueue, $gDecisionQueueVariables, $playerID;
$playerID = 1;  // GetZoneObject resolves my/their from this
$gTurnNumber = 1; $gCurrentPhase = 'HORROR'; $gActionSequence = 1; $gTurnPlayer = 1;
$p1DecisionQueue = []; $p2DecisionQueue = []; $gDecisionQueueVariables = '';
$p1Crypt = []; $p2Crypt = []; $p1Deck = []; $p2Deck = []; $p2Hand = [];
$p1Assets = []; $p2Assets = []; $p1Blood = 0; $p1Malice = 0;

$uid = 6000;
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
$gLocations = [$loc('DOT_020', 1), $loc('DOT_013', 2)];
$p1Monster = [$monster('DOT_006', 1, 'LURKING')];
$p2Monster = [$monster('DOT_001', 2, 'LURKING')];

$prereq = function(int $player = 1) {
    $fn = $GLOBALS['playedPrereqs']['DOT_136:0'] ?? null;
    return is_callable($fn) ? boolval($fn($player, 'myCrypt-0', 'Hand', 1)) : null;
};

// ---------------------------------------------------------------------------
// 1. The gate
// ---------------------------------------------------------------------------
$check(isset($GLOBALS['playedAbilities']['DOT_136:0']), 'DOT_136 has a compiled macro');
$p1Characters = []; $p2Characters = [$minion('DOT_105', 2, 1)];
$check($prereq() === true, 'offered: lurking monster and an enemy minion to aim at');

$p1Monster = [$monster('DOT_006', 1, 'UNLEASHED')];
$check($prereq() === false, 'NEGATIVE: not offered while UNLEASHED — "Lurking Action"');
$p1Monster = [$monster('DOT_006', 1, 'LURKING')];

$p2Characters = [];
$check($prereq() === false, 'NEGATIVE: not offered with no enemy minion');
// "An ENEMY minion" — your own minions are not targets.
$p1Characters = [$minion('DOT_105', 1, 1)];
$check($prereq() === false, 'NEGATIVE: an ALLIED minion is not a legal target — the text says enemy');
$p2Characters = [$minion('DOT_105', 2, 1)];
$check($prereq() === true, 'offered again once an enemy minion exists');

// ---------------------------------------------------------------------------
// 2. The opponent's decision goes to the OPPONENT
// ---------------------------------------------------------------------------
$generated = file_get_contents('./HellbreakSim/GeneratedCode/GeneratedMacroCode.php');
$handler = strstr($generated, '$customDQHandlers["DOT_136:0:Played-1"]');
$handler = $handler === false ? '' : substr($handler, 0, strpos($handler, "\n};"));
$check(strpos($handler, 'AddDecision($opponent, "YESNO"') !== false,
    'the "it\'s a prank" question is queued for the OPPONENT, not the caster');
$check(strpos($handler, 'AddDecision($player, "YESNO"') === false,
    'NEGATIVE: it is not queued for the caster — that would let you answer your own question');

// ---------------------------------------------------------------------------
// 3. The Shark pool: minions, Shark trait, NON-UNIQUE
// ---------------------------------------------------------------------------
$p1Hand = [
    new Hand('DOT_109', 'Hand', 1),  // Rogue Shark      — Shark, non-unique  ✓
    new Hand('DOT_129', 'Hand', 1),  // Jaws Eating Machine — Shark, UNIQUE   ✗
    new Hand('DOT_027', 'Hand', 1),  // Renfield         — Human minion       ✗
    new Hand('DOT_052', 'Hand', 1),  // Drain Life       — an Event           ✗
    new Hand('DOT_119', 'Hand', 1),  // Ravenous Predator — Shark, non-unique ✓
];
$pool = HellbreakHandMinionTargets(1, 'Shark', true);
$check(count($pool) === 2, 'exactly the two non-unique Shark minions are offered (got ' . count($pool) . ')');
$check(in_array('myHand-0', $pool, true) && in_array('myHand-4', $pool, true),
    'the pool is Rogue Shark and Ravenous Predator');
$check(!in_array('myHand-1', $pool, true),
    'NEGATIVE: a UNIQUE Shark is excluded — the text says non-unique');
$check(!in_array('myHand-2', $pool, true), 'NEGATIVE: a non-Shark minion is excluded');
$check(!in_array('myHand-3', $pool, true), 'NEGATIVE: an Event is excluded — it must be a minion');
$check(count(HellbreakHandMinionTargets(1, 'Shark', false)) === 3,
    'without the non-unique filter the unique Jaws joins them, making three');

// ---------------------------------------------------------------------------
// 4. Playing from hand for free
// ---------------------------------------------------------------------------
$p1Characters = []; $p2Characters = [$minion('DOT_105', 2, 2)];
$p1Hand = [new Hand('DOT_109', 'Hand', 1)];
$p1Blood = 3; $p1Malice = 3;
$handBefore = count($p1Hand);

$check(HellbreakPlayMinionFromHandForFree(1, 'myHand-0', 2) === true, 'the Shark is played from hand');
$check(count($p1Hand) === $handBefore - 1, 'it left hand');
$check(count($p1Characters) === 1 && strval($p1Characters[0]->CardID) === 'DOT_109', 'it is in play');
$check(intval($p1Characters[0]->LocationSlot) === 2,
    'it arrives at the location it was told to — "at the same location" as the target');
$check(intval($p1Blood) === 3 && intval($p1Malice) === 3,
    'FOR FREE: neither blood nor malice was spent');
$check(intval($p1Characters[0]->Status) === 1,
    'it enters EXHAUSTED like any other minion (Rogue Shark has no Fearsome)');

// NEGATIVE: an Event cannot be played by the minion helper.
$p1Hand = [new Hand('DOT_052', 'Hand', 1)];
$check(HellbreakPlayMinionFromHandForFree(1, 'myHand-0', 1) === false,
    'NEGATIVE: the helper refuses a non-minion');
$check(count($p1Hand) === 1, 'and the refused card stays in hand');

// NEGATIVE: an unknown location slot is refused rather than dropping the card into limbo.
$p1Hand = [new Hand('DOT_109', 'Hand', 1)];
$check(HellbreakPlayMinionFromHandForFree(1, 'myHand-0', 7) === false,
    'NEGATIVE: an invalid location slot is refused');
$check(count($p1Hand) === 1, 'and that card stays in hand too');

// A Fearsome minion enters READY — the free play keeps the normal entry rules.
$p1Characters = [];
$p1Hand = [new Hand('DOT_028', 'Hand', 1)]; // Transylvanian Wolf — Fearsome
HellbreakPlayMinionFromHandForFree(1, 'myHand-0', 1);
$check(count($p1Characters) === 1 && intval($p1Characters[0]->Status) === 2,
    'a FEARSOME minion still enters ready — free play does not skip entry rules');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
