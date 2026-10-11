<?php
// SWU-PGN/1.0 spec Appendix A is normative: A.1 must fold to exactly A.2 and render to exactly A.3.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_appendix_a.php
// Fixtures are transcribed from the spec text (Engine tag changed to our own value).
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$dir = __DIR__ . '/fixtures/swupgn';
$text = file_get_contents("$dir/appendix-a.swupgn");
$expectedBoardJson = file_get_contents("$dir/appendix-a.fold.json");
$expectedStory = file_get_contents("$dir/appendix-a.render.txt");
if (substr($expectedStory, -1) === "\n") $expectedStory = substr($expectedStory, 0, -1); // file's own newline

$doc = SwuPgnParse($text);

// ---- parse (A.4: header, sections) --------------------------------------------------------------
SwuPgnTestEq(count($doc['headers']), 18, 'A.4 header: every tag pulled out (17 required + Format), including three on one line');
SwuPgnTestEq($doc['headers']['CardPool'] ?? null, 'SOR', 'A.4 header: CardPool read from a multi-tag line');
SwuPgnTestEq($doc['headers']['Engine'] ?? null, 'petranaki@test', 'A.4 header: Engine read from a multi-tag line');
SwuPgnTestEq(count($doc['decks']), 2, 'A.4 DECKS: two lines');
SwuPgnTestEq(count($doc['cards']), 6, 'A.4 CARDS: six entries');
SwuPgnTestEq(count($doc['setup']), 1, 'A.4 SETUP: one INIT');
SwuPgnTestEq(count($doc['events']), 55, 'A.4 EVENTS: 55 records');
SwuPgnTestEq(count($doc['annotations']), 1, 'A.4 ANNOTATIONS: one note');
SwuPgnTestEq($doc['annotations'][0]['nag'] ?? null, '?!', 'A.4 ANNOTATIONS: glyph ?!');
SwuPgnTestEq($doc['story'][0] ?? null, ' ── setup ──', 'STORY kept verbatim, leading space included');

// ---- A.2: the board -----------------------------------------------------------------------------
$board = SwuPgnFold($doc['events'], $warnings);
// Compared as JSON text so `{}` vs `[]` and key order both count.
$gotCompact = SwuPgnStateToJson($board);
$expCompact = json_encode(json_decode($expectedBoardJson), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
SwuPgnTestCheck($gotCompact === $expCompact, 'A.2: fold(A.1) is exactly the A.2 board (same JSON text, key order included)',
    "expected $expCompact\n got      $gotCompact");
SwuPgnTestEq($warnings, [], 'A.2: folding the vector raises no warnings');

// ---- A.3: the story -----------------------------------------------------------------------------
$story = SwuPgnRender($doc);
SwuPgnTestCheck($story === $expectedStory, 'A.3: render(A.1) is exactly the A.3 story', (function () use ($story, $expectedStory) {
    $a = explode("\n", $story); $b = explode("\n", $expectedStory);
    for ($i = 0; $i < max(count($a), count($b)); $i++) {
        if (($a[$i] ?? null) !== ($b[$i] ?? null)) return "first difference at line " . ($i + 1) . ":\n expected " . var_export($b[$i] ?? null, true) . "\n got      " . var_export($a[$i] ?? null, true);
    }
    return 'same lines?';
})());
$m = SwuPgnStoryMatches($doc);
SwuPgnTestCheck($m['present'] && $m['matches'], 'A.3: the file\'s own %%% STORY matches what the renderer produces', $m);

// ---- §20 conformance: validate() no errors, checkKeyframes() no mismatch ------------------------
$v = SwuPgnValidate($doc);
SwuPgnTestEq($v['errors'], [], '§20: validate() reports no errors for the vector');
SwuPgnTestCheck($v['ok'] === true, '§20: validate() ok');
$k = SwuPgnCheckKeyframes($doc['events']);
SwuPgnTestEq($k['mismatches'], [], '§20/A.4: checkKeyframes() reports no mismatch (baseHp/deckSize exempt at the first)');
SwuPgnTestCheck($k['ok'] === true, '§14: checkKeyframes() ok');

// ---- A.4 step-by-step spot checks via stateAt ---------------------------------------------------
$s = SwuPgnStateAt($doc['events'], 'R0.S.6');
SwuPgnTestEq([$s['players'][1]['handSize'], $s['players'][1]['hand']], [3, ['SOR#108', 'SOR#108:2', 'SOR#108:3']], 'A.4: three deck→hand MOVEs + DRAW → handSize 3, hand named once each');
SwuPgnTestCheck(!array_key_exists('deckSize', $s['players'][1]), 'A.4: deckSize still unknown before the first keyframe (absent, not 0)');
$s = SwuPgnStateAt($doc['events'], 'R0.S.21');
SwuPgnTestEq([$s['players'][2]['handSize'], $s['players'][2]['resourcesReady']], [1, 2], 'A.4: after setup resourcing P2 hand 1, resources 2');
$s = SwuPgnStateAt($doc['events'], 'R1.A.0a');
SwuPgnTestEq([$s['players'][1]['resourcesReady'], $s['players'][1]['resourcesExhausted']], [0, 2], 'A.4: EXHAUST_RESOURCES 2 → ready 0, exhausted 2');
$s = SwuPgnStateAt($doc['events'], 'R1.A.0b');
SwuPgnTestEq($s['players'][1]['cards'][0] ?? null, ['id' => 'SOR#108', 'zone' => 'ground', 'damage' => 0, 'exhausted' => false, 'upgrades' => [], 'shields' => 0, 'experience' => 0, 'statusTokens' => [], 'captured' => []], 'A.4: MOVE hand→ground adds a fresh card, exhausted false, no stats');
$s = SwuPgnStateAt($doc['events'], 'R1.A.1');
SwuPgnTestEq(count($s['players'][1]['cards']), 1, 'A.4: PLAY after its MOVE does not add the card twice');
$s = SwuPgnStateAt($doc['events'], 'R1.A.1a');
SwuPgnTestEq($s['players'][1]['cards'][0]['exhausted'], true, 'A.4: entering EXHAUST');
$s = SwuPgnStateAt($doc['events'], 'R1.G.6');
SwuPgnTestEq([$s['players'][1]['deckSize'], $s['players'][2]['handSize']], [0, 3], 'A.4: regroup draws: deckSize 2 → 0 (known since keyframe), P2 hand 3');
$s = SwuPgnStateAt($doc['events'], 'R1.G.13');
SwuPgnTestEq([$s['players'][1]['resourcesReady'], $s['players'][1]['resourcesExhausted']], [3, 0], 'A.4: two READY_RESOURCES → ready 3, exhausted 0');
$s = SwuPgnStateAt($doc['events'], 'no-such-seq');
SwuPgnTestCheck(SwuPgnStateToJson($s) === $gotCompact, '§12.3: stateAt() with an unknown seq folds the whole list');

SwuPgnTestFinish();
