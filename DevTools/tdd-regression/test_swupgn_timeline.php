<?php
// SWU-PGN/1.0 spec §12.3 stateAt() and scrubbing: the board at ANY event position equals folding
// that prefix from scratch, and scrubbing a ~1000-event game through every position is fast.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_timeline.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/fixtures/swupgn_synthetic_game.php';

// Read through the parser, like a real file.
function Doc(array $events): array { return SwuPgnParse(SwuPgnTestFile($events)); }
function J(array $s): string { return SwuPgnStateToJson($s); }

// ── equivalence on a mid-size game, EVERY position ──────────────────────────────────────────────
$events = Doc(SwuPgnSyntheticGame(260))['events'];
$n = count($events);
SwuPgnTestCheck($n >= 260, "synthetic game has $n events");
$tl = SwuPgnTimeline($events);
SwuPgnTestEq($tl['count'], $n, 'timeline counts every event');
$badTl = $badAt = $badSeq = 0;
for ($i = -1; $i < $n; $i++) {
    $ref = J(SwuPgnFold(array_slice($events, 0, $i + 1)));
    if (J(SwuPgnTimelineStateAt($tl, $i)) !== $ref) $badTl++;
    if ($i >= 0) {
        if (J(SwuPgnStateAt($events, $events[$i]['seq'])) !== $ref) $badAt++;
        if (SwuPgnTimelineIndexOf($tl, $events[$i]['seq']) !== $i) $badSeq++;
    }
}
SwuPgnTestEq($badTl, 0, 'SwuPgnTimelineStateAt(i) equals fold(events[0..i]) at every position (and -1 = empty board)');
SwuPgnTestEq($badAt, 0, '§12.3: stateAt(seq) — starting from the last usable keyframe — equals the full fold at every seq');
SwuPgnTestEq($badSeq, 0, 'SwuPgnTimelineIndexOf maps every seq to its position');
SwuPgnTestCheck(J(SwuPgnTimelineStateAt($tl, -1)) === J(SwuPgnEmptyState()) && J(SwuPgnTimelineStateAt($tl, $n + 50)) === J(SwuPgnFold($events)), 'positions clamp: below 0 → empty board, past the end → final board');
SwuPgnTestCheck(SwuPgnTimelineIndexOf($tl, 'R99.A.1') === null, 'an unknown seq has no position');
SwuPgnTestCheck(J(SwuPgnStateAt($events, 'R99.A.1')) === J(SwuPgnFold($events)), '§12.3: an unknown seq folds the whole list');

$kfIdx = [];
foreach ($events as $i => $e) if (_SwuPgnIsKeyframeEvent($e) && SwuPgnKeyframeProblem($e['keyframe']) === null) $kfIdx[] = $i;
SwuPgnTestEq($tl['keyframes'], $kfIdx, 'timeline lists the usable keyframe positions (the damaged R3.start is not one)');
$damaged = 0;
foreach ($events as $e) if (($e['seq'] ?? '') === 'R3.start' && SwuPgnKeyframeProblem($e['keyframe']) !== null) $damaged++;
SwuPgnTestEq($damaged, 1, 'the synthetic game carries one damaged keyframe (R3.start)');
SwuPgnTestEq(SwuPgnCheckKeyframes($events)['mismatches'], [['seq' => 'R3.start', 'path' => 'keyframe', 'expected' => 'a complete keyframe', 'got' => 'keyframe is missing seat 1']], '§14 on the synthetic game: only the damaged keyframe is reported');

// Value semantics: a caller changing a returned board cannot corrupt the timeline.
$s = SwuPgnTimelineStateAt($tl, 40);
$before = J($s);
$s['players'][1]['handSize'] = 999;
$s['players'][1]['cards'][] = ['id' => 'X'];
SwuPgnTestCheck(J(SwuPgnTimelineStateAt($tl, 40)) === $before && J(SwuPgnTimelineStateAt($tl, 31)) === J(SwuPgnFold(array_slice($events, 0, 32))), 'returned boards are copies; checkpoints are never changed');

