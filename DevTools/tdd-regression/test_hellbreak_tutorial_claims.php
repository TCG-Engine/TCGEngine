<?php

// Every factual claim the tutorial makes to the player must be TRUE in the running game.
//
// 2026-09-16: playtesters reported the tutorial did not match the game. An in-depth analysis found the
// lesson text stating things that were false in its own engine, with nothing to catch it:
//
//   - "When prompted, pay 1 malice to ready [the Wolf]" — Transylvanian Wolf has FEARSOME, so it enters
//     ready and that prompt never appears.
//   - "You retain initiative" in round 2 — false. When both players bid nothing the bids tie at 0, and a
//     tie goes to the player WITHOUT initiative, so the opponent takes it.
//   - "Bid Mina Seward if she is available" — with no warning that skipping the bid leaves the Wolf
//     unplayable, because the Wolf needs two Cursed icons and Mina supplies the second.
//
// test_hellbreak_tutorial.php deliberately asserts the MECHANISM rather than the copy, because copy
// changes. This file is the other half: each claim the copy makes is paired with the engine fact it
// relies on. If a card is re-transcribed or a rule changes, the fact-check fails here, pointing at the
// sentence that just became a lie — instead of a playtester finding it.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

include_once './Core/DecisionQueueController.php';
include_once './HellbreakSim/ZoneClasses.php';
include_once './HellbreakSim/ZoneAccessors.php';
include_once './HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './HellbreakSim/Custom/GameLogic.php';
include_once './HellbreakSim/Tutorial/TutorialRuntime.php';
include_once './HellbreakSim/Fixtures/QuickStartFixtures.php';
include_once './HellbreakSim/Custom/CardLogic.php';

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

$lesson = file_get_contents('./HellbreakSim/Tutorial/tutorial-client.js');
$says = fn(string $phrase) => str_contains($lesson, $phrase);

// ---------------------------------------------------------------------------
// Claim: "the Wolf has Fearsome, so it enters ready"
// ---------------------------------------------------------------------------
$check($says('the Wolf has Fearsome, so it enters ready'), 'the lesson tells the player the Wolf enters ready');
$check(HellbreakCardHasKeyword('DOT_028', 'Fearsome'),
    'FACT: Transylvanian Wolf really has Fearsome — if it is ever re-transcribed without it, the lesson is lying');
$check(!$says('pay 1 malice to ready it; minions normally enter exhausted'),
    'the old instruction to pay malice to ready the Wolf is gone (that prompt never appeared)');

// ---------------------------------------------------------------------------
// Claim: "a tie goes to the player without initiative"
// ---------------------------------------------------------------------------
$check($says('a tie goes to the player without initiative'), 'the lesson explains the tie rule');
$check(HellbreakBidWinner(0, 0, 1) === 2,
    'FACT: when P1 holds initiative and the bids tie, P2 wins the bid (Rules of Play p3)');
$check(HellbreakBidWinner(0, 0, 2) === 1, 'FACT: and the reverse, so the rule is about the holder, not a seat');
$check(HellbreakBidWinner(3, 0, 1) === 1, 'FACT: a higher bid still wins outright, so the tie rule is only for ties');
$check(!$says('You retain initiative'),
    'the old claim that you keep initiative on a no-bid round is gone — it was false');

// ---------------------------------------------------------------------------
// Claim: "Mina's Cursed icon is what lets you play the Wolf, which needs two"
// ---------------------------------------------------------------------------
$check($says('Mina\\\'s Cursed icon is what lets you play the Wolf, which needs two'),
    'the lesson ties the Mina bid to being able to play the Wolf');
$wolfLoyalty = json_decode((string)CardLoyaltyAspects('DOT_028'), true);
$check(intval($wolfLoyalty['Cursed'] ?? 0) === 2, 'FACT: the Wolf needs exactly two Cursed icons');
$draculaCursed = intval(HellbreakCardResources('DOT_001')['aspects']['Cursed'] ?? 0);
$minaCursed = intval(HellbreakCardResources('DOT_032')['aspects']['Cursed'] ?? 0);
$check($draculaCursed === 1, 'FACT: Dracula alone supplies one Cursed icon — not enough on his own');
$check($minaCursed === 1, 'FACT: Mina supplies the second');
$check($draculaCursed + $minaCursed === intval($wolfLoyalty['Cursed'] ?? 0),
    'FACT: Dracula plus Mina is exactly what the Wolf needs, so skipping the Mina bid really does lock it out');

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
