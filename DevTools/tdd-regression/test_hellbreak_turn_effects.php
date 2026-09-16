<?php

// Turn effects — temporary stat changes, granted keywords/traits and markers.
//
// 2026-09-16: HellbreakSim had NO duration mechanism. The TurnEffects field was declared on all four
// card-bearing zones and the generator emitted a full Add/Remove/Has/Clear API for it, but nothing in
// the game read or wrote it, so 23 cards whose text says "this attack", "this phase" or "this round"
// were unimplementable. This is a port of SWUSim's design with its known failure modes fixed.
//
// The four things this file pins, each of which is a real SWUSim bug the port set out to avoid:
//
//   1. AN UNREGISTERED BASE STILL EXPIRES. SWUExpireTurnEffects skips any token whose base is not in
//      its registry, so a forgotten registry row makes the effect PERMANENT — three SWU cards still
//      leak that way today (JTL_077, LOF_209, SEC_185). Here an unknown base defaults to phase
//      duration, so forgetting a row costs an effect its label, never its expiry.
//   2. EVERY BEARING ZONE IS SWEPT. SWU walks only its two arenas, so an effect parked on a leader or
//      base never expires. Hellbreak sweeps Monster, Characters, Assets and Locations.
//   3. STALE EFFECTS DO NOT APPLY EVEN IF NO SWEEP RAN. Each token records the context it was made in
//      and is re-checked on read. SWU's "SWU_DUR_ROUND never expired" family is a duration hook that
//      lives in only one of the phases it spans; a read-side guard cannot have that bug.
//   4. IDENTICAL APPLICATIONS STACK. The generated AddTurnEffects de-dupes on exact string equality,
//      so two "+2 combat" buffs would collapse into one. Each application gets its own ordinal.

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
       $p1Deck, $p2Deck, $p1Crypt, $p2Crypt, $p1Hand, $p2Hand, $gLocations,
       $gTurnNumber, $gCurrentPhase, $gActionSequence, $p1DecisionQueue, $p2DecisionQueue,
       $gDecisionQueueVariables;
$gTurnNumber = 1; $gCurrentPhase = 'HORROR'; $gActionSequence = 5;
$p1DecisionQueue = []; $p2DecisionQueue = []; $gDecisionQueueVariables = '';
$p1Assets = []; $p2Assets = []; $p1Crypt = []; $p2Crypt = [];
$p1Hand = []; $p2Hand = []; $p1Deck = []; $p2Deck = [];
$p1Monster = []; $p2Monster = []; $gLocations = [];

$uid = 2000;
$minion = function(string $cardID, int $player, int $damage = 0) use (&$uid) {
    $obj = new Characters($cardID . ' 2 ' . $damage . ' ' . $player . ' ' . $player . ' 1 ' . (++$uid) . ' - -', 'Characters', $player);
    $obj->UniqueID = $uid; $obj->Damage = $damage; $obj->LocationSlot = 1;
    return $obj;
};
$combatOf = fn($o) => HellbreakCardCombatValue(strval($o->CardID), $o);
$healthOf = fn($o) => HellbreakCardHealthValue(strval($o->CardID), $o);

// ---------------------------------------------------------------------------
// 1. Token grammar round-trip
// ---------------------------------------------------------------------------
$token = HellbreakMakeTurnEffect('COMBAT', [2], HELLBREAK_DUR_ATTACK, 'DOT_092');
$parsed = HellbreakParseTurnEffect($token);
$check($parsed['base'] === 'COMBAT', 'the base parses back');
$check(intval($parsed['params'][0]) === 2, 'the parameter parses back');
$check($parsed['duration'] === HELLBREAK_DUR_ATTACK, 'the duration parses back');
$check($parsed['source'] === 'DOT_092', 'the source card parses back');
$check($parsed['kind'] === 'STAT_COMBAT', 'the registry supplies the kind');
$check(strpos($token, ' ') === false && strpos($token, '~') === false,
    'the token contains no space or tilde — both are zone-row delimiters and would corrupt the save');

// ⚠ SIGIL COLLISION, found by this test during the port: with a DASH as the parameter separator a
// negative amount reads as "COMBAT--1", which splits to ["", "1"] — a -1 debuff silently became a +1
// buff. The separator is a comma for exactly this reason.
$negative = HellbreakParseTurnEffect(HellbreakMakeTurnEffect('COMBAT', [-3], HELLBREAK_DUR_PHASE));
$check(intval($negative['params'][0]) === -3, 'a NEGATIVE parameter survives the round trip intact');
$multi = HellbreakParseTurnEffect(HellbreakMakeTurnEffect('KEYWORD', ['Overkill', 2], HELLBREAK_DUR_PHASE));
$check($multi['params'] === ['Overkill', '2'], 'multiple parameters keep their order and values');

// An unknown base must still carry a duration.
$unknown = HellbreakParseTurnEffect('DOT_999-1@phase:1:HORROR');
$check($unknown['registered'] === false, 'an unregistered base is reported as unregistered');
$check($unknown['duration'] === HELLBREAK_DUR_PHASE,
    'an UNREGISTERED base still expires (SWU makes it permanent — three of its cards still leak)');
