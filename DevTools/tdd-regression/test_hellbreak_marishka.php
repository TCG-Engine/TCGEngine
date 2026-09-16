<?php

// DOT_161 Marishka, Cunning Bride, and the two shared helpers behind her second clause.
//
//   "This card costs 1 blood less to play for each allied Bride minion.
//    Attack / Scheme — You may discard 1 card from the top of your deck and collect its resource icons."
//
// 2026-09-16: Marishka had no reviewed card data at all — ImplementationPlan carried "resolve the
// missing Marishka source image/rules" as a blocker — so she sat blank in the GAMA Dracula deck.
// Her source image turned out to be in the research mirror; the transcription was verified icon by
// icon against cards whose values were already known (HAUNT vs PROWL from DOT_029/DOT_185, the green
// malice icon from DOT_025).
//
// What this pins:
//   1. The cost reduction counts ALLIED BRIDE MINIONS, and applies to MARISHKA ONLY. A PlayCostModifier
//      with no prereq discounts every card in hand — the cheapest possible bug and invisible in a
//      single-card test.
//   2. Discarding the top of a deck is not a draw and not a failed draw: the card reaches the crypt
//      and no monster damage is dealt.
//   3. "Collect its resource icons" takes blood and malice from the card's printed resource bar.

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

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

// ---------------------------------------------------------------------------
// Reviewed card data — the transcription itself, read back through the engine
// ---------------------------------------------------------------------------
$check(CardName('DOT_161') === 'Marishka, Cunning Bride', 'DOT_161 is Marishka, Cunning Bride');
$check(intval(CardCost('DOT_161')) === 4, 'she costs 4 blood');
$check(intval(CardCombat('DOT_161')) === 1 && intval(CardHealth('DOT_161')) === 4, 'she is 1 combat / 4 health');
$check(CardUnique('DOT_161') == true, 'she is unique');
$resources = HellbreakCardResources('DOT_161');
$check(intval($resources['blood']) === 1 && intval($resources['malice']) === 1 && intval($resources['draw']) === 0,
    'her resource bar is 1 blood, 1 malice, 0 draw');
$check(HellbreakCardHasTrait('DOT_161', 'Bride'), 'she is a Bride');
$scheme = json_decode((string)CardScheme('DOT_161'), true);
$check(is_array($scheme) && count($scheme) === 1 && ($scheme[0]['type'] ?? '') === 'HAUNT' && intval($scheme[0]['value'] ?? 0) === 1,
    'her scheme bar is HAUNT 1 (the ghost icon, matched against DOT_029)');

// ---------------------------------------------------------------------------
// Board
// ---------------------------------------------------------------------------
global $p1Monster, $p2Monster, $p1Characters, $p2Characters, $p1Assets, $p2Assets,
       $p1Deck, $p2Deck, $p1Crypt, $p2Crypt, $p1Blood, $p2Blood, $p1Malice, $p2Malice,
       $p1Hand, $gLocations, $gTurnNumber;
$gTurnNumber = 1; $gLocations = [];
$p1Assets = []; $p2Assets = []; $p1Crypt = []; $p2Crypt = []; $p1Hand = [];
$p1Blood = 0; $p2Blood = 0; $p1Malice = 0; $p2Malice = 0;
$p1Monster = []; $p2Monster = [];

$uid = 500;
$minion = function(string $cardID, int $player) use (&$uid) {
    $obj = new Characters($cardID . ' 2 0 ' . $player . ' ' . $player . ' 1 ' . (++$uid) . ' - -', 'Characters', $player);
    $obj->UniqueID = $uid;
    return $obj;
};
$deckCard = function(string $cardID, int $player) {
    return new Deck($cardID, 'Deck', $player);
};

// ---------------------------------------------------------------------------
// 1. The cost reduction — counts allied Brides, applies to Marishka alone
// ---------------------------------------------------------------------------
$p1Characters = []; $p2Characters = [];
$marishkaInHand = new Hand('DOT_161', 'Hand', 1);
$printed = intval(CardCost('DOT_161'));

$check(HellbreakCardPlayCost(1, 'DOT_161', $marishkaInHand) === $printed,
    "with no allied Bride she costs her printed {$printed}");

$p1Characters = [$minion('DOT_165', 1)]; // Verona, a Bride
$check(HellbreakCardPlayCost(1, 'DOT_161', $marishkaInHand) === $printed - 1,
    'one allied Bride makes her cost 1 less');

$p1Characters = [$minion('DOT_165', 1), $minion('DOT_169', 1)]; // Verona + The Bride
$check(HellbreakCardPlayCost(1, 'DOT_161', $marishkaInHand) === $printed - 2,
    'two allied Brides make her cost 2 less');

// NEGATIVE: a non-Bride ally must not discount her.
$p1Characters = [$minion('DOT_027', 1)]; // Renfield — Human, not a Bride
$check(HellbreakCardPlayCost(1, 'DOT_161', $marishkaInHand) === $printed,
    'NEGATIVE: a non-Bride ally does NOT discount her');

