<?php
// SWU-PGN writer matcher: diff changes + game-log notes → SWU-PGN events (design Section 5), with seq and `for` (§9.1).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_matcher.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnWriter/_include.php';

// A fresh context: seat 1 deck SOR#95 SOR#120 SOR#172 SOR#46 SOR#49, seat 2 deck SOR#96 SOR#49:2; leader SOR#5 at base 1.
function MCtx(string $phase = 'A', int $round = 1): array {
    $L = SwuPgnLedgerInit([1 => ['SOR_095', 'SOR_120', 'SOR_172', 'SOR_046', 'SOR_049'], 2 => ['SOR_096', 'SOR_049']]);
    SwuPgnLedgerPut($L, 1, 'base', SwuPgnLedgerNewToken($L, 'SOR_005'));
    return ['ledger' => $L, 'counters' => [], 'round' => $round, 'phase' => $phase];
}
function MPlace(array &$ctx, int $seat, string $zone, string $cardID, ?int $uid = null): string {
    $copy = SwuPgnLedgerTake($ctx['ledger'], $seat, 'deck', $cardID);
    SwuPgnLedgerPut($ctx['ledger'], $seat, $zone, $copy, $uid);
    return $copy;
}
$SHIELD = SwuPgnCardRef('SOR_T02');
$stats = fn($uid, $p = 3, $hp = 3, $seat = 1) => ['k' => 'stats', 'seat' => $seat, 'uid' => $uid, 'power' => $p, 'hp' => $hp, 'keywords' => []];

// ── Whose copy: a card named by CardID is the copy in the seat and zone the note says ───────────────
// P2 picks from its OWN hand a card P1 also runs (P1's copy is in P1's deck): the offer names P2's copy, so P1's own
// view can hide it — naming P1's copy would tell P1 what P2 holds.
$ctx = MCtx(); MPlace($ctx, 2, 'hand', 'SOR_096'); MPlace($ctx, 2, 'hand', 'SOR_049');
$r = SwuPgnMatch([], [['t' => 'CHOICE', 'p' => 2, 'prompt' => 'Discard a card', 'offered' => ['SOR_096', 'SOR_049'],
    'offeredSeats' => [2, 2], 'offeredZones' => ['hand', 'hand'], 'chose' => 0]], $ctx);
SwuPgnTestEq($r['events'][0]['offered'] ?? null, ['SOR#96', 'SOR#49:2'], 'CHOICE.offered names the chooser\'s copies, not the other seat\'s');
// Mirror leaders: P2's leader exhausting is P2's leader.
$ctx = MCtx(); SwuPgnLedgerPut($ctx['ledger'], 2, 'base', SwuPgnLedgerNewToken($ctx['ledger'], 'SOR_005'));
$r = SwuPgnMatch([['k' => 'exhaust', 'seat' => 2, 'target' => 'leader@2', 'cardID' => 'SOR_005']], [['t' => 'ABILITY', 'p' => 2, 'cardID' => 'SOR_005', 'kind' => 'action']], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['card']], $r['events']), [['EXHAUST', 'SOR#5:2'], ['ABILITY_ACTIVATE', 'SOR#5:2']], 'a mirror leader\'s action: P2\'s leader');
$ctx = MCtx(); SwuPgnLedgerPut($ctx['ledger'], 2, 'base', SwuPgnLedgerNewToken($ctx['ledger'], 'SOR_005'));
$r = SwuPgnMatch([['k' => 'ready', 'seat' => 2, 'target' => 'leader@2', 'cardID' => 'SOR_005']], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['card']], $r['events']), [['READY', 'SOR#5:2']], 'an unannounced mirror-leader ready: P2\'s leader');

// ── Play a unit from hand ────────────────────────────────────────────────────────────────────────
$ctx = MCtx(); MPlace($ctx, 1, 'hand', 'SOR_095');
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'ground', 'uid' => 7, 'exhausted' => true],
    ['k' => 'resources', 'seat' => 1, 'exhaustedDelta' => 2],
    $stats(7),
], [['t' => 'PLAY', 'p' => 1, 'cardID' => 'SOR_095', 'how' => 'unit', 'uid' => 7, 'targetUid' => null, 'cost' => 2]], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0a', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2, 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.0b', 't' => 'MOVE', 'card' => 'SOR#95', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit', 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.0c', 't' => 'STATS', 'card' => 'SOR#95', 'power' => 3, 'hp' => 3, 'keywords' => [], 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#95', 'zone' => 'ground', 'cost' => 2],
    ['seq' => 'R1.A.1a', 't' => 'EXHAUST', 'card' => 'SOR#95'],
], 'play a unit: EXHAUST_RESOURCES · MOVE · STATS · PLAY · EXHAUST, with for');
SwuPgnTestEq($r['gaps'], [], 'a fully announced play leaves no gaps');
SwuPgnTestEq(SwuPgnLedgerByUid($ctx['ledger'], 7), 'SOR#95', 'the played copy is bound to its UID');

// ── An attack: target choice, exhaust, combat damage both ways, a defeat ─────────────────────────
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_095', 7); MPlace($ctx, 2, 'ground', 'SOR_096', 8);
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 2, 'cardID' => 'SOR_096', 'from' => 'ground', 'to' => 'discard', 'uid' => 8],
    ['k' => 'damage', 'seat' => 1, 'target' => 7, 'amount' => 2, 'hp' => 1],
    ['k' => 'exhaust', 'seat' => 1, 'target' => 7, 'cardID' => 'SOR_095'],
], [
    ['t' => 'CHOICE', 'p' => 1, 'offered' => [8, 'base@2'], 'chose' => 0],
    ['t' => 'ATTACK', 'p' => 1, 'atkUid' => 7, 'def' => 8],
    ['t' => 'DAMAGE', 'src' => 7, 'tgt' => 8, 'amt' => 3, 'combat' => true],
    ['t' => 'DAMAGE', 'src' => 8, 'tgt' => 7, 'amt' => 2, 'combat' => true],
    ['t' => 'DEFEAT', 'uid' => 8, 'reason' => 'attack', 'by' => 7],
], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0a', 't' => 'CHOICE', 'p' => 1, 'offered' => ['SOR#96', 'base@2'], 'chose' => 0, 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.0b', 't' => 'EXHAUST', 'card' => 'SOR#95', 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'ATTACK', 'p' => 1, 'atk' => 'SOR#95', 'def' => 'SOR#96', 'defenderType' => 'unit'],
    ['seq' => 'R1.A.1a', 't' => 'DAMAGE', 'src' => 'SOR#95', 'tgt' => 'SOR#96', 'amt' => 3, 'damageType' => 'combat', 'hp' => 0],
    ['seq' => 'R1.A.1b', 't' => 'DAMAGE', 'src' => 'SOR#96', 'tgt' => 'SOR#95', 'amt' => 2, 'damageType' => 'combat', 'hp' => 1],
    ['seq' => 'R1.A.1c', 't' => 'MOVE', 'card' => 'SOR#96', 'from' => 'ground', 'to' => 'discard', 'p' => 2, 'kind' => 'unit'],
    ['seq' => 'R1.A.1d', 't' => 'DEFEAT', 'card' => 'SOR#96', 'reason' => 'attack', 'defeatedBy' => 'SOR#95'],
], 'attack: CHOICE · EXHAUST · ATTACK · DAMAGE both ways · MOVE · DEFEAT; damage to a unit that died reads hp 0');
SwuPgnTestEq($r['gaps'], [], 'a fully announced attack leaves no gaps');

