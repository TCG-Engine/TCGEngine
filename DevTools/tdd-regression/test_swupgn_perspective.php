<?php
// SWU-PGN own-view cut: a player's file shows their own cards and only what the opponent made public (design §7).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_perspective.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$all = SwuPgnParse(file_get_contents(__DIR__ . '/fixtures/swupgn/viewer-game.swupgn'));
$hidden = ['deck', 'hand', 'resource'];

// From the all-seeing file: P2's cards, and the ones P2 never showed (only ever in a hidden zone).
$p2Cards = array_flip($all['setup'][0]['p2DeckOrder']);
$public = [];
foreach ($all['events'] as $e) {
    if ($e['t'] === 'MOVE' && isset($p2Cards[$e['card']]) && !in_array($e['to'], $hidden, true)) $public[$e['card']] = true;
}
$neverShown = array_keys(array_diff_key($p2Cards, $public));
SwuPgnTestCheck(count($neverShown) > 0 && count($public) > 0, 'the fixture has P2 cards both hidden and shown');

$cut = SwuPgnCutPerspective($all, 1);
$text = SwuPgnWriteText($cut);
$doc = SwuPgnParse($text);
SwuPgnTestEq([$doc['headers']['Perspective'] ?? null, $doc['headers']['Seed'] ?? null], ['P1', 'unseeded'], 'Perspective P1, Seed "unseeded"');
$leak = array_values(array_filter($neverShown, fn($id) => str_contains($text, '"' . $id . '"')));
SwuPgnTestEq($leak, [], 'no id P2 never showed appears anywhere in P1\'s file');
$hiddenMoves = array_filter($doc['events'], fn($e) => $e['t'] === 'MOVE' && ($e['p'] ?? 0) === 2 && in_array($e['from'], $hidden, true) && in_array($e['to'], $hidden, true));
SwuPgnTestCheck($hiddenMoves && !array_filter($hiddenMoves, fn($e) => $e['card'] !== 'HIDDEN'), 'P2\'s hidden-zone moves name "HIDDEN"');
SwuPgnTestCheck(!array_filter($doc['events'], fn($e) => $e['t'] === 'DRAW' && $e['p'] === 2 && $e['cards'] !== []), 'P2\'s DRAW.cards are []');
SwuPgnTestCheck(!array_filter($doc['events'], fn($e) => $e['t'] === 'RESOURCE' && $e['p'] === 2 && $e['card'] !== 'HIDDEN'), 'P2\'s RESOURCE.card is "HIDDEN"');
SwuPgnTestEq([$doc['setup'][0]['p2DeckOrder'], $doc['decks'][1]['deck']], [[], []], 'P2\'s deck order and decklist are empty');
SwuPgnTestEq([$doc['decks'][1]['leader'], $doc['decks'][1]['base']], [$all['decks'][1]['leader'], $all['decks'][1]['base']], 'P2\'s leader and base are kept');
SwuPgnTestEq($doc['setup'][0]['p1DeckOrder'], $all['setup'][0]['p1DeckOrder'], 'P1\'s own deck order is unchanged');

// P2's public copies are renumbered in order of appearance, after P1's own copies of the same card.
$firstP2Public = null;
foreach ($doc['events'] as $e) {
    if ($e['t'] === 'MOVE' && ($e['p'] ?? 0) === 2 && !in_array($e['to'], $hidden, true) && str_starts_with($e['card'], 'SOR#045')) { $firstP2Public = $e['card']; break; }
}
SwuPgnTestEq($firstP2Public, 'SOR#045', 'P2\'s first public copy of a card P1 lacks has no :N');
// Both decks hold JTL#095: P2's copy must never take an id P1's copies use.
$p2Shown = [];
foreach ($doc['events'] as $e) if ($e['t'] === 'MOVE' && ($e['p'] ?? 0) === 2 && $e['card'] !== 'HIDDEN') $p2Shown[$e['card']] = true;
SwuPgnTestEq(array_values(array_intersect(array_keys($p2Shown), $doc['setup'][0]['p1DeckOrder'])), [], 'no P2 id collides with a P1 copy (mirror cards)');