// Empty event list.
$empty = SwuPgnTimeline([]);
SwuPgnTestCheck($empty['count'] === 0 && J(SwuPgnTimelineStateAt($empty, 0)) === J(SwuPgnEmptyState()), 'an empty game scrubs to the empty board');

// ── performance: ~1000 events, every position ──────────────────────────────────────────────────
$big = Doc(SwuPgnSyntheticGame(1000, 11))['events'];
$nb = count($big);
$t0 = microtime(true);
$tl = SwuPgnTimeline($big);
for ($i = -1; $i < $nb; $i++) SwuPgnTimelineStateAt($tl, $i);
$scrub = microtime(true) - $t0;
SwuPgnTestCheck($scrub < 0.5, sprintf('scrubbing a %d-event game through every position takes %.3fs (< 0.5s)', $nb, $scrub));
$t0 = microtime(true);
for ($i = $nb - 1; $i >= 0; $i -= 3) SwuPgnTimelineStateAt($tl, $i);
$back = microtime(true) - $t0;
SwuPgnTestCheck($back < 0.3, sprintf('scrubbing backwards (every 3rd position) takes %.3fs', $back));
$t0 = microtime(true);
foreach ($big as $e) SwuPgnStateAt($big, $e['seq']);
$sa = microtime(true) - $t0;
SwuPgnTestCheck($sa < 3.0, sprintf('stateAt() for every seq of the same game (keyframe start) takes %.3fs', $sa));
SwuPgnTestCheck(J(SwuPgnTimelineStateAt($tl, $nb - 1)) === J(SwuPgnFold($big)), 'the big game\'s last position is the full fold');
$t0 = microtime(true);
$r = SwuPgnCheckKeyframes($big);
SwuPgnValidate(SwuPgnParse(SwuPgnTestFile($big)));
SwuPgnRenderEvents($big, []);
SwuPgnTestCheck(microtime(true) - $t0 < 1.0, 'integrity + validate + render of the big game together take under a second');
echo sprintf("      (%d events; scrub %.1f ms; stateAt-all %.1f ms; peak memory %.1f MB)\n", $nb, $scrub * 1000, $sa * 1000, memory_get_peak_usage() / 1048576);

// ── a starting state (a reader with card data knows base HP and deck size before any keyframe) ──
$start = SwuPgnEmptyState();
$start['players'][1]['baseHp'] = 35; $start['players'][1]['baseMaxHp'] = 35; $start['players'][1]['deckSize'] = 10;
$se = Doc([['seq' => 'R0.S.0a', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => 'deck', 'to' => 'hand', 'p' => 1],
    ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action'],
    ['seq' => 'R1.A.1a', 't' => 'DAMAGE', 'src' => 'SOR#045', 'tgt' => 'base@1', 'amt' => 4, 'hp' => 31]])['events'];
$stl = SwuPgnTimeline($se, $start);
SwuPgnTestEq(J(SwuPgnTimelineStateAt($stl, -1)), J($start), 'start state: position −1 is the start state');
SwuPgnTestEq([SwuPgnTimelineStateAt($stl, 1)['players'][1]['baseHp'], SwuPgnTimelineStateAt($stl, 1)['players'][1]['deckSize']], [35, 9],
    'start state: base HP holds until something sets it; deck MOVEs count down from the start size');
SwuPgnTestEq(SwuPgnTimelineStateAt($stl, 2)['players'][1]['baseHp'], 31, 'start state: a base DAMAGE still sets the absolute HP');
SwuPgnTestEq(J(SwuPgnFold($se, $sw, $start)), J($stl['final']), 'start state: Fold and Timeline agree');
$dtl = SwuPgnTimeline($se);
SwuPgnTestCheck(SwuPgnTimelineStateAt($dtl, 1)['players'][1]['baseHp'] === 30 && !array_key_exists('deckSize', SwuPgnTimelineStateAt($dtl, 1)['players'][1]),
    'no start state: unchanged (placeholder 30 HP, deck size unknown)');

SwuPgnTestFinish();
