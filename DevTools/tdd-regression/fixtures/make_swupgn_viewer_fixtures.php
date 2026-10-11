<?php
// Writes our own replay-viewer fixtures (real SWUSim cards) into fixtures/swupgn/. Re-run after editing:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/fixtures/make_swupgn_viewer_fixtures.php
// Keyframes are the fold of the events so far (plus deck counts, leaders and base HP at the first one),
// so the file is self-consistent by construction (SWU-PGN/1.0 spec §13–§14).
require_once __DIR__ . '/swupgn_test_helpers.php';

$ev = [];
$cnt = [];      // top-level step counter per phase letter, reset each round
$sub = 0;       // sub-step letter under the current top-level step
$round = 0;
$add = function (string $phase, array $e, bool $top = false) use (&$ev, &$cnt, &$sub, &$round) {
    $cnt[$phase] = $cnt[$phase] ?? 0;
    if ($top) { $cnt[$phase]++; $sub = 0; $seq = "R$round.$phase.{$cnt[$phase]}"; }
    else { $sub++; $seq = "R$round.$phase.{$cnt[$phase]}" . chr(96 + $sub); }
    $ev[] = ['seq' => $seq] + $e;
};
$nextTop = function (string $phase) use (&$cnt, &$round) { return "R$round.$phase." . (($cnt[$phase] ?? 0) + 1); };
$newRound = function (int $r) use (&$round, &$cnt, &$sub) { $round = $r; $cnt = []; $sub = 0; };

$deck = [1 => 8, 2 => 8];   // = the DECKS totals below; every card is drawn by the end
$draw = function (int $p, array $ids, string $phase) use ($add, &$deck) {
    foreach ($ids as $id) { $add($phase, ['t' => 'MOVE', 'card' => $id, 'from' => 'deck', 'to' => 'hand', 'p' => $p]); $deck[$p]--; }
    $add($phase, ['t' => 'DRAW', 'p' => $p, 'count' => count($ids), 'cards' => $ids]);
};
// The MOVE is stamped `for` the RESOURCE that follows it (§9.1); the RESOURCE takes the next number.
$resource = function (int $p, string $id, string $phase) use ($add, $nextTop) {
    $add($phase, ['t' => 'MOVE', 'card' => $id, 'from' => 'hand', 'to' => 'resource', 'p' => $p, 'for' => $nextTop($phase)]);
    $add($phase, ['t' => 'RESOURCE', 'p' => $p, 'card' => $id], true);
};
// A play: precursors stamped `for` the PLAY, then the PLAY, then the entering EXHAUST.
$play = function (int $p, string $id, string $zone, int $cost, int $power, int $hp, array $kw = []) use ($add, $nextTop) {
    $seq = $nextTop('A');
    if ($cost > 0) $add('A', ['t' => 'EXHAUST_RESOURCES', 'p' => $p, 'amount' => $cost, 'for' => $seq]);
    $add('A', ['t' => 'MOVE', 'card' => $id, 'from' => 'hand', 'to' => $zone, 'p' => $p, 'kind' => 'unit', 'for' => $seq]);
    $add('A', ['t' => 'STATS', 'card' => $id, 'power' => $power, 'hp' => $hp, 'keywords' => $kw, 'for' => $seq]);
    $add('A', ['t' => 'PLAY', 'p' => $p, 'card' => $id, 'zone' => $zone, 'cost' => $cost], true);
    $add('A', ['t' => 'EXHAUST', 'card' => $id]);
};
$keyframe = function (string $t, array $extra) use (&$ev, &$round) {
    // Fold the events exactly as a reader receives them (through the parser), so earlier keyframes snap;
    // decode without `assoc` so objects stay objects ({} for empty statusTokens).
    $kf = json_decode(SwuPgnStateToJson(SwuPgnFold(SwuPgnParse(SwuPgnTestFile($ev))['events'])));
    foreach ($extra as $k => $v) $kf->$k = $v;
    $ev[] = ['seq' => $t === 'ROUND_START' ? "R$round.start" : "R$round.end", 't' => $t, 'round' => $round, 'keyframe' => $kf];
};

// ── setup (round 0) ──
$ev[] = ['seq' => 'R0.S.start', 't' => 'PHASE_START', 'phase' => 'setup'];
$p1Order = ['SOR#108', 'SOR#108:2', 'SOR#108:3', 'JTL#095', 'LOF#091', 'JTL#058', 'SOR#108:4', 'SOR#108:5'];
$p2Order = ['SOR#045', 'SOR#045:2', 'SOR#045:3', 'JTL#032', 'ZZZ#999', 'JTL#095:2', 'SOR#045:4', 'JTL#032:2'];
$draw(1, array_slice($p1Order, 0, 6), 'S');
$draw(2, array_slice($p2Order, 0, 6), 'S');
$add('S', ['t' => 'KEEP_HAND', 'p' => 1], true);
$add('S', ['t' => 'KEEP_HAND', 'p' => 2], true);
$resource(1, 'SOR#108:2', 'S'); $resource(1, 'SOR#108:3', 'S');
$resource(2, 'SOR#045:2', 'S'); $resource(2, 'SOR#045:3', 'S');
$ev[] = ['seq' => 'R0.S.end', 't' => 'PHASE_END', 'phase' => 'setup'];