// ── Play an upgrade ──────────────────────────────────────────────────────────────────────────────
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_095', 7); MPlace($ctx, 1, 'hand', 'SOR_120');
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'hand', 'to' => 'ground', 'host' => 7, 'kind' => 'upgrade'],
    ['k' => 'resources', 'seat' => 1, 'exhaustedDelta' => 2],
    $stats(7, 5, 5),
], [['t' => 'PLAY', 'p' => 1, 'cardID' => 'SOR_120', 'how' => 'upgrade', 'uid' => null, 'targetUid' => 7, 'cost' => 2]], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0a', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2, 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.0b', 't' => 'MOVE', 'card' => 'SOR#120', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#95', 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'SOR#120', 'zone' => 'ground', 'target' => 'SOR#95', 'cost' => 2],
    ['seq' => 'R1.A.1a', 't' => 'STATS', 'card' => 'SOR#95', 'power' => 5, 'hp' => 5, 'keywords' => []],
], 'play an upgrade: EXHAUST_RESOURCES · MOVE(attachedTo) · PLAY_UPGRADE(target) · host STATS');

// ── Play an event and its effect ─────────────────────────────────────────────────────────────────
$ctx = MCtx(); MPlace($ctx, 1, 'hand', 'SOR_172');
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_172', 'from' => 'hand', 'to' => 'discard'],
    ['k' => 'damage', 'seat' => 2, 'target' => 'base@2', 'amount' => 3, 'hp' => 27],
    ['k' => 'resources', 'seat' => 1, 'exhaustedDelta' => 3],
], [
    ['t' => 'PLAY', 'p' => 1, 'cardID' => 'SOR_172', 'how' => 'event', 'uid' => null, 'targetUid' => null, 'cost' => 3],
    ['t' => 'DAMAGE', 'src' => 'SOR_172', 'tgt' => 'base@2', 'amt' => 3, 'combat' => false],
], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0a', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 3, 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.0b', 't' => 'MOVE', 'card' => 'SOR#172', 'from' => 'hand', 'to' => 'discard', 'p' => 1, 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'PLAY_EVENT', 'p' => 1, 'card' => 'SOR#172', 'zone' => 'discard', 'cost' => 3],
    ['seq' => 'R1.A.1a', 't' => 'DAMAGE', 'src' => 'SOR#172', 'tgt' => 'base@2', 'amt' => 3, 'damageType' => 'ability', 'hp' => 27],
], 'play an event: EXHAUST_RESOURCES · MOVE · PLAY_EVENT · its effect; an event card has no kind');

// ── Draw and resource (action phase: sub-steps; the resource MOVE is stamped for its RESOURCE) ─
$ctx = MCtx(); MPlace($ctx, 1, 'hand', 'SOR_095');
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'resource', 'exhausted' => false],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'deck', 'to' => 'hand'],
], [['t' => 'DRAW', 'p' => 1, 'n' => 1], ['t' => 'RESOURCE', 'p' => 1, 'cardID' => 'SOR_095']], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#120', 'from' => 'deck', 'to' => 'hand', 'p' => 1, 'kind' => 'upgrade'],
    ['seq' => 'R1.A.0b', 't' => 'DRAW', 'p' => 1, 'count' => 1, 'cards' => ['SOR#120']],
    ['seq' => 'R1.A.0c', 't' => 'MOVE', 'card' => 'SOR#95', 'from' => 'hand', 'to' => 'resource', 'p' => 1, 'kind' => 'unit', 'for' => 'R1.A.0d'],
    ['seq' => 'R1.A.0d', 't' => 'RESOURCE', 'p' => 1, 'card' => 'SOR#95'],
], 'draw: MOVE · DRAW; resource: MOVE(for) · RESOURCE');

// A RESOURCE note that names no card takes the seat's move into the row.
$ctx = MCtx(); MPlace($ctx, 1, 'hand', 'SOR_095');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'resource', 'exhausted' => false]],
    [['t' => 'RESOURCE', 'p' => 1, 'cardID' => null]], $ctx);
SwuPgnTestEq([array_column($r['events'], 't'), $r['gaps']], [['MOVE', 'RESOURCE'], []], 'a cardless RESOURCE note claims the row move');

// A deploy's epic flag comes from the leader's Epic Action flipping with it.
$ctx = MCtx();
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_005', 'from' => 'base', 'to' => 'ground', 'uid' => 12, 'exhausted' => false],
    ['k' => 'leader', 'seat' => 1, 'field' => 'deployed', 'value' => true],
    ['k' => 'leader', 'seat' => 1, 'field' => 'epicUsed', 'value' => true],
], [['t' => 'DEPLOY', 'p' => 1, 'cardID' => 'SOR_005']], $ctx);
SwuPgnTestEq(array_values(array_filter($r['events'], fn($e) => $e['t'] === 'DEPLOY_LEADER'))[0]['epic'] ?? null, true, 'DEPLOY_LEADER epic from the claimed epicUsed change');

// A leader deployed into play exhausted (Phee Genoa's tax unpaid): the DEPLOY_LEADER, then its EXHAUST.
$ctx = MCtx();
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_005', 'from' => 'base', 'to' => 'ground', 'uid' => 12, 'exhausted' => true]],
    [['t' => 'DEPLOY', 'p' => 1, 'cardID' => 'SOR_005']], $ctx);
SwuPgnTestEq(array_values(array_filter(array_column($r['events'], 't'), fn($t) => $t !== 'STATS')), ['MOVE', 'DEPLOY_LEADER', 'EXHAUST'], 'a leader deployed exhausted: DEPLOY_LEADER · EXHAUST');

// A play whose card has not moved yet (its host is picked in the next request) is carried, not emitted.
$ctx = MCtx(); MPlace($ctx, 1, 'hand', 'SOR_120');
$play = ['t' => 'PLAY', 'p' => 1, 'cardID' => 'SOR_120', 'how' => 'upgrade', 'uid' => null, 'targetUid' => null, 'cost' => 2];
$r = SwuPgnMatch([], [$play], $ctx);
SwuPgnTestEq([$r['events'], $r['carry']], [[], [$play]], 'an unmoved PLAY is carried to the next frame');

// A search: SEARCH, then the MOVE of the card that left the deck.
$ctx = MCtx();
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_046', 'from' => 'deck', 'to' => 'hand']],
    [['t' => 'SEARCH', 'p' => 1, 'cardIDs' => ['SOR_046']]], $ctx);
SwuPgnTestEq([array_column($r['events'], 't'), $r['gaps']], [['SEARCH', 'MOVE'], []], 'SEARCH claims the searched card\'s move');

// Staging: a searched deck laid out in the TempZone is no move; the pick leaves "deck", the rest go back silently.
$ctx = MCtx();
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'deck', 'to' => 'temp'],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'deck', 'to' => 'temp'],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_049', 'from' => 'outsideTheGame', 'to' => 'temp'],
], [], $ctx);
SwuPgnTestEq([$r['events'], $r['gaps']], [[], []], 'staging into the TempZone writes nothing');
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'temp', 'to' => 'hand'],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'temp', 'to' => 'deck'],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_049', 'from' => 'temp', 'to' => 'outsideTheGame'],
], [['t' => 'SEARCH', 'p' => 1, 'cardIDs' => ['SOR_120']]], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['from'] ?? null, $e['to'] ?? null], $r['events']),
    [['SEARCH', null, null, null], ['MOVE', 'SOR#120', 'deck', 'hand']], 'the pick leaves the deck; the returns are silent');
SwuPgnTestEq([$r['gaps'], in_array('SOR#95', $ctx['ledger']['zones'][1]['deck'] ?? [], true)], [[], true], 'no gaps, and the returned copy is back in the deck');
// A searched card lifted out of staging a request before it is played (Bossk's bounty search): limbo, not gone — it is
// played from the deck, as the same copy.
$ctx = MCtx();
SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'deck', 'to' => 'temp']], [], $ctx);
$r1 = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'temp', 'to' => 'outsideTheGame']], [], $ctx);
$r2 = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'outsideTheGame', 'to' => 'ground', 'uid' => 9, 'exhausted' => false]], [], $ctx);
SwuPgnTestEq([$r1['events'], array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['from'] ?? null, $e['to'] ?? null], $r2['events'])],
    [[], [['MOVE', 'SOR#95', 'deck', 'ground']]], 'a searched card held out of staging is played from the deck, as itself');

