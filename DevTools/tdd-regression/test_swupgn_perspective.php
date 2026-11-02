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

// What a base holds is renumbered with everything else: P2's second Tarkin Doctrine on P2's base, and P2's second
// Battlefield Marine held by P1's base, read as P2's first copies in P1's file — in the events and the keyframe alike.
$held = SwuPgnParse(SwuPgnTestFile([
    ['seq' => 'R1.A.1', 't' => 'MOVE', 'card' => 'HMW#206:2', 'from' => 'hand', 'to' => 'base', 'p' => 2, 'kind' => 'upgrade', 'attachedTo' => 'base@2'],
    ['seq' => 'R1.A.2', 't' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'HMW#206:2', 'zone' => 'base', 'target' => 'base@2'],
    ['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'SOR#95:2', 'from' => 'hand', 'to' => 'ground', 'p' => 2, 'kind' => 'unit'],
    ['seq' => 'R1.A.4', 't' => 'MOVE', 'card' => 'SOR#95:2', 'from' => 'ground', 'to' => 'capture', 'p' => 2, 'kind' => 'unit'],
    ['seq' => 'R1.A.5', 't' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#95:2', 'by' => 'base@1'],
    ['seq' => 'R1.end', 't' => 'ROUND_END', 'round' => 1, 'keyframe' => SwuPgnTestKeyframe(['baseCaptured' => ['SOR#95:2']], ['baseUpgrades' => ['HMW#206:2']])],
], ['setup' => [['seq' => 'R1.S.0', 't' => 'INIT', 'p1DeckOrder' => ['SOR#45'], 'p2DeckOrder' => ['HMW#206', 'HMW#206:2', 'SOR#95', 'SOR#95:2']]]]));
SwuPgnTestEq(SwuPgnCheckKeyframes($held['events'])['mismatches'], [], 'the all-seeing base-holding fixture checks clean');
$h1 = SwuPgnParse(SwuPgnWriteText(SwuPgnCutPerspective($held, 1)));
$hkf = end($h1['events'])['keyframe'];
SwuPgnTestEq([$hkf['players'][2]['baseUpgrades'] ?? null, $hkf['players'][1]['baseCaptured'] ?? null], [['HMW#206'], ['SOR#95']],
    'P1\'s keyframe names what the bases hold by P1\'s file\'s ids');
SwuPgnTestEq(SwuPgnCheckKeyframes($h1['events'])['mismatches'], [], 'and P1\'s file passes its own keyframe check');

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

// ── 2026-10-28 records (§21.1: what a perspective rule must hide) ────────────────────────────────
// P1 owns one SOR#095; P2 owns SOR#095:2/:3, SOR#045, SOR#108, SOR#150 and SOR#151.
$ids64 = ['P1Id' => 'sha256:' . str_repeat('a', 64), 'P2Id' => 'sha256:' . str_repeat('b', 64)];
$vis = ['headers' => SwuPgnTestHeaderTags($ids64), 'decks' => [], 'cards' => [], 'annotations' => [],
    'setup' => [['seq' => 'R0.S.0', 't' => 'INIT', 'p1DeckOrder' => ['SOR#010', 'SOR#095'], 'p2DeckOrder' => ['SOR#095:2', 'SOR#095:3', 'SOR#045', 'SOR#108', 'SOR#150', 'SOR#151']]],
    'events' => SwuPgnTestSeq([
        ['t' => 'MOVE', 'card' => 'SOR#095:2', 'from' => 'deck', 'to' => 'hand', 'p' => 2],                                       // 1
        ['t' => 'MOVE', 'card' => 'SOR#045', 'from' => 'deck', 'to' => 'hand', 'p' => 2],                                         // 2
        ['t' => 'MOVE', 'card' => 'SOR#108', 'from' => 'deck', 'to' => 'hand', 'p' => 2],                                         // 3
        ['t' => 'LOOK', 'p' => 2, 'owner' => 2, 'zone' => 'deck', 'cards' => ['SOR#150'], 'count' => 1],                          // 4
        ['t' => 'LOOK', 'p' => 1, 'owner' => 2, 'zone' => 'hand', 'cards' => ['SOR#045'], 'count' => 1],                          // 5
        ['t' => 'SEARCH', 'p' => 2, 'owner' => 2, 'zone' => 'deck', 'seen' => ['SOR#150', 'SOR#095:3'], 'found' => ['SOR#150']],  // 6
        ['t' => 'SEARCH', 'p' => 1, 'owner' => 2, 'zone' => 'deck', 'seen' => ['SOR#095:3']],                                    // 7
        ['t' => 'CHOICE', 'p' => 2, 'offered' => ['SOR#095:2', 'SOR#045', 'SOR#108'], 'chose' => 0, 'blind' => 'R1.A.1'],         // 8
        ['t' => 'CHOICE', 'p' => 2, 'offered' => ['SOR#045', 'SOR#108'], 'chose' => 0],                                           // 9
        ['t' => 'ABILITY_ACTIVATE', 'p' => 2, 'card' => 'SOR#108', 'kind' => 'triggered', 'usedBy' => 'SOR#045', 'grantedBy' => 'SOR#151'], // 10
        ['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#108', 'p' => 2, 'targets' => ['SOR#095:2'], 'duration' => 'phase'], // 11
        ['t' => 'NAME', 'p' => 2, 'name' => 'Wampa'],                                                                             // 12
        ['t' => 'MOVE', 'card' => 'SOR#095:2', 'from' => 'hand', 'to' => 'ground', 'p' => 2, 'kind' => 'unit'],                  // 13
        ['t' => 'STATS', 'card' => 'SOR#095:2', 'power' => 3, 'hp' => 3, 'copyOf' => 'SOR#108', 'name' => 'Wampa', 'cost' => 1, 'aspects' => []], // 14
        ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['SOR#095:2']],                              // 15
        ['t' => 'LOOK', 'p' => 1, 'owner' => 2, 'zone' => 'deck', 'cards' => ['SOR#150'], 'count' => 1],                          // 16
        ['t' => 'SHUFFLE', 'p' => 2],                                                                                             // 17
        ['t' => 'MOVE', 'card' => 'SOR#150', 'from' => 'deck', 'to' => 'hand', 'p' => 2],                                         // 18
        ['t' => 'MOVE', 'card' => 'SOR#150', 'from' => 'hand', 'to' => 'resource', 'p' => 2],                                     // 19
        ['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#150', 'zone' => 'resource', 'from' => 2],                                // 20
        ['t' => 'EFFECT_START', 'id' => 'fx@2', 'src' => 'SOR#095:2', 'p' => 2, 'seat' => 2, 'duration' => 'round'],              // 21
    ])];
$v1 = [];
foreach (SwuPgnCutPerspective($vis, 1)['events'] as $e) $v1[(int)substr($e['seq'], 5)] = $e;
SwuPgnTestEq([$v1[4]['cards'], $v1[4]['count']], [[], 1], '§21.1 LOOK.cards: the other seat\'s look names nothing (count kept)');
SwuPgnTestEq($v1[5]['cards'], ['SOR#045'], 'your own LOOK at their hand names what you saw');
SwuPgnTestCheck(!array_key_exists('seen', $v1[6]) && !array_key_exists('found', $v1[6]), '§21.1 SEARCH.seen / found: the other seat\'s search names nothing');
SwuPgnTestEq($v1[7]['seen'], ['SOR#095:2'], 'your own SEARCH of their deck names what you saw (their first public SOR#095 copy after your own)');
SwuPgnTestEq($v1[8]['offered'], ['HIDDEN', 'HIDDEN', 'HIDDEN'], '§21.1 a blind CHOICE: the other seat\'s offered cards are all hidden (even one you saw)');
SwuPgnTestEq($v1[9]['offered'], ['SOR#045', 'HIDDEN'], 'a non-blind CHOICE of theirs shows only what you know');
SwuPgnTestEq([$v1[10]['card'], $v1[10]['usedBy'], $v1[10]['grantedBy']], ['SOR#108', 'SOR#045', 'HIDDEN'], 'a trigger from their hand reveals its card (CR 7.6.3a); usedBy / grantedBy carry ids like every other field');
SwuPgnTestEq([$v1[11]['src'], $v1[11]['targets']], ['SOR#108', ['HIDDEN']], '§21.1 EFFECT_START.targets naming a hidden card is hidden');
SwuPgnTestEq($v1[12]['name'], 'Wampa', 'NAME stays: naming a card is public');
SwuPgnTestEq([$v1[13]['card'], $v1[14]['card'], $v1[14]['copyOf']], ['SOR#095:3', 'SOR#095:3', 'SOR#108'], 'STATS.copyOf is renumbered with the rest');
SwuPgnTestEq($v1[15]['targets'], ['HIDDEN'], 'EFFECT_END.targets name the target as its EFFECT_START did (the effect ends what it started on)');
SwuPgnTestEq($v1[16]['cards'], ['SOR#150'], 'your look at their deck top names it');
SwuPgnTestEq($v1[18]['card'], 'HIDDEN', 'after they SHUFFLE, a deck card you saw is forgotten: drawn, it is HIDDEN');
SwuPgnTestCheck($v1[20]['card'] !== 'HIDDEN', 'a resource you take control of is revealed to you', $v1[20]['card']);
SwuPgnTestEq($v1[21]['src'], 'SOR#095:3', 'EFFECT_START.src is renumbered');
$v2 = [];
foreach (SwuPgnCutPerspective($vis, 2)['events'] as $e) $v2[(int)substr($e['seq'], 5)] = $e;
SwuPgnTestEq([$v2[4]['cards'], $v2[6]['seen'] ?? null, $v2[8]['offered'], $v2[11]['targets']], [['SOR#150'], ['SOR#150', 'SOR#095:2'], ['SOR#095', 'SOR#045', 'SOR#108'], ['SOR#095']],
    'the seat that looked / searched / chose keeps every card it saw, renumbered from 1');
$visCut = SwuPgnParse(SwuPgnWriteText(SwuPgnCutPerspective($vis, 1)));
SwuPgnTestEq(array_column(SwuPgnValidate($visCut)['errors'], 'code'), [], 'the cut of the new records validates (no effect-end-unknown: the END names what the START did)');
$leak = array_values(array_filter(['SOR#151'], fn($id) => str_contains(SwuPgnWriteText(SwuPgnCutPerspective($vis, 1)), '"' . $id . '"')));
SwuPgnTestEq($leak, [], 'nothing P2 never showed appears in P1\'s file');

// A deck card you saw that they then drew is not forgotten when they shuffle (it left the deck): it stays known.
$drawn = $vis;
$drawn['events'] = SwuPgnTestSeq([
    ['t' => 'LOOK', 'p' => 1, 'owner' => 2, 'zone' => 'deck', 'cards' => ['SOR#151', 'SOR#150'], 'count' => 2],
    ['t' => 'MOVE', 'card' => 'SOR#151', 'from' => 'deck', 'to' => 'hand', 'p' => 2],
    ['t' => 'SHUFFLE', 'p' => 2],
    ['t' => 'MOVE', 'card' => 'SOR#151', 'from' => 'hand', 'to' => 'discard', 'p' => 2],
    ['t' => 'MOVE', 'card' => 'SOR#150', 'from' => 'deck', 'to' => 'hand', 'p' => 2],
]);
$dc = array_map(fn($e) => $e['card'] ?? null, SwuPgnCutPerspective($drawn, 1)['events']);
SwuPgnTestEq([$dc[1], $dc[3], $dc[4]], ['SOR#151', 'SOR#151', 'HIDDEN'], 'their shuffle forgets only the seen cards still in their deck');

// Keyframe fields from the 2026-10-28 text carry ids too: effects, abilityUses, foreignResources, copyOf, upgradeControllers.
$kfv = SwuPgnTestKeyframe(
    ['foreignResources' => ['SOR#150'], 'resources' => ['SOR#150'], 'resourcesExhausted' => 1,
        'abilityUses' => [['card' => 'SOR#095', 'ability' => 'x', 'count' => 1, 'per' => 'round', 'max' => 1]]],
    ['cards' => [SwuPgnTestCard('SOR#095:2', 'ground', ['copyOf' => 'SOR#150', 'name' => 'Wampa', 'upgradeControllers' => ['SOR#150' => 1], 'upgrades' => ['SOR#045', 'SOR#150']])],
        'abilityUses' => [['card' => 'SOR#095:2', 'ability' => 'y', 'count' => 1, 'per' => 'phase', 'max' => 2]]],
    ['effects' => [['id' => 'fx@1', 'src' => 'SOR#095:2', 'p' => 2, 'targets' => ['SOR#095:2', 'SOR#045'], 'duration' => 'phase']]]);
$kdoc = $vis;
$kdoc['events'] = array_merge(array_slice($vis['events'], 0, 20), [['seq' => 'R1.end', 't' => 'ROUND_END', 'round' => 1, 'keyframe' => $kfv]]);
$kcut = end(SwuPgnCutPerspective($kdoc, 1)['events'])['keyframe'];
SwuPgnTestEq([$kcut['effects'][0]['src'], $kcut['effects'][0]['targets']], ['SOR#095:3', ['HIDDEN', 'SOR#045']], 'keyframe effects: src and targets mapped (a target as its EFFECT_START named it, though shown since)');
SwuPgnTestEq([$kcut['players']['1']['abilityUses'][0]['card'], $kcut['players']['2']['abilityUses'][0]['card']], ['SOR#095', 'SOR#095:3'], 'keyframe abilityUses: card mapped');
SwuPgnTestEq([$kcut['players']['1']['foreignResources'], $kcut['players']['1']['resources']], [[$v1[20]['card']], [$v1[20]['card']]], 'keyframe foreignResources / resources: mapped (the stolen card you saw, by its new id)');
SwuPgnTestEq($v1[20]['card'], 'SOR#150:2', 'seen again after it was forgotten, it is a new id');
$kc = $kcut['players']['2']['cards'][0];
SwuPgnTestEq([$kc['id'], $kc['copyOf'], array_keys((array)$kc['upgradeControllers']), $kc['upgrades']], ['SOR#095:3', 'SOR#150:2', ['SOR#150:2'], ['SOR#045', 'SOR#150:2']], 'keyframe card: copyOf and upgradeControllers keys mapped');

SwuPgnTestFinish();