// ── round 1 ──
$newRound(1);
$keyframe('ROUND_START', ['round' => 1, 'phase' => 'action', 'initiative' => 1, 'initiativeTaken' => false, 'active' => 1]);
$first = $ev[count($ev) - 1]['keyframe'];
foreach ([1, 2] as $p) {    // the first keyframe supplies what no event carries
    $first->players->{$p}->deckSize = $deck[$p];
    $first->players->{$p}->leader = (object)['id' => $p === 1 ? 'SOR#010' : 'SOR#005', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false];
}
$first->players->{2}->baseHp = 25;      // P2's base is Jedha City (25 HP); P1's Capital City is 30
$first->players->{2}->baseMaxHp = 25;
$ev[] = ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action'];
$play(1, 'SOR#108', 'ground', 1, 1, 2);
$add('A', ['t' => 'MOVE', 'card' => 'TOKEN:shield#8752877738', 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#108']);
$add('A', ['t' => 'SHIELD_GAIN', 'card' => 'SOR#108']);
$play(2, 'SOR#045', 'ground', 1, 2, 4, ['restore 1']);
$add('A', ['t' => 'MOVE', 'card' => 'TOKEN:experience#2007868442', 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 2, 'kind' => 'upgrade', 'attachedTo' => 'SOR#045']);
$add('A', ['t' => 'EXPERIENCE_GAIN', 'card' => 'SOR#045', 'count' => 1]);
$add('A', ['t' => 'STATS', 'card' => 'SOR#045', 'power' => 3, 'hp' => 5, 'keywords' => ['restore 1']]);
$seq = $nextTop('A');
$add('A', ['t' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 1, 'for' => $seq]);
$add('A', ['t' => 'MOVE', 'card' => 'LOF#091', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#108', 'for' => $seq]);
$add('A', ['t' => 'STATS', 'card' => 'SOR#108', 'power' => 3, 'hp' => 4, 'keywords' => [], 'for' => $seq]);
$add('A', ['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#091', 'zone' => 'ground', 'target' => 'SOR#108', 'cost' => 1], true);
$add('A', ['t' => 'MOVE', 'card' => 'TOKEN:advantage#5844562972', 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#108']);
$add('A', ['t' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'advantage', 'count' => 1]);
$add('A', ['t' => 'MOVE', 'card' => 'TOKEN:credit#8015500527', 'from' => 'outsideTheGame', 'to' => 'base', 'p' => 1]);
$add('A', ['t' => 'PASS', 'p' => 2], true);
$add('A', ['t' => 'MOVE', 'card' => 'TOKEN:the-force#4571900905', 'from' => 'outsideTheGame', 'to' => 'base', 'p' => 2]);
$add('A', ['t' => 'CLAIM_INITIATIVE', 'p' => 1], true);
$add('A', ['t' => 'PASS', 'p' => 2], true);
$ev[] = ['seq' => 'R1.A.end', 't' => 'PHASE_END', 'phase' => 'action'];
$ev[] = ['seq' => 'R1.G.start', 't' => 'PHASE_START', 'phase' => 'regroup'];
$draw(1, array_slice($p1Order, 6, 2), 'G');
$draw(2, array_slice($p2Order, 6, 2), 'G');
$resource(1, 'SOR#108:4', 'G');
$resource(2, 'SOR#045:4', 'G');
$add('G', ['t' => 'READY', 'card' => 'SOR#108'], true);
$add('G', ['t' => 'READY', 'card' => 'SOR#045'], true);
$add('G', ['t' => 'READY_RESOURCES', 'p' => 1, 'amount' => 2], true);
$add('G', ['t' => 'READY_RESOURCES', 'p' => 2, 'amount' => 1], true);
$ev[] = ['seq' => 'R1.G.end', 't' => 'PHASE_END', 'phase' => 'regroup'];
$keyframe('ROUND_END', ['round' => 1, 'phase' => 'regroup', 'initiative' => 1, 'initiativeTaken' => true]);

// ── round 2 ──
$newRound(2);
$keyframe('ROUND_START', ['round' => 2, 'phase' => 'action', 'initiative' => 1, 'initiativeTaken' => false, 'active' => 1]);
$ev[] = ['seq' => 'R2.A.start', 't' => 'PHASE_START', 'phase' => 'action'];
$seq = $nextTop('A');
$add('A', ['t' => 'MOVE', 'card' => 'SOR#010', 'from' => 'base', 'to' => 'ground', 'p' => 1, 'kind' => 'unit', 'for' => $seq]);
$add('A', ['t' => 'STATS', 'card' => 'SOR#010', 'power' => 5, 'hp' => 8, 'keywords' => [], 'for' => $seq]);
$add('A', ['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'zone' => 'ground', 'epic' => true], true);
$play(2, 'JTL#032', 'ground', 2, 2, 2);
$add('A', ['t' => 'MOVE', 'card' => 'JTL#032', 'from' => 'ground', 'to' => 'capture', 'p' => 2, 'kind' => 'unit']);
$add('A', ['t' => 'CAPTURE', 'p' => 1, 'card' => 'JTL#032', 'by' => 'SOR#010']);
$play(1, 'JTL#095', 'space', 2, 3, 2);
$seq = $nextTop('A');
$add('A', ['t' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 1, 'for' => $seq]);
$add('A', ['t' => 'MOVE', 'card' => 'JTL#058', 'from' => 'hand', 'to' => 'space', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'JTL#095', 'for' => $seq]);
$add('A', ['t' => 'STATS', 'card' => 'JTL#095', 'power' => 4, 'hp' => 4, 'keywords' => [], 'for' => $seq]);
$add('A', ['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'JTL#058', 'zone' => 'space', 'target' => 'JTL#095', 'cost' => 1], true);
$play(2, 'ZZZ#999', 'ground', 0, 1, 1);
$seq = $nextTop('A');
$add('A', ['t' => 'CHOICE', 'p' => 1, 'prompt' => 'Vanguard Infantry', 'offered' => ['base@2'], 'chose' => 0, 'for' => $seq]);
$add('A', ['t' => 'EXHAUST', 'card' => 'SOR#108', 'for' => $seq]);
$add('A', ['t' => 'ATTACK', 'p' => 1, 'atk' => 'SOR#108', 'def' => 'base@2', 'defenderType' => 'base'], true);
$add('A', ['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@2', 'amt' => 3, 'damageType' => 'combat', 'hp' => 22]);

$cards = [
    ['id' => 'SOR#010', 'name' => 'Darth Vader, Dark Lord of the Sith'], ['id' => 'SOR#005', 'name' => 'Luke Skywalker, Faithful Friend'],
    ['id' => 'SOR#020', 'name' => 'Capital City'], ['id' => 'SOR#028', 'name' => 'Jedha City'],
    ['id' => 'SOR#108', 'name' => 'Vanguard Infantry', 'kind' => 'unit'], ['id' => 'SOR#045', 'name' => 'Yoda, Old Master', 'kind' => 'unit'],
    ['id' => 'JTL#095', 'name' => 'Phoenix Squadron A-Wing', 'kind' => 'unit'], ['id' => 'JTL#058', 'name' => 'Academy Graduate', 'kind' => 'unit'],
    ['id' => 'JTL#032', 'name' => 'Director Krennic', 'kind' => 'unit'], ['id' => 'LOF#091', 'name' => 'Craving Power', 'kind' => 'upgrade'],
    ['id' => 'ZZZ#999', 'name' => 'Mystery <b>Card</b>', 'kind' => 'unit'],
    ['id' => 'TOKEN:shield#8752877738', 'name' => 'Shield', 'kind' => 'upgrade'], ['id' => 'TOKEN:experience#2007868442', 'name' => 'Experience', 'kind' => 'upgrade'],
    ['id' => 'TOKEN:advantage#5844562972', 'name' => 'Advantage', 'kind' => 'upgrade'],
    ['id' => 'TOKEN:credit#8015500527', 'name' => 'Credit'], ['id' => 'TOKEN:the-force#4571900905', 'name' => 'The Force'],
];
$header = ['GameId' => 'viewer-fixture', 'CardPool' => 'SOR,JTL,LOF', 'Rounds' => '2', 'Result' => 'Incomplete', 'Reason' => 'Fixture',
    'P1Leader' => 'SOR#010', 'P1Base' => 'SOR#020', 'P2Leader' => 'SOR#005', 'P2Base' => 'SOR#028'];
$decks = [['p' => 1, 'leader' => 'SOR#010', 'base' => 'SOR#020', 'deck' => [['SOR#108', 5], ['JTL#095', 1], ['LOF#091', 1], ['JTL#058', 1]]],
          ['p' => 2, 'leader' => 'SOR#005', 'base' => 'SOR#028', 'deck' => [['SOR#045', 4], ['JTL#032', 2], ['ZZZ#999', 1], ['JTL#095', 1]]]];
$setup = [['seq' => 'R1.S.0', 't' => 'INIT', 'p1DeckOrder' => $p1Order, 'p2DeckOrder' => $p2Order]];
$dir = __DIR__ . '/swupgn';
file_put_contents("$dir/viewer-game.swupgn", SwuPgnTestFile($ev, ['header' => $header, 'cards' => $cards, 'decks' => $decks, 'setup' => $setup]));
$blanked = array_map(fn($e) => (($e['t'] ?? '') === 'DRAW' && ($e['p'] ?? 0) === 2) ? array_merge($e, ['cards' => []]) : $e, $ev);
file_put_contents("$dir/viewer-game-p1.swupgn", SwuPgnTestFile($blanked, ['header' => $header + ['Perspective' => 'P1'], 'cards' => $cards, 'decks' => $decks, 'setup' => $setup]));
$GLOBALS['__swupgn_t']['done'] = true;
echo "wrote viewer-game.swupgn and viewer-game-p1.swupgn (" . count($ev) . " events)\n";
