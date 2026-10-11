<?php
// SWU-PGN step list (SWU-PGN/1.0 spec §9.1, §16): one step per numbered action + one per phase start.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_steps.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$doc = SwuPgnParse(file_get_contents(__DIR__ . '/fixtures/swupgn/appendix-a.swupgn'));
$events = array_values($doc['events']);
$s = SwuPgnSteps($doc);
$steps = $s['steps'];
$seqAt = fn(int $pos) => $events[$pos]['seq'] ?? null;

SwuPgnTestEq(array_column($steps, 'kind'), ['phase', 'phase', 'action', 'action', 'action', 'phase', 'phase', 'action'], 'Appendix A: 8 steps — phase/action kinds in order');
SwuPgnTestEq(array_column($steps, 'caption'), [
    'Setup', 'Round 1 · action phase', 'Player 1 plays Wampa to ground (cost 2)', 'Player 2 passes', 'Player 1 passes',
    'Round 1 · regroup phase', 'Round 2 · action phase', "Player 1 attacks Player 2's base with Wampa",
], 'Appendix A: captions are the §16 story lines');
SwuPgnTestEq(array_map($seqAt, array_column($steps, 'pos')), ['R1.start', 'R1.A.start', 'R1.A.1a', 'R1.A.2', 'R1.A.end', 'R2.start', 'R2.A.start', 'R2.A.1a'],
    'Appendix A: each step shows the board up to the event before the next step starts');
SwuPgnTestEq($steps[1]['pos'] < array_search('R1.A.0a', array_column($events, 'seq'), true), true,
    '§9.1 fallback: the play\'s unstamped precursors (R1.A.0a-c) belong to the PLAY step, not the phase step before it');
SwuPgnTestEq(array_column($steps, 'round'), [0, 1, 1, 1, 1, 1, 2, 2], 'Appendix A: rounds');
SwuPgnTestEq($s['rounds'], [0 => 0, 1 => 1, 2 => 6], 'Appendix A: first step of each round');
SwuPgnTestEq($steps[7]['lines'], ["4 damage to Player 2's base — 26 HP left"], 'the attack step carries its consequence lines');
SwuPgnTestEq(count($steps[0]['lines']), 8, 'the setup step carries the 8 setup story lines');
SwuPgnTestEq([$steps[2]['actor'], $steps[3]['actor'], $steps[0]['actor']], [1, 2, null], 'actor = the acting seat; null for a phase step');

// `for` stamping wins over the fallback.
$stamped = SwuPgnParse(SwuPgnTestFile([
    ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action'],
    ['seq' => 'R1.A.0a', 't' => 'CHOICE', 'p' => 1, 'offered' => ['base@2'], 'chose' => 0, 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.0b', 't' => 'EXHAUST', 'card' => 'JTL#095', 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'ATTACK', 'p' => 1, 'atk' => 'JTL#095', 'def' => 'base@2', 'defenderType' => 'base'],
]));
$st = SwuPgnSteps($stamped)['steps'];
SwuPgnTestEq([count($st), $st[0]['pos']], [2, 0], '`for`-stamped precursors start the ATTACK step (phase step ends at PHASE_START)');

// `for` alone decides, even when nothing in the run names the action's card (the fallback would not take it).
$stampedOnly = SwuPgnParse(SwuPgnTestFile([
    ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action', 'p' => 1],
    ['seq' => 'R1.A.0a', 't' => 'MODAL_CHOICE', 'p' => 1, 'offered' => ['Use 1 Credit', 'Pay'], 'chose' => 1, 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.0b', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2, 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#095', 'zone' => 'ground', 'cost' => 2],
]));
$so = SwuPgnSteps($stampedOnly)['steps'];
SwuPgnTestEq($so[0]['pos'], 0, '§9.1: `for`-stamped records belong to their action even when none names its card');
SwuPgnTestEq($so[0]['actor'], null, 'a phase step has no actor, even if its PHASE_START carries a `p`');

// A run that does not name the action's card is NOT taken (§9.1) — it stays with the step before.
$unrelated = SwuPgnParse(SwuPgnTestFile([
    ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action'],
    ['seq' => 'R1.A.0a', 't' => 'EXHAUST', 'card' => 'OTHER#1'],
    ['seq' => 'R1.A.1', 't' => 'ATTACK', 'p' => 1, 'atk' => 'JTL#095', 'def' => 'base@2', 'defenderType' => 'base'],
]));
SwuPgnTestEq(SwuPgnSteps($unrelated)['steps'][0]['pos'], 1, '§9.1: an unanchored run stays with the previous step');

// Untrusted input never throws.
[$threw] = SwuPgnTestThrows(fn() => SwuPgnSteps(['events' => [null, 5, 'x', ['t' => 'PASS']], 'cards' => []]));
SwuPgnTestEq($threw, false, 'junk events do not throw');
SwuPgnTestEq(SwuPgnSteps(['events' => [], 'cards' => []]), ['steps' => [], 'rounds' => []], 'an empty game has no steps');

// A seat's view: the other seat's draws, resources and search finds are hidden; its own stay named.
$vg = SwuPgnParse(file_get_contents(__DIR__ . '/fixtures/swupgn/viewer-game.swupgn'));
$text = fn(array $st) => implode("\n", array_merge(...array_map(fn($x) => array_merge([$x['caption']], $x['lines']), $st)));
$all = $text(SwuPgnSteps($vg)['steps']);
$p1 = $text(SwuPgnSteps($vg, 1)['steps']);
$p2 = $text(SwuPgnSteps($vg, 2)['steps']);
SwuPgnTestCheck(str_contains($all, 'Player 2 resources Yoda') && str_contains($all, 'Player 2 draws 6: '), 'all-seeing: every card is named (so the checks below are not vacuous)');
SwuPgnTestCheck(!str_contains($p1, 'Player 2 resources Yoda') && str_contains($p1, 'Player 2 resources a card'), "P1's view: P2's resources are 'a card'");
SwuPgnTestCheck(!str_contains($p1, 'Player 2 draws 6: ') && str_contains($p1, 'Player 2 draws 6'), "P1's view: P2's draws are a count only");
SwuPgnTestCheck(str_contains($p1, 'Player 1 draws 6: ') && str_contains($p1, 'Player 1 resources Vanguard'), "P1's view: P1's own draws and resources stay named");
SwuPgnTestCheck(!str_contains($p2, 'Player 1 resources Vanguard') && str_contains($p2, 'Player 2 resources Yoda'), "P2's view mirrors it");
$search = SwuPgnParse(SwuPgnTestFile([['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action'],
    ['seq' => 'R1.A.0a', 't' => 'SEARCH', 'p' => 2, 'found' => ['SOR#005']], ['seq' => 'R1.A.1', 't' => 'PASS', 'p' => 1]]));
SwuPgnTestCheck(str_contains($text(SwuPgnSteps($search)['steps']), 'Player 2 searches, finds '), 'all-seeing: the search find is named');
SwuPgnTestCheck(str_contains($text(SwuPgnSteps($search, 1)['steps']), 'Player 2 searches their deck')
    && !str_contains($text(SwuPgnSteps($search, 1)['steps']), 'finds'), "P1's view: P2's search finds are hidden");

SwuPgnTestFinish();