// The cut is a valid file and folds to its own keyframes; P2's hand and resources are left out of them.
SwuPgnTestEq(SwuPgnValidate($doc)['errors'], [], 'P1\'s file validates');
$check = SwuPgnCheckKeyframes($doc['events']);
SwuPgnTestCheck(!empty($check['ok']), 'P1\'s file passes its own keyframe check', json_encode(array_slice($check['mismatches'] ?? [], 0, 4)));
$kf = null; foreach ($doc['events'] as $e) if (isset($e['keyframe'])) { $kf = $e['keyframe']; break; }
SwuPgnTestCheck($kf !== null && !array_key_exists('resources', $kf['players'][2]) && isset($kf['players'][2]['handSize']),
    'keyframes drop P2\'s resources list, keep the counts');
$p2HandAll = null; foreach ($all['events'] as $e) if (isset($e['keyframe'])) { $p2HandAll = $e['keyframe']['players'][2]['hand'] ?? null; break; }
SwuPgnTestCheck(is_array($kf['players'][2]['hand'] ?? null) && !array_intersect($kf['players'][2]['hand'], (array)$p2HandAll),
    'P2\'s keyframe hand is a list (§13) naming none of P2\'s real hand');
SwuPgnTestCheck(isset($kf['players'][1]['hand']), 'and keep P1\'s own hand');
$p1HandAll = null; foreach ($all['events'] as $e) if (isset($e['keyframe'])) { $p1HandAll = $e['keyframe']['players'][1]['hand'] ?? null; break; }
SwuPgnTestEq($kf['players'][1]['hand'] ?? null, $p1HandAll, 'P1\'s own hand ids are unchanged');
$cardIds = array_column($doc['cards'], 'id');
$rest = SwuPgnWriteText(['cards' => []] + $doc);
$unused = array_values(array_filter($cardIds, fn($id) => !str_contains($rest, '"' . $id . '"') && !str_contains($rest, '"' . $id . ':')));
SwuPgnTestEq($unused, [], 'every CARDS entry names an id the rest of the file uses');
foreach ($neverShown as $id) {
    if (!array_key_exists(SwuPgnBaseId($id), array_flip(array_merge($all['setup'][0]['p1DeckOrder'], array_keys($public))))) {
        SwuPgnTestCheck(!in_array(SwuPgnBaseId($id), $cardIds, true), "CARDS has no entry for $id, a card P2 never showed");
    }
}

// Renumbering, pinned on a tiny game: P2 shows its SECOND copy of a card first (it must read as the first), and a
// copy of a card P1 also owns (it must take the number after P1's copies).
$mini = SwuPgnParse(SwuPgnTestFile([
    ['seq' => 'R0.S.1', 't' => 'MOVE', 'card' => 'SOR#46:2', 'from' => 'deck', 'to' => 'hand', 'p' => 2],
    ['seq' => 'R0.S.2', 't' => 'MOVE', 'card' => 'SOR#95:2', 'from' => 'deck', 'to' => 'hand', 'p' => 2],
    ['seq' => 'R1.A.1', 't' => 'MOVE', 'card' => 'SOR#46:2', 'from' => 'hand', 'to' => 'ground', 'p' => 2],
    ['seq' => 'R1.A.2', 't' => 'MOVE', 'card' => 'SOR#95:2', 'from' => 'hand', 'to' => 'ground', 'p' => 2],
], ['setup' => [['seq' => 'R1.S.0', 't' => 'INIT', 'p1DeckOrder' => ['SOR#95'], 'p2DeckOrder' => ['SOR#95:2', 'SOR#46', 'SOR#46:2']]]]));
$m1 = SwuPgnCutPerspective($mini, 1)['events'];
SwuPgnTestEq(array_column($m1, 'card'), ['HIDDEN', 'HIDDEN', 'SOR#46', 'SOR#95:2'],
    'P2\'s copies read in order of appearance, after P1\'s own copies of the same card');