// NEGATIVE: an ENEMY Bride is not "allied".
$p1Characters = []; $p2Characters = [$minion('DOT_165', 2)];
$check(HellbreakCardPlayCost(1, 'DOT_161', $marishkaInHand) === $printed,
    'NEGATIVE: an ENEMY Bride does NOT discount her');

// NEGATIVE: the discount is hers alone. Without the prereq that pins subject === source, a
// PlayCostModifier reduces the cost of EVERY card its controller tries to play.
$p1Characters = [$minion('DOT_165', 1), $minion('DOT_169', 1)];
$p2Characters = [];
$otherInHand = new Hand('DOT_025', 'Hand', 1);
$check(HellbreakCardPlayCost(1, 'DOT_025', $otherInHand) === intval(CardCost('DOT_025')),
    'NEGATIVE: another card in hand is NOT discounted while she sits in hand');

// ⚠ THE DISCRIMINATING BOARD. A card in HAND is not a modifier source — only the subject being
// priced and the objects IN PLAY are — so the check above passes even with the prereq deleted.
// The prereq only earns its keep once Marishka is ON THE BOARD: she is then an active source for
// every pricing question, and without "subject === source" she discounts the whole hand.
$p1Characters = [$minion('DOT_161', 1), $minion('DOT_165', 1)]; // Marishka in play, plus a Bride
$check(HellbreakCardPlayCost(1, 'DOT_025', $otherInHand) === intval(CardCost('DOT_025')),
    'NEGATIVE: with Marishka IN PLAY, another card in hand is still NOT discounted');

// ---------------------------------------------------------------------------
// 2. Discarding the top of a deck
// ---------------------------------------------------------------------------
$p1Deck = [$deckCard('DOT_151', 1), $deckCard('DOT_152', 1)];
$p1Crypt = [];
$discarded = HellbreakDiscardTopOfDeck(1);
$check($discarded === 'DOT_151', 'the TOP card is the one discarded');
$check(count($p1Deck) === 1, 'the deck shrinks by exactly one');
$check(count($p1Crypt) === 1 && strval($p1Crypt[0]->CardID ?? '') === 'DOT_151',
    'the discarded card is in its owner\'s crypt');
$check(count($p1Hand) === 0, 'NEGATIVE: a discard is not a draw — nothing reached hand');

$p1Deck = [];
$check(HellbreakDiscardTopOfDeck(1) === '', 'an empty deck discards nothing and reports it');

// ---------------------------------------------------------------------------
// 3. Collecting the discarded card's resource icons
// ---------------------------------------------------------------------------
$p1Blood = 0; $p1Malice = 0;
$bat = HellbreakCardResources('DOT_025'); // 1 blood, 1 malice
$collected = HellbreakCollectCardResourceIcons(1, 'DOT_025');
$check(intval($p1Blood) === intval($bat['blood']), 'collecting adds the card\'s blood icons');
$check(intval($p1Malice) === intval($bat['malice']), 'collecting adds the card\'s malice icons');
$check(intval($collected['blood']) === intval($bat['blood']), 'the collected amount is reported back');

// QUANTITY DISCRIMINATION: collecting must read the DISCARDED card's own bar, not a fixed amount.
// Verona is 2 blood / 0 malice against the Bat's 1 / 1, so a hardcoded "gain 1 blood 1 malice" —
// or reading the wrong card — fails here and nowhere else.
// (There is no card in DOT with an empty resource bar, so "collects nothing" is untestable.)
$p1Blood = 0; $p1Malice = 0;
$verona = HellbreakCardResources('DOT_165');
HellbreakCollectCardResourceIcons(1, 'DOT_165');
$check(intval($p1Blood) === intval($verona['blood']) && intval($p1Blood) !== intval($bat['blood']),
    'collecting reads the discarded card\'s OWN blood (Verona 2, not the Bat\'s 1)');
$check(intval($p1Malice) === intval($verona['malice']) && intval($verona['malice']) !== intval($bat['malice']),
    'collecting reads the discarded card\'s OWN malice (Verona 0, not the Bat\'s 1)');

// ---------------------------------------------------------------------------
// 4. Her abilities are wired to BOTH halves of "Attack / Scheme —"
// ---------------------------------------------------------------------------
$check(isset($GLOBALS['attackDeclaredAbilities']['DOT_161:0']), 'the Attack half has a compiled macro');
$check(isset($GLOBALS['schemeStartedAbilities']['DOT_161:0']), 'the Scheme half has a compiled macro');
$check(isset($GLOBALS['playCostModifierAbilities']['DOT_161:0']), 'the cost reduction has a compiled macro');
$check(isset($GLOBALS['playCostModifierPrereqs']['DOT_161:0']),
    'the cost reduction has a PREREQ — without it every card gets discounted');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