$check(HellbreakParseTurnEffect('MYSTERY')['duration'] === HELLBREAK_DUR_PHASE,
    'a bare unknown token with no duration at all defaults to phase, not perm');

// ---------------------------------------------------------------------------
// 2. Stat effects apply
// ---------------------------------------------------------------------------
$unit = $minion('DOT_105', 1);
$p1Characters = [$unit]; $p2Characters = [];
$printedCombat = intval(CardCombat('DOT_105'));
$printedHealth = intval(CardHealth('DOT_105'));

$check($combatOf($unit) === $printedCombat, 'baseline: no effects, printed combat');
HellbreakAddTurnEffect($unit, 'COMBAT', [2], HELLBREAK_DUR_ATTACK, 'DOT_092');
$check($combatOf($unit) === $printedCombat + 2, '+2 combat applies');
HellbreakAddTurnEffect($unit, 'COMBAT', [-1], HELLBREAK_DUR_PHASE, 'DOT_169');
$check($combatOf($unit) === $printedCombat + 1, 'a NEGATIVE amount is a debuff, and nets against the buff');
$check($healthOf($unit) === $printedHealth, 'NEGATIVE: a combat effect does not change health');

HellbreakAddTurnEffect($unit, 'HEALTH', [3], HELLBREAK_DUR_PHASE, 'DOT_169');
$check($healthOf($unit) === $printedHealth + 3, '+3 health applies');

// Combat can never go below zero.
$unit->TurnEffects = [];
HellbreakAddTurnEffect($unit, 'COMBAT', [-99], HELLBREAK_DUR_PHASE);
$check($combatOf($unit) === 0, 'combat clamps at 0, never negative');

// ---------------------------------------------------------------------------
// 3. Stacking — the de-dupe bug
// ---------------------------------------------------------------------------
$stacker = $minion('DOT_105', 1);
$p1Characters = [$stacker];
HellbreakAddTurnEffect($stacker, 'COMBAT', [2], HELLBREAK_DUR_PHASE, 'DOT_143');
HellbreakAddTurnEffect($stacker, 'COMBAT', [2], HELLBREAK_DUR_PHASE, 'DOT_143');
$check(count($stacker->TurnEffects) === 2,
    'two IDENTICAL applications are stored separately, not de-duped');
$check($combatOf($stacker) === $printedCombat + 4,
    'two identical +2 buffs give +4 — they are two continuous effects, not one');
$check($stacker->TurnEffects[0] !== $stacker->TurnEffects[1],
    'the two tokens differ, by ordinal');

// ---------------------------------------------------------------------------
// 4. Granted keywords and traits
// ---------------------------------------------------------------------------
$granted = $minion('DOT_105', 1); // Barracuda: Creature, Fish; no printed keywords
$p1Characters = [$granted];
$check(!HellbreakObjectHasKeyword($granted, 'Overkill'), 'baseline: no Overkill');
HellbreakAddTurnEffect($granted, 'KEYWORD', ['Overkill', 1], HELLBREAK_DUR_PHASE, 'DOT_125');
$check(HellbreakObjectHasKeyword($granted, 'Overkill'), 'a granted keyword is seen by the keyword reader');
$check(!HellbreakObjectHasKeyword($granted, 'Stealth'),
    'NEGATIVE: only the named keyword is granted');

$check(!HellbreakObjectHasTrait($granted, 'Vampire', 1), 'baseline: not a Vampire');
HellbreakAddTurnEffect($granted, 'TRAIT', ['Vampire'], HELLBREAK_DUR_PHASE, 'DOT_171');
$check(HellbreakObjectHasTrait($granted, 'Vampire', 1), 'a granted trait is seen by the trait reader');
$check(HellbreakObjectHasTrait($granted, 'Creature', 1), 'printed traits still work alongside a grant');
$check(!HellbreakObjectHasTrait($granted, 'Undead', 1), 'NEGATIVE: only the named trait is granted');

// ---------------------------------------------------------------------------
// 5. Expiry — by sweep AND by read-side guard
// ---------------------------------------------------------------------------
$subject = $minion('DOT_105', 1);
$p1Characters = [$subject]; $p2Characters = [];
$gActionSequence = 5; $gTurnNumber = 1; $gCurrentPhase = 'HORROR';
HellbreakAddTurnEffect($subject, 'COMBAT', [2], HELLBREAK_DUR_ATTACK);
HellbreakAddTurnEffect($subject, 'COMBAT', [1], HELLBREAK_DUR_PHASE);
HellbreakAddTurnEffect($subject, 'COMBAT', [4], HELLBREAK_DUR_PERM);
$check($combatOf($subject) === $printedCombat + 7, 'all three durations apply while their windows are open');

// The ACTION advances: the attack-duration effect is stale, the others are not.
$gActionSequence = 6;
$check($combatOf($subject) === $printedCombat + 5,
    'READ-SIDE GUARD: the attack effect stops applying the moment the action advances, with no sweep');