// ...and when the engine shows that card leaving the deck itself, the held copy is the one that left.
$ctx = MCtx();
SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'deck', 'to' => 'temp']], [], $ctx);
SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'temp', 'to' => 'outsideTheGame']], [], $ctx);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'deck', 'to' => 'ground', 'uid' => 9, 'exhausted' => false]], [], $ctx);
SwuPgnTestEq([array_column($r['events'], 'card'), $ctx['ledger']['limbo'] ?? []], [['SOR#95'], []], 'a held card shown leaving the deck: the held copy, limbo cleared');

// A held card that never lands is not held forever: after SWUPGN_LIMBO_PASSES passes its move out of the game is written,
// as an unannounced change (a gap), and the board and the file agree again.
$ctx = MCtx(); MPlace($ctx, 1, 'space', 'SOR_049', 7); MPlace($ctx, 1, 'attached:7', 'SOR_120');
SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'space', 'to' => 'outsideTheGame', 'host' => 7, 'kind' => 'upgrade']], [], $ctx);
$late = [];
for ($i = 0; $i < SWUPGN_LIMBO_PASSES; $i++) { $r = SwuPgnMatch([], [], $ctx); $late = array_merge($late, $r['events']); $lateGaps = $r['gaps']; }
SwuPgnTestEq([array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['from'] ?? null, $e['to'] ?? null], $late), count($lateGaps), $ctx['ledger']['limbo'] ?? []],
    [[['MOVE', 'SOR#120', 'space', 'outsideTheGame']], 1, []], 'a held card that never lands: its move out of the game is written late, as a gap');
// Two held copies of one card, one per seat: the one landing in seat 2's play is seat 2's copy.
$ctx = MCtx(); MPlace($ctx, 1, 'space', 'SOR_049', 7); MPlace($ctx, 2, 'space', 'SOR_049', 8);
SwuPgnLedgerPut($ctx['ledger'], 1, 'attached:7', SwuPgnLedgerNewToken($ctx['ledger'], 'JTL_046'));
SwuPgnLedgerPut($ctx['ledger'], 2, 'attached:8', $p2pilot = SwuPgnLedgerNewToken($ctx['ledger'], 'JTL_046'));
SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'space', 'to' => 'outsideTheGame', 'host' => 7, 'kind' => 'upgrade'],
    ['k' => 'move', 'seat' => 2, 'cardID' => 'JTL_046', 'from' => 'space', 'to' => 'outsideTheGame', 'host' => 8, 'kind' => 'upgrade']], [], $ctx);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 2, 'cardID' => 'JTL_046', 'from' => 'outsideTheGame', 'to' => 'space', 'host' => 8, 'kind' => 'upgrade']], [], $ctx);
SwuPgnTestEq(array_values(array_unique(array_column($r['events'], 'card'))), [$p2pilot], 'a held card lands as its own seat\'s copy');

// A deck card out of sight for a request in the middle of a search, then put back through the staging area: it never
// left the deck.
$ctx = MCtx();
$r1 = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'deck', 'to' => 'outsideTheGame']], [], $ctx);
$r2 = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'temp', 'to' => 'deck']], [], $ctx);
SwuPgnTestEq([$r1['events'], $r1['gaps'], $r2['events'], in_array('SOR#95', $ctx['ledger']['zones'][1]['deck'] ?? [], true), $ctx['ledger']['limbo'] ?? []],
    [[], [], [], true, []], 'a deck card out of sight mid-search and put back: nothing written, the same copy in the deck');

// A revealed card out of sight for a request and put straight back where it was (Hired Slicer, Don't Get Cocky): no move.
$ctx = MCtx();
SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'deck', 'to' => 'outsideTheGame']], [], $ctx);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'outsideTheGame', 'to' => 'deck']], [], $ctx);
SwuPgnTestEq([$r['events'], $r['gaps'], in_array('SOR#95', $ctx['ledger']['zones'][1]['deck'] ?? [], true), $ctx['ledger']['limbo'] ?? []],
    [[], [], true, []], 'a held card back where it was: nothing written');

// Staged for the OPPONENT to pick from (Admiral Trench), then handed back: a hop between staging areas is no change —
// the copy stays its deck owner's, and still left "deck" when it is finally taken.
$ctx = MCtx();
SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'deck', 'to' => 'temp', 'fromSeat' => 1, 'toSeat' => 2]], [], $ctx);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 2, 'cardID' => 'SOR_095', 'from' => 'temp', 'to' => 'temp', 'fromSeat' => 2, 'toSeat' => 1]], [], $ctx);
SwuPgnTestEq([$r['events'], $ctx['ledger']['zones'][1]['temp'] ?? [], $ctx['ledger']['tempOrigin']['SOR#95'] ?? null], [[], ['SOR#95'], 'deck'],
    'a hop between staging areas writes nothing and keeps the copy\'s origin');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'temp', 'to' => 'hand']], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['from'] ?? null, $e['to'] ?? null], $r['events']), [['MOVE', 'SOR#95', 'deck', 'hand']],
    'taken at last: it left the deck');

// Cross-seat moves: one MOVE cannot carry two seats, so a TAKE_CONTROL re-seats the card.
$ctx = MCtx(); MPlace($ctx, 2, 'discard', 'SOR_096');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 2, 'cardID' => 'SOR_096', 'from' => 'discard', 'to' => 'ground', 'uid' => 9, 'exhausted' => false, 'fromSeat' => 2, 'toSeat' => 1]], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['p'] ?? null, $e['zone'] ?? ($e['to'] ?? null)], $r['events']), [['MOVE', 2, 'ground'], ['TAKE_CONTROL', 1, 'ground']],
    'into another seat\'s arena: MOVE (the card\'s side), then TAKE_CONTROL to the new controller');
$ctx = MCtx(); MPlace($ctx, 1, 'resource', 'SOR_095');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'resource', 'to' => 'hand', 'fromExhausted' => true, 'fromSeat' => 1, 'toSeat' => 2]], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['p'] ?? null, $e['from'] ?? null, $e['exhausted'] ?? null], $r['events']), [['TAKE_CONTROL', 2, 1, true], ['MOVE', 2, 'resource', true]],
    'out of a resource row to another seat: TAKE_CONTROL (from, exhausted), then the MOVE on the new side');

// A resource stolen into the thief's row (DJ): a control change, not a zone change — one TAKE_CONTROL carries the counts
// (§10 TAKE_CONTROL), and a change of state on arrival is the THIEF's row's.
$steal = fn(bool $leftEx, bool $arrivedEx) => [['k' => 'move', 'seat' => 2, 'cardID' => 'SOR_096', 'from' => 'resource', 'to' => 'resource',
    'exhausted' => $arrivedEx, 'fromExhausted' => $leftEx, 'fromSeat' => 2, 'toSeat' => 1]];
$ctx = MCtx(); MPlace($ctx, 2, 'resource', 'SOR_096');
$r = SwuPgnMatch($steal(true, true), [], $ctx);
SwuPgnTestEq($r['events'], [['seq' => 'R1.A.0a', 't' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#96', 'zone' => 'resource', 'from' => 2, 'exhausted' => true]],
    'a resource stolen exhausted, staying exhausted: one TAKE_CONTROL');
SwuPgnTestEq([$ctx['ledger']['zones'][1]['resource'] ?? [], $ctx['ledger']['zones'][2]['resource'] ?? []], [['SOR#96'], []], 'the copy is re-seated in the ledger');
$ctx = MCtx(); MPlace($ctx, 2, 'resource', 'SOR_096');
$r = SwuPgnMatch($steal(false, true), [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['p'] ?? null, $e['exhausted'] ?? null, $e['amount'] ?? null], $r['events']),
    [['TAKE_CONTROL', 1, null, null], ['EXHAUST_RESOURCES', 1, null, 1]], 'stolen ready, arriving exhausted: TAKE_CONTROL · EXHAUST_RESOURCES (the thief\'s)');
$ctx = MCtx(); MPlace($ctx, 2, 'resource', 'SOR_096');
$r = SwuPgnMatch($steal(true, false), [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['p'] ?? null, $e['exhausted'] ?? null, $e['amount'] ?? null], $r['events']),
    [['TAKE_CONTROL', 1, true, null], ['READY_RESOURCES', 1, null, 1]], 'stolen exhausted, arriving ready: TAKE_CONTROL · READY_RESOURCES');