// The other seat's cut: P1 is the hidden side, and P2's own copies are numbered from 1.
$cut2 = SwuPgnParse(SwuPgnWriteText(SwuPgnCutPerspective($all, 2)));
SwuPgnTestEq($cut2['headers']['Perspective'] ?? null, 'P2', 'Perspective P2');
SwuPgnTestEq($cut2['setup'][0]['p1DeckOrder'], [], 'P1\'s deck order is empty in P2\'s file');
SwuPgnTestCheck(in_array('JTL#095', $cut2['setup'][0]['p2DeckOrder'], true), 'P2\'s own copies are renumbered from 1 (JTL#095:2 → JTL#095)');
SwuPgnTestEq(SwuPgnValidate($cut2)['errors'], [], 'P2\'s file validates');
SwuPgnTestCheck(!empty(SwuPgnCheckKeyframes($cut2['events'])['ok']), 'P2\'s file passes its own keyframe check',
    json_encode(array_slice(SwuPgnCheckKeyframes($cut2['events'])['mismatches'] ?? [], 0, 4)));

// An engine prompt can name a card only its chooser may see ("Discard <the top card of your deck>?"): in a player's own
// view the OTHER player's CHOICE prompts are left out; their own are kept.
$withChoices = $all;
array_splice($withChoices['events'], 1, 0, [
    ['seq' => 'R0.S.900', 't' => 'CHOICE', 'p' => 2, 'prompt' => 'Discard Wampa?', 'offered' => ['YES', 'NO'], 'chose' => 0],
    ['seq' => 'R0.S.901', 't' => 'CHOICE', 'p' => 1, 'prompt' => 'Mine to read', 'offered' => ['YES', 'NO'], 'chose' => 1],
]);
$cc = array_values(array_filter(SwuPgnCutPerspective($withChoices, 1)['events'], fn($e) => $e['t'] === 'CHOICE' && in_array($e['seq'], ['R0.S.900', 'R0.S.901'], true)));
SwuPgnTestEq(array_map(fn($e) => [$e['p'], $e['prompt'] ?? null], $cc), [[2, null], [1, 'Mine to read']], 'the other player\'s CHOICE prompts are left out of your view');

// A card of theirs that goes back into their deck is forgotten: drawn again it is HIDDEN, shown again it is a new id —
// the file must not tell you that the card they redraw (or later show) is the same one you saw.
$back = ['headers' => SwuPgnTestHeaderTags(), 'decks' => [], 'cards' => [], 'annotations' => [],
    'setup' => [['seq' => 'R1.S.0', 't' => 'INIT', 'p1DeckOrder' => ['SOR#010'], 'p2DeckOrder' => ['SOR#095', 'SOR#095:2']]],
    'events' => SwuPgnTestSeq([
        ['t' => 'MOVE', 'card' => 'SOR#095', 'from' => 'deck', 'to' => 'hand', 'p' => 2],
        ['t' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 2, 'kind' => 'unit'],
        ['t' => 'MOVE', 'card' => 'SOR#095', 'from' => 'ground', 'to' => 'deck', 'p' => 2, 'kind' => 'unit'],
        ['t' => 'MOVE', 'card' => 'SOR#095', 'from' => 'deck', 'to' => 'hand', 'p' => 2],
        ['t' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'discard', 'p' => 2],
    ])];
$ids = array_column(SwuPgnCutPerspective($back, 1)['events'], 'card');
SwuPgnTestCheck($ids[0] === 'HIDDEN' && $ids[1] !== 'HIDDEN' && $ids[2] === $ids[1] && $ids[3] === 'HIDDEN' && !in_array($ids[4], ['HIDDEN', $ids[1]], true),
    'their card back in the deck is forgotten: redrawn HIDDEN, shown again under a new id', json_encode($ids));
$cutBack = SwuPgnParse(SwuPgnWriteText(SwuPgnCutPerspective($back, 1)));
SwuPgnTestEq(array_values(array_filter(SwuPgnValidate($cutBack)['errors'], fn($e) => !str_starts_with($e['code'], 'decks') && !str_starts_with($e['code'], 'header'))), [], 'and the cut still validates');

SwuPgnTestFinish();