$check(count($subject->TurnEffects) === 3, 'it is still stored, though — nothing has swept yet');
$dropped = HellbreakExpireTurnEffects();
$check($dropped === 1 && count($subject->TurnEffects) === 2, 'the sweep then removes exactly the stale one');
$check($combatOf($subject) === $printedCombat + 5, 'the sweep and the guard agree on the result');

// The PHASE advances.
$gCurrentPhase = 'REFRESH_READY';
$check($combatOf($subject) === $printedCombat + 4, 'the phase effect goes stale when the phase changes');
HellbreakExpireTurnEffects();
$check(count($subject->TurnEffects) === 1, 'only the permanent effect survives the phase sweep');

// The ROUND advances — perm still stands.
$gTurnNumber = 2;
$check($combatOf($subject) === $printedCombat + 4, 'a PERM effect survives everything');
$check(HellbreakExpireTurnEffects() === 0, 'a second sweep drops nothing — it is idempotent');

// A round-duration effect goes stale on the round, not the phase.
$rounder = $minion('DOT_105', 1);
$p1Characters = [$rounder];
$gTurnNumber = 2; $gCurrentPhase = 'HORROR';
HellbreakAddTurnEffect($rounder, 'COMBAT', [3], HELLBREAK_DUR_ROUND);
$gCurrentPhase = 'REFRESH_READY';
$check($combatOf($rounder) === $printedCombat + 3,
    'a ROUND effect survives a phase change — the window is the round');
$gTurnNumber = 3;
$check($combatOf($rounder) === $printedCombat, 'and goes stale when the round advances');

// ---------------------------------------------------------------------------
// 6. Every bearing zone is swept
// ---------------------------------------------------------------------------
$gTurnNumber = 5; $gCurrentPhase = 'HORROR'; $gActionSequence = 1;
$char = $minion('DOT_105', 1);
$asset = new Assets('DOT_049 2 1 1 3001 0 - -', 'Assets', 1); $asset->UniqueID = 3001;
$mon = new Monster('DOT_001 2 LURKING 1 1 3002 - -', 'Monster', 1); $mon->UniqueID = 3002;
$loc = new Locations('DOT_016 1 1 1 0 0 9 3003 - -', 'Locations', 0); $loc->UniqueID = 3003; $loc->Slot = 1;
$p1Characters = [$char]; $p1Assets = [$asset]; $p1Monster = [$mon]; $gLocations = [$loc];
$p2Characters = []; $p2Assets = []; $p2Monster = [];
foreach([$char, $asset, $mon, $loc] as $bearer) HellbreakAddTurnEffect($bearer, 'MARKER', ['TEST'], HELLBREAK_DUR_PHASE);
$check(count(HellbreakTurnEffectBearers()) === 4, 'all four bearing zones are collected');
$gCurrentPhase = 'REFRESH_READY';
$check(HellbreakExpireTurnEffects() === 4,
    'the sweep reaches Characters, Assets, MONSTER and LOCATIONS — SWU misses its equivalents of the last two');
foreach([['character', $char], ['asset', $asset], ['monster', $mon], ['location', $loc]] as [$label, $bearer]) {
    $check(count($bearer->TurnEffects) === 0, "the {$label}'s effect was swept");
}

// ---------------------------------------------------------------------------
// 7. An expiring +health buff makes a damaged minion lethal
// ---------------------------------------------------------------------------
$gTurnNumber = 6; $gCurrentPhase = 'HORROR'; $gActionSequence = 1;
$propped = $minion('DOT_105', 1, intval(CardHealth('DOT_105'))); // damage exactly equal to printed health
$p1Characters = [$propped]; $p1Assets = []; $p1Monster = []; $gLocations = [];
HellbreakAddTurnEffect($propped, 'HEALTH', [2], HELLBREAK_DUR_PHASE);
$check($healthOf($propped) === $printedHealth + 2, 'the buff is holding it above its damage');
$check(count($p1Characters) === 1, 'it is alive while the buff holds');
$gCurrentPhase = 'REFRESH_READY';
HellbreakExpireTurnEffects();
$check(count($p1Characters) === 0,
    'once the buff expires the minion is lethally damaged and the sweep defeats it');

// ---------------------------------------------------------------------------
// 8. Serialization survives a round trip with effects attached
// ---------------------------------------------------------------------------
$saved = $minion('DOT_105', 1);
HellbreakAddTurnEffect($saved, 'COMBAT', [2], HELLBREAK_DUR_PHASE, 'DOT_092');
HellbreakAddTurnEffect($saved, 'KEYWORD', ['Overkill', 1], HELLBREAK_DUR_PHASE, 'DOT_125');
$row = trim($saved->Serialize());
$restored = new Characters($row, 'Characters', 1);
$check(count($restored->TurnEffects) === 2, 'both effects survive serialization');
$check(HellbreakTurnEffectStatBonus($restored, 'combat') === 2, 'the stat effect still reads after a reload');
$check(HellbreakTurnEffectGrantsKeyword($restored, 'Overkill') === 1, 'the keyword grant still reads after a reload');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