// Role changes. A pilot ejected to a unit: off its host and back in as a unit, now bound to its new unit. A unit made a
// pilot (L3-37): out as a unit, back onto the host. Always through outsideTheGame — the fold only detaches on leaving the
// arenas, and a MOVE's from ≠ to.
$pilotCtx = function (bool $attached) {
    $ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_049', 7);
    SwuPgnLedgerPut($ctx['ledger'], 1, $attached ? 'attached:7' : 'ground', SwuPgnLedgerNewToken($ctx['ledger'], 'JTL_046'), $attached ? null : 9);
    return $ctx;
};
$pilot = SwuPgnCardRef('JTL_046'); $host7 = SwuPgnCardRef('SOR_049');
$shape = fn($r) => array_map(fn($e) => [$e['t'], $e['from'] ?? null, $e['to'] ?? null, $e['kind'] ?? null, $e['attachedTo'] ?? null], $r['events']);
$ctx = $pilotCtx(true);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'ground', 'to' => 'ground', 'uid' => 9, 'host' => 7, 'kind' => 'upgrade', 'detach' => true, 'exhausted' => true]], [], $ctx);
SwuPgnTestEq($shape($r), [['MOVE', 'ground', 'outsideTheGame', 'upgrade', null], ['MOVE', 'outsideTheGame', 'ground', 'unit', null], ['EXHAUST', null, null, null, null]],
    'ejected in the same arena: detach · enter as a unit · EXHAUST');
SwuPgnTestEq([SwuPgnLedgerByUid($ctx['ledger'], 9), $ctx['ledger']['zones'][1]['attached:7'] ?? []], [$pilot, []], 'the ejected pilot is its new unit');
$ctx = MCtx(); MPlace($ctx, 1, 'space', 'SOR_049', 7); SwuPgnLedgerPut($ctx['ledger'], 1, 'attached:7', SwuPgnLedgerNewToken($ctx['ledger'], 'JTL_046'));
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'space', 'to' => 'ground', 'uid' => 9, 'host' => 7, 'kind' => 'upgrade', 'detach' => true, 'exhausted' => false]], [], $ctx);
SwuPgnTestEq($shape($r), [['MOVE', 'space', 'outsideTheGame', 'upgrade', null], ['MOVE', 'outsideTheGame', 'ground', 'unit', null]],
    'ejected from a space Vehicle to the ground: also out and in (an arena-to-arena MOVE does not detach, §12.1)');
$ctx = $pilotCtx(false);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'ground', 'to' => 'ground', 'uid' => 9, 'host' => 7, 'kind' => 'upgrade', 'attachFromPlay' => true]], [], $ctx);
SwuPgnTestEq($shape($r), [['MOVE', 'ground', 'outsideTheGame', 'unit', null], ['MOVE', 'outsideTheGame', 'ground', 'upgrade', $host7]],
    'a unit made a pilot: out as a unit, onto the host');
SwuPgnTestEq([SwuPgnLedgerByUid($ctx['ledger'], 9), $ctx['ledger']['zones'][1]['attached:7'] ?? []], [null, [$pilot]], 'the same copy, now on the host');

// A ready resource smuggled into play: it LEFT the row ready (MOVE.exhausted absent) and ENTERED play exhausted (EXHAUST).
$ctx = MCtx(); MPlace($ctx, 1, 'resource', 'SOR_095');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'resource', 'to' => 'ground', 'uid' => 9, 'exhausted' => true, 'fromExhausted' => false]], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['exhausted'] ?? null], $r['events']), [['MOVE', null], ['EXHAUST', null]],
    'a ready resource smuggled in: MOVE (left ready) · EXHAUST (entered exhausted)');

// A host defeated with its upgrade: the upgrade leaves first, defeated by the framework (its host left play, §6.4).
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_049', 7); MPlace($ctx, 1, 'attached:7', 'SOR_120');
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_049', 'from' => 'ground', 'to' => 'discard', 'uid' => 7],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'ground', 'to' => 'discard', 'host' => 7, 'kind' => 'upgrade'],
], [['t' => 'DEFEAT', 'uid' => 7, 'cardID' => 'SOR_049', 'reason' => 'attack', 'by' => null]], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['reason'] ?? null], $r['events']),
    [['MOVE', 'SOR#120', null], ['DEFEAT', 'SOR#120', 'frameworkEffect'], ['MOVE', 'SOR#49', null], ['DEFEAT', 'SOR#49', 'attack']],
    'the host\'s upgrade goes first, as a framework defeat');

// An event that puts itself into the resource row exhausted (Resupply): a played event is filed to discard (§12), so it
// goes there first, then on to the row — RESOURCE after that MOVE, then the exhaustion.
$ctx = MCtx(); MPlace($ctx, 1, 'hand', 'SOR_172');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_172', 'from' => 'hand', 'to' => 'resource', 'exhausted' => true]],
    [['t' => 'PLAY', 'p' => 1, 'cardID' => 'SOR_172', 'how' => 'event', 'cost' => 3]], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['from'] ?? null, $e['to'] ?? null], $r['events']),
    [['MOVE', 'hand', 'discard'], ['PLAY_EVENT', null, null], ['MOVE', 'discard', 'resource'], ['RESOURCE', null, null], ['EXHAUST_RESOURCES', null, null]],
    'an event resourced exhausted: MOVE→discard · PLAY_EVENT · MOVE→resource · RESOURCE · EXHAUST_RESOURCES');
SwuPgnTestEq($ctx['ledger']['zones'][1]['resource'] ?? [], [SwuPgnCardRef('SOR_172')], 'the same copy ends in the row');

// A base's Action is Epic only when it spent the base's Epic Action (a "three times per game" base Action does not).
$ctx = MCtx();
$r = SwuPgnMatch([['k' => 'baseEpic', 'seat' => 1]], [['t' => 'ABILITY', 'p' => 1, 'cardID' => 'base@1', 'kind' => 'epic']], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['kind'] ?? null, $e['epic'] ?? null], $r['events']), [['epic', true]], 'a base Epic Action: kind epic');
$ctx = MCtx();
$r = SwuPgnMatch([], [['t' => 'ABILITY', 'p' => 1, 'cardID' => 'base@1', 'kind' => 'epic']], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['kind'] ?? null, $e['epic'] ?? null], $r['events']), [['action', null]], 'a base Action that left the Epic Action unused: kind action');

// An event played FROM the discard pile (One in a Million via Aid from the Innocent) goes back to it: no zone change for
// the diff to see. It leaves the pile while it is played and is filed on top again (the fold's PLAY_EVENT).
$ctx = MCtx(); MPlace($ctx, 1, 'discard', 'SOR_172'); MPlace($ctx, 1, 'discard', 'SOR_046');
$r = SwuPgnMatch([['k' => 'resources', 'seat' => 1, 'exhaustedDelta' => 1]],
    [['t' => 'PLAY', 'p' => 1, 'cardID' => 'SOR_172', 'how' => 'event', 'from' => 'discard', 'cost' => 1]], $ctx);
SwuPgnTestEq([array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['from'] ?? null, $e['to'] ?? null], $r['events']), $r['carry'], $ctx['ledger']['zones'][1]['discard']],
    [[['EXHAUST_RESOURCES', null, null, null], ['MOVE', 'SOR#172', 'discard', 'outsideTheGame'], ['PLAY_EVENT', 'SOR#172', null, null]], [], ['SOR#46', 'SOR#172']],
    'an event played from the discard pile: written, and filed on top again');

// A base upgrade defeated (Trap Field, as its own cost): the DEFEAT note claims its move out of the base area, in place.
$ctx = MCtx(); SwuPgnLedgerPut($ctx['ledger'], 1, 'base', SwuPgnLedgerNewToken($ctx['ledger'], 'HMW_171'));
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'HMW_171', 'from' => 'base', 'to' => 'discard']],
    [['t' => 'DEFEAT', 'uid' => null, 'cardID' => 'HMW_171', 'reason' => 'ability', 'by' => null]], $ctx);
SwuPgnTestEq([array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['from'] ?? null], $r['events']), $r['gaps']],
    [[['MOVE', SwuPgnCardRef('HMW_171'), 'base'], ['DEFEAT', SwuPgnCardRef('HMW_171'), null]], []], 'a base upgrade defeated: MOVE base → discard · DEFEAT, no gap');

// A host defeated with a Pilot leader aboard: the leader goes home exhausted (its EXHAUST), the host is defeated.
$ctx = MCtx(); MPlace($ctx, 1, 'space', 'SOR_049', 7); SwuPgnLedgerPut($ctx['ledger'], 1, 'attached:7', SwuPgnLedgerNewToken($ctx['ledger'], 'JTL_012'));
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_012', 'from' => 'space', 'to' => 'base', 'host' => 7, 'kind' => 'upgrade', 'exhausted' => true],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_049', 'from' => 'space', 'to' => 'discard', 'uid' => 7],
], [['t' => 'DEFEAT', 'uid' => 7, 'cardID' => 'SOR_049', 'reason' => 'ability', 'by' => null]], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['card'] ?? null], $r['events']),
    [['MOVE', SwuPgnCardRef('JTL_012')], ['EXHAUST', SwuPgnCardRef('JTL_012')], ['MOVE', SwuPgnCardRef('SOR_049')], ['DEFEAT', SwuPgnCardRef('SOR_049')]],
    'a Pilot leader whose host is defeated comes home exhausted');

// A Pilot leader returned home exhausted (Bamboozle): the MOVE off its host, then its EXHAUST.
$ctx = MCtx(); MPlace($ctx, 1, 'space', 'SOR_049', 7); SwuPgnLedgerPut($ctx['ledger'], 1, 'attached:7', SwuPgnLedgerNewToken($ctx['ledger'], 'JTL_012'));
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_012', 'from' => 'space', 'to' => 'base', 'host' => 7, 'kind' => 'upgrade', 'exhausted' => true]], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['attachedTo'] ?? null], $r['events']),
    [['MOVE', SwuPgnCardRef('JTL_012'), null], ['EXHAUST', SwuPgnCardRef('JTL_012'), null]], 'a Pilot leader comes home exhausted: MOVE · EXHAUST');

// A pilot whose host is bounced, moved to the ground as a unit a request later (Luke Skywalker, You Still With Me?): when
// it lands, it is ejected — off its host and in as a unit.
$ctx = $pilotCtx(true);
SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'ground', 'to' => 'outsideTheGame', 'host' => 7, 'kind' => 'upgrade'],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_049', 'from' => 'ground', 'to' => 'hand', 'uid' => 7]], [], $ctx);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'outsideTheGame', 'to' => 'ground', 'uid' => 9, 'exhausted' => true]], [], $ctx);
SwuPgnTestEq([$shape($r), SwuPgnLedgerByUid($ctx['ledger'], 9)], [[['MOVE', 'ground', 'outsideTheGame', 'upgrade', null], ['MOVE', 'outsideTheGame', 'ground', 'unit', null],
    ['EXHAUST', null, null, null, null]], $pilot], 'a held pilot landing as a unit: ejected, as itself');

// A captive played as a pilot (Dryden Vos), its Vehicle picked a request later: one MOVE out of capture onto the host.
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_046', 7); MPlace($ctx, 1, 'space', 'SOR_049', 8);
SwuPgnLedgerPut($ctx['ledger'], 1, 'capture:7', SwuPgnLedgerNewToken($ctx['ledger'], 'JTL_045'));
$r1 = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_045', 'from' => 'capture', 'to' => 'outsideTheGame', 'captor' => 7]], [], $ctx);
$r2 = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_045', 'from' => 'outsideTheGame', 'to' => 'space', 'host' => 8, 'kind' => 'upgrade']], [], $ctx);
SwuPgnTestEq([$r1['events'], array_map(fn($e) => [$e['card'] ?? null, $e['from'] ?? null, $e['to'] ?? null, $e['attachedTo'] ?? null], $r2['events'])],
    [[], [['JTL#45', 'capture', 'space', 'SOR#49']]], 'a captive played as a pilot: one MOVE capture → its Vehicle');
// A captive tucked away while its captor is a pilot (Paige Tico), back when the captor is a unit again: a CAPTURE by it.
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_046', 7);
SwuPgnLedgerPut($ctx['ledger'], 1, 'capture:7', SwuPgnLedgerNewToken($ctx['ledger'], 'JTL_045'));
SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_045', 'from' => 'capture', 'to' => 'outsideTheGame', 'captor' => 7]], [], $ctx);
SwuPgnLedgerPut($ctx['ledger'], 1, 'ground', SwuPgnLedgerTake($ctx['ledger'], 1, 'ground', 'SOR_046'), 9);   // the captor, re-entered as uid 9
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_045', 'from' => 'outsideTheGame', 'to' => 'capture', 'captor' => 9]], [], $ctx);
SwuPgnTestEq([array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['by'] ?? null], $r['events']), $ctx['ledger']['zones'][1]['capture:9'] ?? []],
    [[['CAPTURE', 'JTL#45', 'SOR#46']], ['JTL#45']], 'a tucked captive comes back: CAPTURE by its captor, the same copy');

// Poe Dameron attaching himself in one request (no DEPLOY note): the DEPLOY_LEADER names his host, after his MOVE.
$ctx = MCtx(); MPlace($ctx, 1, 'space', 'SOR_049', 7);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_005', 'from' => 'base', 'to' => 'space', 'host' => 7, 'kind' => 'upgrade'],
    ['k' => 'leader', 'seat' => 1, 'field' => 'deployed', 'value' => true]], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['zone'] ?? null, $e['kind'] ?? null, $e['target'] ?? null], array_values(array_filter($r['events'], fn($e) => $e['t'] === 'DEPLOY_LEADER'))),
    [['DEPLOY_LEADER', 'space', 'upgrade', 'SOR#49']], 'an unannounced pilot deploy: DEPLOY_LEADER onto its host');
// A defeat that was REPLACED (Roger Roger: the upgrade moves to a Battle Droid instead): no DEFEAT, just the move.
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_049', 7); MPlace($ctx, 1, 'ground', 'SOR_046', 8); MPlace($ctx, 1, 'attached:7', 'SOR_120');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'ground', 'to' => 'ground', 'host' => 8, 'kind' => 'upgrade', 'fromHost' => 7]],
    [['t' => 'DEFEAT', 'uid' => null, 'cardID' => 'SOR_120', 'reason' => 'ability', 'by' => null]], $ctx);
SwuPgnTestEq(array_column($r['events'], 't'), ['MOVE', 'MOVE'], 'a replaced defeat writes no DEFEAT');

// A card lifted out of play while its next place is picked (Poe Dameron deploying himself as a pilot, or hopping to
// another Vehicle): nothing is written until it lands — then one move from where it really was, and for a deploy the
// DEPLOY_LEADER with its host.
$ctx = MCtx(); MPlace($ctx, 1, 'space', 'SOR_049', 7);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_005', 'from' => 'base', 'to' => 'outsideTheGame'],
    ['k' => 'leader', 'seat' => 1, 'field' => 'deployed', 'value' => true]], [['t' => 'ABILITY', 'p' => 1, 'cardID' => 'SOR_005', 'kind' => 'action']], $ctx);
SwuPgnTestEq([array_column($r['events'], 't'), $r['gaps']], [['ABILITY_ACTIVATE'], []], 'lifted off the base, vehicle not yet picked: only the ability');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_005', 'from' => 'outsideTheGame', 'to' => 'space', 'host' => 7, 'kind' => 'upgrade']], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['card'] ?? null, $e['from'] ?? null, $e['kind'] ?? null, $e['attachedTo'] ?? $e['target'] ?? null], $r['events']),
    [['MOVE', 'SOR#5', 'base', 'upgrade', 'SOR#49'], ['DEPLOY_LEADER', 'SOR#5', null, 'upgrade', 'SOR#49']], 'landed: one MOVE from the base, then DEPLOY_LEADER onto its host');
$ctx = MCtx(); MPlace($ctx, 1, 'space', 'SOR_049', 7); MPlace($ctx, 1, 'space', 'SOR_046', 8); MPlace($ctx, 1, 'attached:7', 'SOR_120');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'space', 'to' => 'outsideTheGame', 'host' => 7, 'kind' => 'upgrade']], [], $ctx);
SwuPgnTestEq([$r['events'], $r['gaps']], [[], []], 'lifted off its host, next host not yet picked: nothing yet');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'outsideTheGame', 'to' => 'space', 'host' => 8, 'kind' => 'upgrade']], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['card'] ?? null, $e['from'] ?? null, $e['to'] ?? null, $e['attachedTo'] ?? null], $r['events']),
    [['SOR#120', 'space', 'outsideTheGame', null], ['SOR#120', 'outsideTheGame', 'space', 'SOR#46']], 'landed on the new host: the same copy, re-attached');

// A host defeated while its upgrade moves to another unit (Death Star Plans): the host leaving play takes only its
// tokens with it — the upgrade keeps its copy.
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_049', 7); MPlace($ctx, 1, 'ground', 'SOR_046', 8); MPlace($ctx, 1, 'attached:7', 'SOR_120');
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'ground', 'to' => 'ground', 'host' => 8, 'kind' => 'upgrade', 'fromHost' => 7],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_049', 'from' => 'ground', 'to' => 'discard', 'uid' => 7],
], [['t' => 'DEFEAT', 'uid' => 7, 'cardID' => 'SOR_049', 'reason' => 'attack', 'by' => null]], $ctx);
SwuPgnTestEq([array_values(array_unique(array_filter(array_map(fn($e) => $e['card'] ?? null, $r['events']), fn($c) => $c !== null && str_starts_with($c, 'SOR#120')))),
    $ctx['ledger']['zones'][1]['attached:8'] ?? []], [['SOR#120'], ['SOR#120']], 'the upgrade keeps its copy when its old host leaves first');

// An upgrade moved to another host: off the old host, onto the new one.
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_049', 7); MPlace($ctx, 1, 'ground', 'SOR_046', 8); MPlace($ctx, 1, 'attached:7', 'SOR_120');
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'ground', 'to' => 'ground', 'host' => 8, 'kind' => 'upgrade', 'fromHost' => 7]], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['from'], $e['to'], $e['attachedTo'] ?? null], $r['events']), [['ground', 'outsideTheGame', null], ['outsideTheGame', 'ground', 'SOR#46']],
    'a re-attach is a detach and an attach (from ≠ to)');
SwuPgnTestEq([array_unique(array_column($r['events'], 'card')), $ctx['ledger']['zones'][1]['attached:7'] ?? [], $ctx['ledger']['zones'][1]['attached:8'] ?? []],
    [[SwuPgnCardRef('SOR_120')], [], [SwuPgnCardRef('SOR_120')]], 'a re-attach moves the same copy off the old host (no new copy)');

// A note about a unit that entered this frame: its arrival is written first, so the note can name it.
$ctx = MCtx();
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_T01', 'from' => 'outsideTheGame', 'to' => 'space', 'uid' => 40, 'exhausted' => false],
    ['k' => 'token', 'seat' => 1, 'uid' => 40, 'kind' => 'shield', 'delta' => 1],
    $stats(40),
], [['t' => 'TOKEN', 'uid' => 40, 'kind' => 'shield', 'delta' => 1]], $ctx);
$tie = SwuPgnCardRef('JTL_T01');
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['card'] ?? $e['token'] ?? null, $e['attachedTo'] ?? null], $r['events']), [
    ['CREATE_TOKEN', $tie, null], ['MOVE', $tie, null], ['MOVE', $SHIELD, $tie], ['SHIELD_GAIN', $tie, null], ['STATS', $tie, null]],
    'a Shield on a token created this frame names the token');

// A leader flip, announced and not.
$ctx = MCtx();
$flip = ['k' => 'leader', 'seat' => 1, 'field' => 'onStartingSide', 'value' => false];
$r = SwuPgnMatch([$flip], [['t' => 'FLIP', 'p' => 1, 'cardID' => 'SOR_005']], $ctx);
SwuPgnTestEq([array_column($r['events'], 't'), $r['events'][0]['onStartingSide'] ?? null, $r['gaps']], [['LEADER_FLIP'], false, []], 'FLIP → LEADER_FLIP with the stated face');
$r = SwuPgnMatch([$flip], [], $ctx);
SwuPgnTestEq([array_column($r['events'], 't'), count($r['gaps'])], [['LEADER_FLIP'], 1], 'an unannounced flip is still a LEADER_FLIP, and a gap');

// Regroup: every record takes its own number; readying is structural (no gaps).
$ctx = MCtx('G'); MPlace($ctx, 1, 'ground', 'SOR_095', 7);
$r = SwuPgnMatch([
    ['k' => 'ready', 'seat' => 1, 'target' => 7, 'cardID' => 'SOR_095'],
    ['k' => 'resources', 'seat' => 1, 'exhaustedDelta' => -2],
], [], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.G.1', 't' => 'READY', 'card' => 'SOR#95'],
    ['seq' => 'R1.G.2', 't' => 'READY_RESOURCES', 'p' => 1, 'amount' => 2],
], 'regroup ready step: READY per unit, one READY_RESOURCES per run, own numbers');
SwuPgnTestEq($r['gaps'], [], 'the regroup ready step is structural, not a gap');

// ── Unclaimed changes: plain events in the fixed order, listed in gaps (stats never are) ─────────
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_095', 7);
$mill = ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_046', 'from' => 'deck', 'to' => 'discard'];
$hit = ['k' => 'damage', 'seat' => 1, 'target' => 7, 'amount' => 1, 'hp' => 2];
$shield = ['k' => 'token', 'seat' => 1, 'uid' => 7, 'kind' => 'shield', 'delta' => 1];
$tap = ['k' => 'exhaust', 'seat' => 1, 'target' => 7, 'cardID' => 'SOR_095'];
$r = SwuPgnMatch([$stats(7, 4, 3), $tap, $shield, $hit, $mill], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => $e['t'], $r['events']), ['MOVE', 'DAMAGE', 'MOVE', 'SHIELD_GAIN', 'EXHAUST', 'STATS'],
    'unclaimed: move → damage/heal → token → exhaust/ready → stats');
SwuPgnTestEq($r['events'][0], ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#46', 'from' => 'deck', 'to' => 'discard', 'p' => 1, 'kind' => 'unit'],
    'an unclaimed move is a plain MOVE');
SwuPgnTestEq($r['events'][1], ['seq' => 'R1.A.0b', 't' => 'DAMAGE', 'src' => 'unknown', 'tgt' => 'SOR#95', 'amt' => 1, 'damageType' => 'ability', 'hp' => 2],
    'unannounced damage has src "unknown"');
SwuPgnTestEq($r['events'][2], ['seq' => 'R1.A.0c', 't' => 'MOVE', 'card' => $SHIELD, 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#95'],
    'a token gain moves the token onto its host');
SwuPgnTestEq($r['gaps'], [$mill, $hit, $shield, $tap], 'every unclaimed change but stats is a gap');

// ── A note that explains nothing is dropped ─────────────────────────────────────────────────────
$ctx = MCtx();
$r = SwuPgnMatch([], [['t' => 'DAMAGE', 'src' => 7, 'tgt' => 'base@2', 'amt' => 3, 'combat' => false]], $ctx);
SwuPgnTestEq($r['events'], [], 'a DAMAGE note with no damage change is dropped');

// ── Deck-out damage ──────────────────────────────────────────────────────────────────────────────
$ctx = MCtx('G');
$r = SwuPgnMatch([['k' => 'damage', 'seat' => 1, 'target' => 'base@1', 'amount' => 3, 'hp' => 27]],
    [['t' => 'DAMAGE', 'src' => 'rule:deck-out', 'tgt' => 'base@1', 'amt' => 3, 'combat' => false]], $ctx);
SwuPgnTestEq($r['events'], [['seq' => 'R1.G.1', 't' => 'DAMAGE', 'src' => 'rule:deck-out', 'tgt' => 'base@1', 'amt' => 3, 'damageType' => 'ability', 'hp' => 27]],
    'deck-out damage carries src "rule:deck-out"');

// Two damage notes against one target share its change; hp reads the running total.
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_095', 7);
$r = SwuPgnMatch([['k' => 'damage', 'seat' => 2, 'target' => 'base@2', 'amount' => 5, 'hp' => 25]],
    [['t' => 'DAMAGE', 'src' => 7, 'tgt' => 'base@2', 'amt' => 2, 'combat' => false], ['t' => 'DAMAGE', 'src' => 7, 'tgt' => 'base@2', 'amt' => 3, 'combat' => false]], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['amt'], $e['hp']], $r['events']), [[2, 28], [3, 25]], 'split damage: hp after each hit');

// ── Tokens: gain and pop with notes; advantage is a status token ─────────────────────────────────
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_095', 7);
$r = SwuPgnMatch([$shield], [['t' => 'TOKEN', 'uid' => 7, 'kind' => 'shield', 'delta' => 1]], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => $SHIELD, 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#95'],
    ['seq' => 'R1.A.0b', 't' => 'SHIELD_GAIN', 'card' => 'SOR#95'],
], 'shield gained: token MOVE in, then SHIELD_GAIN on the host');
$r = SwuPgnMatch([array_replace($shield, ['delta' => -1])], [['t' => 'TOKEN', 'uid' => 7, 'kind' => 'shield', 'delta' => -1]], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0c', 't' => 'SHIELD_USE', 'card' => 'SOR#95'],
    ['seq' => 'R1.A.0d', 't' => 'MOVE', 'card' => $SHIELD, 'from' => 'ground', 'to' => 'outsideTheGame', 'p' => 1, 'kind' => 'upgrade'],
], 'shield used: SHIELD_USE, then the token MOVE out (host-less); counters carry across frames');
$r = SwuPgnMatch([['k' => 'token', 'seat' => 1, 'uid' => 7, 'kind' => 'advantage', 'delta' => 1]], [['t' => 'TOKEN', 'uid' => 7, 'kind' => 'advantage', 'delta' => 1]], $ctx);
SwuPgnTestEq($r['events'][1], ['seq' => 'R1.A.0f', 't' => 'STATUS_TOKEN', 'card' => 'SOR#95', 'token' => 'advantage', 'count' => 1], 'advantage is a STATUS_TOKEN');

// ── Deploy ───────────────────────────────────────────────────────────────────────────────────────
$ctx = MCtx();
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_005', 'from' => 'base', 'to' => 'ground', 'uid' => 12, 'exhausted' => false],
    $stats(12, 4, 7),
    ['k' => 'leader', 'seat' => 1, 'field' => 'deployed', 'value' => true],
    ['k' => 'leader', 'seat' => 1, 'field' => 'epicUsed', 'value' => true],
], [['t' => 'DEPLOY', 'p' => 1, 'cardID' => 'SOR_005', 'epic' => true, 'pilotHostUid' => null]], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#5', 'from' => 'base', 'to' => 'ground', 'p' => 1, 'kind' => 'unit', 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.0b', 't' => 'STATS', 'card' => 'SOR#5', 'power' => 4, 'hp' => 7, 'keywords' => [], 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#5', 'zone' => 'ground', 'epic' => true],
], 'deploy: MOVE base→ground · STATS · DEPLOY_LEADER');
SwuPgnTestEq($r['gaps'], [], 'the deploy claims the leader flags');

// ── Capture and rescue ───────────────────────────────────────────────────────────────────────────
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_095', 7); MPlace($ctx, 2, 'ground', 'SOR_049', 20);
$r = SwuPgnMatch([['k' => 'capture', 'seat' => 2, 'cardID' => 'SOR_095', 'uid' => 7, 'captor' => 20, 'from' => 'ground']],
    [['t' => 'CAPTURE', 'p' => 2, 'cardID' => 'SOR_095', 'by' => 20]], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#95', 'from' => 'ground', 'to' => 'capture', 'p' => 1, 'kind' => 'unit'],
    ['seq' => 'R1.A.0b', 't' => 'CAPTURE', 'p' => 2, 'card' => 'SOR#95', 'by' => 'SOR#49:2'],
], 'capture: MOVE arena→capture · CAPTURE(by)');
$r = SwuPgnMatch([
    ['k' => 'rescue', 'seat' => 1, 'cardID' => 'SOR_095', 'captor' => 20, 'uid' => 31, 'to' => 'ground', 'exhausted' => true],
    $stats(31),
], [['t' => 'RESCUE', 'p' => 1, 'cardID' => 'SOR_095']], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R1.A.0c', 't' => 'RESCUE', 'p' => 1, 'card' => 'SOR#95'],
    ['seq' => 'R1.A.0d', 't' => 'MOVE', 'card' => 'SOR#95', 'from' => 'capture', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'],
    ['seq' => 'R1.A.0e', 't' => 'STATS', 'card' => 'SOR#95', 'power' => 3, 'hp' => 3, 'keywords' => []],
    ['seq' => 'R1.A.0f', 't' => 'EXHAUST', 'card' => 'SOR#95'],
], 'rescue: RESCUE · MOVE capture→arena · STATS · EXHAUST; the same copy id comes back');
SwuPgnTestEq(SwuPgnLedgerByUid($ctx['ledger'], 31), 'SOR#95', 'the rescued copy is bound to its new UID');

// ── Take control ─────────────────────────────────────────────────────────────────────────────────
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_095', 7);
$r = SwuPgnMatch([['k' => 'control', 'seat' => 2, 'uid' => 7, 'cardID' => 'SOR_095', 'from' => 1, 'to' => 2]],
    [['t' => 'CONTROL', 'p' => 2, 'uid' => 7]], $ctx);
SwuPgnTestEq($r['events'], [['seq' => 'R1.A.0a', 't' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'SOR#95', 'zone' => 'ground']], 'take control: TAKE_CONTROL(zone)');
SwuPgnTestEq([SwuPgnLedgerByUid($ctx['ledger'], 7), $ctx['ledger']['zones'][2]['ground'] ?? []], ['SOR#95', ['SOR#95']], 'the copy moves seats in the ledger');

// ── Pass, claim, Credits ─────────────────────────────────────────────────────────────────────────
$ctx = MCtx();
$r1 = SwuPgnMatch([], [['t' => 'PASS', 'p' => 2]], $ctx);
$r2 = SwuPgnMatch([['k' => 'initiative', 'seat' => 1, 'taken' => true]], [['t' => 'CLAIM', 'p' => 1]], $ctx);
SwuPgnTestEq(array_merge($r1['events'], $r2['events']), [
    ['seq' => 'R1.A.1', 't' => 'PASS', 'p' => 2],
    ['seq' => 'R1.A.2', 't' => 'CLAIM_INITIATIVE', 'p' => 1],
], 'PASS and CLAIM_INITIATIVE are numbered actions');
SwuPgnTestEq($r2['gaps'], [], 'the claim note claims the initiative change');
$r = SwuPgnMatch([['k' => 'credit', 'seat' => 1, 'delta' => 1]], [], $ctx);
SwuPgnTestEq($r['events'], [['seq' => 'R1.A.2a', 't' => 'MOVE', 'card' => SwuPgnCardRef('LAW_T01'), 'from' => 'outsideTheGame', 'to' => 'base', 'p' => 1]],
    'a Credit token is a MOVE into base');
SwuPgnTestEq($r['gaps'], [], 'Credits are structural, not gaps');

// ── Mulligan (setup: every record numbered) ──────────────────────────────────────────────────────
$ctx = MCtx('S', 0); MPlace($ctx, 1, 'hand', 'SOR_095');
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'deck'],
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'deck', 'to' => 'hand'],
], [['t' => 'MULLIGAN', 'p' => 1], ['t' => 'DRAW', 'p' => 1, 'n' => 1]], $ctx);
SwuPgnTestEq($r['events'], [
    ['seq' => 'R0.S.1', 't' => 'MULLIGAN', 'p' => 1],
    ['seq' => 'R0.S.2', 't' => 'MOVE', 'card' => 'SOR#95', 'from' => 'hand', 'to' => 'deck', 'p' => 1, 'kind' => 'unit'],
    ['seq' => 'R0.S.3', 't' => 'SHUFFLE', 'p' => 1],
    ['seq' => 'R0.S.4', 't' => 'MOVE', 'card' => 'SOR#120', 'from' => 'deck', 'to' => 'hand', 'p' => 1, 'kind' => 'upgrade'],
    ['seq' => 'R0.S.5', 't' => 'DRAW', 'p' => 1, 'count' => 1, 'cards' => ['SOR#120']],
], 'mulligan: MULLIGAN · MOVE hand→deck · SHUFFLE · MOVE deck→hand · DRAW');

// ── An Ambush unit: the play's records must not be filed under the attack ────────────────────────
$ctx = MCtx(); MPlace($ctx, 1, 'hand', 'SOR_095'); MPlace($ctx, 2, 'ground', 'SOR_096', 8);
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'ground', 'uid' => 7, 'exhausted' => true],
    $stats(7),
], [
    ['t' => 'PLAY', 'p' => 1, 'cardID' => 'SOR_095', 'how' => 'unit', 'uid' => 7, 'targetUid' => null, 'cost' => 2],
    ['t' => 'ATTACK', 'p' => 1, 'atkUid' => 7, 'def' => 8],
], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['seq'], $e['t'], $e['for'] ?? null], $r['events']), [
    ['R1.A.0a', 'MOVE', 'R1.A.1'], ['R1.A.0b', 'STATS', 'R1.A.1'], ['R1.A.1', 'PLAY', null], ['R1.A.1a', 'EXHAUST', null], ['R1.A.2', 'ATTACK', null],
], 'the walk-back stops at the play (an Ambush attack does not claim the play\'s EXHAUST)');

// A run that does not name the action's card is left unlinked (§9.1: a wrong link is worse than none).
$ctx = MCtx();
$r = SwuPgnMatch([], [['t' => 'CHOICE', 'p' => 1, 'offered' => ['base@2'], 'chose' => 0], ['t' => 'PASS', 'p' => 1]], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['seq'], $e['t'], $e['for'] ?? null], $r['events']), [['R1.A.0a', 'CHOICE', null], ['R1.A.1', 'PASS', null]],
    'no anchor, no for');

// ── SwuPgnNote: one boolean check when the game is not recorded ──────────────────────────────────
unset($GLOBALS['SWU_PGN_RECORDING']); $GLOBALS['SWU_PGN_NOTES'] = [];
SwuPgnNote(['t' => 'PASS', 'p' => 1]);
SwuPgnTestEq($GLOBALS['SWU_PGN_NOTES'], [], 'an unrecorded game queues no notes');
$GLOBALS['SWU_PGN_RECORDING'] = true;
SwuPgnNote(['t' => 'PASS', 'p' => 1]); SwuPgnNote(['t' => 'CLAIM', 'p' => 2]);
SwuPgnTestEq($GLOBALS['SWU_PGN_NOTES'], [['t' => 'PASS', 'p' => 1], ['t' => 'CLAIM', 'p' => 2]], 'a recorded game queues notes in order');
unset($GLOBALS['SWU_PGN_RECORDING']); $GLOBALS['SWU_PGN_NOTES'] = [];

// ── A base is a host (SWU-PGN 1.0 base-host revision) ──────────────────────────────────────────────
// An upgrade played onto a base: its MOVE into base is attachedTo base@N, and PLAY_UPGRADE's target is base@N.
$ctx = MCtx(); SwuPgnLedgerPut($ctx['ledger'], 2, 'hand', SwuPgnLedgerNewToken($ctx['ledger'], 'HMW_206'));
$r = SwuPgnMatch([
    ['k' => 'move', 'seat' => 2, 'cardID' => 'HMW_206', 'from' => 'hand', 'to' => 'base'],
    ['k' => 'resources', 'seat' => 2, 'exhaustedDelta' => 1],
], [['t' => 'PLAY', 'p' => 2, 'cardID' => 'HMW_206', 'how' => 'upgrade', 'uid' => null, 'targetUid' => null, 'cost' => 1]], $ctx);
SwuPgnTestEq(array_values(array_filter($r['events'], fn($e) => in_array($e['t'], ['MOVE', 'PLAY_UPGRADE'], true))), [
    ['seq' => 'R1.A.0b', 't' => 'MOVE', 'card' => 'HMW#206', 'from' => 'hand', 'to' => 'base', 'p' => 2, 'kind' => 'upgrade', 'attachedTo' => 'base@2', 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'HMW#206', 'zone' => 'base', 'target' => 'base@2', 'cost' => 1],
], 'a base upgrade: MOVE into base attachedTo base@2 · PLAY_UPGRADE target base@2');
// Defeated, it leaves host-less, the way every upgrade does.
$r = SwuPgnMatch([['k' => 'move', 'seat' => 2, 'cardID' => 'HMW_206', 'from' => 'base', 'to' => 'discard']], [], $ctx);
SwuPgnTestEq(array_values(array_filter($r['events'], fn($e) => $e['t'] === 'MOVE')), [
    ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'HMW#206', 'from' => 'base', 'to' => 'discard', 'p' => 2, 'kind' => 'upgrade'],
], 'a base upgrade leaving base names no host');
// A leader coming home is not a base attach.
$ctx = MCtx(); MPlace($ctx, 1, 'ground', 'SOR_046', 9);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_046', 'uid' => 9, 'from' => 'ground', 'to' => 'base']], [], $ctx);
SwuPgnTestCheck(!array_key_exists('attachedTo', $r['events'][0] ?? []), 'a unit card moving into base is not attached to it');
// A unit a base captured (Arrest): CAPTURE by base@N.
$ctx = MCtx(); MPlace($ctx, 2, 'ground', 'SOR_096', 20);
$r = SwuPgnMatch([['k' => 'capture', 'seat' => 1, 'cardID' => 'SOR_096', 'uid' => 20, 'captor' => 'base@1', 'from' => 'ground']],
    [['t' => 'CAPTURE', 'p' => 1, 'cardID' => 'SOR_096', 'by' => 'base@1']], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['by'] ?? $e['to'] ?? null], $r['events']), [['MOVE', 'capture'], ['CAPTURE', 'base@1']],
    'a base capture: MOVE arena→capture · CAPTURE by base@1');

SwuPgnTestFinish();
