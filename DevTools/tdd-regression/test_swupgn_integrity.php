<?php
// SWU-PGN/1.0 spec §14: checkKeyframes() — what is compared, the first-keyframe exemptions,
// absent-vs-zero, sets vs order, damaged keyframes.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_integrity.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

function K(array $events): array
{
    $doc = SwuPgnParse(SwuPgnTestFile(SwuPgnTestSeq($events)));
    return SwuPgnCheckKeyframes($doc['events']);
}
// Mismatches from the seeding keyframe (compared against an empty fold) are dropped when a test is
// about a LATER keyframe.
function KL(array $events, string $seed = 'R1.start'): array
{
    $r = K($events);
    $r['mismatches'] = array_values(array_filter($r['mismatches'], function ($m) use ($seed) { return $m['seq'] !== $seed; }));
    $r['ok'] = !$r['mismatches'];
    return $r;
}
function RS(string $seq, array $kf): array { return ['seq' => $seq, 't' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf]; }
function RE(string $seq, array $kf): array { return ['seq' => $seq, 't' => 'ROUND_END', 'round' => 1, 'keyframe' => $kf]; }
function paths(array $r): array { return array_map(function ($m) { return $m['path']; }, $r['mismatches']); }
$leader = ['id' => 'SOR#010', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false];

// ── the documented mismatch shape ───────────────────────────────────────────────────────────────
$r = KL([RS('R6.start', SwuPgnTestKeyframe(['handSize' => 2])), RS('R7.start', SwuPgnTestKeyframe(['handSize' => 3]))], 'R6.start');
SwuPgnTestEq($r, ['ok' => false, 'mismatches' => [['seq' => 'R7.start', 'path' => 'players.1.handSize', 'expected' => 3, 'got' => 2]]], '§14: { seq, path, expected (keyframe), got (fold) } and ok:false');
$r = K([RS('R1.start', SwuPgnTestKeyframe())]);
SwuPgnTestEq($r, ['ok' => true, 'mismatches' => []], '§14: a consistent file is ok with no mismatches');

// ── first-keyframe exemptions ───────────────────────────────────────────────────────────────────
$kf1 = SwuPgnTestKeyframe(['baseHp' => 28, 'baseMaxHp' => 28, 'deckSize' => 40, 'leader' => $leader], ['baseHp' => 33, 'deckSize' => 41, 'leader' => $leader]);
$r = K([['t' => 'MOVE', 'card' => 'X#1', 'from' => 'deck', 'to' => 'hand', 'p' => 1], ['t' => 'MOVE', 'card' => 'X#1', 'from' => 'hand', 'to' => 'discard', 'p' => 1], RS('R1.start', $kf1 + [])]);
SwuPgnTestEq(paths($r), ['players.1.discard'], '§14: at the FIRST keyframe baseHp, deckSize and an unknown leader are exempt; everything else (discard) is still compared');
$kf2 = $kf1;
$kf2['players']['1']['baseHp'] = 27;
$kf2['players']['2']['deckSize'] = 40;
$r = K([RS('R1.start', $kf1), RE('R1.end', $kf2)]);
SwuPgnTestEq($r['mismatches'], [['seq' => 'R1.end', 'path' => 'players.1.baseHp', 'expected' => 27, 'got' => 28], ['seq' => 'R1.end', 'path' => 'players.2.deckSize', 'expected' => 40, 'got' => 41]], '§14: after the first keyframe baseHp and deckSize are compared');
$r = K([RS('R1.start', ['players' => ['1' => SwuPgnTestSeatKeyframe(1)]]), RS('R2.start', $kf1)]);
SwuPgnTestEq(paths($r), ['keyframe'], 'the first USABLE keyframe gets the exemptions — a damaged one before it supplies nothing');

// ── absent means "not recorded" (keyframe side), and fold-side defaults ─────────────────────────
$noDeck = SwuPgnTestKeyframe();
$r = K([RS('R1.start', $kf1), RE('R1.end', SwuPgnTestKeyframe(['baseHp' => 28, 'baseMaxHp' => 28, 'leader' => $leader], ['baseHp' => 33, 'leader' => $leader]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: a field the keyframe does not carry (deckSize) is skipped');
$r = K([RS('R1.start', $noDeck), RE('R1.end', SwuPgnTestKeyframe(['deckSize' => 3]))]);
SwuPgnTestEq($r['mismatches'], [['seq' => 'R1.end', 'path' => 'players.1.deckSize', 'expected' => 3]], '§14: a field the fold never had is reported with NO `got` (absent, not 0)');
$r = K([RS('R1.start', SwuPgnTestKeyframe([], [], ['initiativeTaken' => false]) + [])]);
SwuPgnTestEq($r['mismatches'], [], '§14: initiativeTaken — the fold\'s "never claimed" (absent) matches false');
// Probed with a ROUND_END keyframe: a ROUND_START one describes the board after §12.2's reset.
$r = K([['t' => 'CLAIM_INITIATIVE', 'p' => 1], RE('R1.end', SwuPgnTestKeyframe())]);
SwuPgnTestEq($r['mismatches'], [['seq' => 'R1.end', 'path' => 'initiativeTaken', 'expected' => false, 'got' => true]], '§14: initiativeTaken is compared');
$r = K([RS('R1.start', SwuPgnTestKeyframe(['baseEpicActionUsed' => false])), RE('R1.end', SwuPgnTestKeyframe(['baseEpicActionUsed' => true]))]);
SwuPgnTestEq(paths($r), ['players.1.baseEpicActionUsed'], '§14: baseEpicActionUsed compared whenever the keyframe carries it');
$r = K([['t' => 'ABILITY_ACTIVATE', 'p' => 2, 'card' => 'base@2', 'kind' => 'epic', 'epic' => true], RS('R1.start', SwuPgnTestKeyframe([], ['baseEpicActionUsed' => true]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: the base Epic Action folded from ABILITY_ACTIVATE matches the keyframe');
$r = K([RS('R1.start', SwuPgnTestKeyframe())]);
SwuPgnTestEq($r['mismatches'], [], 'a keyframe with no baseEpicActionUsed skips it');
$r = K([RS('R1.start', SwuPgnTestKeyframe(['baseEpicActionUsed' => false]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: the fold\'s absent baseEpicActionUsed matches false');

// ── sets vs order ───────────────────────────────────────────────────────────────────────────────
$m = function (string $c, string $from, string $to, int $p = 1, array $x = []) { return ['t' => 'MOVE', 'card' => $c, 'from' => $from, 'to' => $to, 'p' => $p] + $x; };
$r = K([$m('A#1', 'deck', 'hand'), $m('A#2', 'deck', 'hand'), RS('R1.start', SwuPgnTestKeyframe(['handSize' => 2, 'hand' => ['A#2', 'A#1']]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: hand compared as a set');
$r = K([$m('A#1', 'hand', 'discard'), $m('A#2', 'hand', 'discard'), RS('R1.start', SwuPgnTestKeyframe(['discard' => ['A#2', 'A#1']]))]);
SwuPgnTestEq(paths($r), ['players.1.discard'], '§14: discard compared IN ORDER');
$r = K([$m('A#1', 'hand', 'resource'), $m('A#2', 'hand', 'resource'), RS('R1.start', SwuPgnTestKeyframe(['resourcesReady' => 2, 'resources' => ['A#2', 'A#1']]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: resources compared as a set when the keyframe carries it');
$r = K([$m('A#1', 'hand', 'resource'), RS('R1.start', SwuPgnTestKeyframe(['resourcesReady' => 1, 'resources' => ['A#9']]))]);
SwuPgnTestEq(paths($r), ['players.1.resources'], '§14: resources membership is gated');
$r = K([$m('A#1', 'hand', 'resource'), RS('R1.start', SwuPgnTestKeyframe(['resourcesReady' => 1]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: no resources in the keyframe → not compared');
$r = K([$m('A#1', 'hand', 'resource'), ['t' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 1], RS('R1.start', SwuPgnTestKeyframe(['resourcesReady' => 1]))]);
SwuPgnTestEq(paths($r), ['players.1.resourcesReady', 'players.1.resourcesExhausted'], '§14: resourcesReady and resourcesExhausted are compared');
$r = K([$m('TOKEN:credit#1', 'outsideTheGame', 'base', 2), $m('TOKEN:the-force#2', 'outsideTheGame', 'base', 2), RS('R1.start', SwuPgnTestKeyframe())]);
SwuPgnTestEq(paths($r), ['players.2.credits', 'players.2.hasForce'], '§14: credits and hasForce are compared');

// ── leader ──────────────────────────────────────────────────────────────────────────────────────
$kfL = SwuPgnTestKeyframe(['leader' => $leader]);
$r = K([RS('R1.start', $kfL), ['t' => 'EXHAUST', 'card' => 'SOR#010'], RE('R1.end', $kfL)]);
SwuPgnTestEq($r['mismatches'], [['seq' => 'R1.end', 'path' => 'players.1.leader.exhausted', 'expected' => false, 'got' => true]], '§14: leader fields compared');
$r = K([RS('R1.start', SwuPgnTestKeyframe()), RE('R1.end', $kfL)]);
SwuPgnTestEq($r['mismatches'], [['seq' => 'R1.end', 'path' => 'players.1.leader', 'expected' => $leader]], '§14: after the first keyframe a leader the fold lacks is a mismatch (no got)');
$kfF = SwuPgnTestKeyframe(['leader' => $leader + ['onStartingSide' => true]]);
$r = K([RS('R1.start', $kfL), RE('R1.end', $kfF)]);
SwuPgnTestEq($r['mismatches'], [['seq' => 'R1.end', 'path' => 'players.1.leader.onStartingSide', 'expected' => true]], '§14: onStartingSide compared when the keyframe states it; the fold\'s absent is NOT false');
$r = K([RS('R1.start', $kfF), ['t' => 'LEADER_FLIP', 'p' => 1, 'card' => 'SOR#010', 'onStartingSide' => false], RE('R1.end', $kfL)]);
SwuPgnTestEq($r['mismatches'], [], '§14: a keyframe without onStartingSide does not compare it');
$r = K([RS('R1.start', $kfF), ['t' => 'LEADER_FLIP', 'p' => 1, 'card' => 'SOR#010', 'onStartingSide' => false], RE('R1.end', $kfF)]);
SwuPgnTestEq(paths($r), ['players.1.leader.onStartingSide'], '§14: a flip the keyframe does not reflect is caught');
$r = K([RS('R1.start', $kfL), RE('R1.end', SwuPgnTestKeyframe(['leader' => ['id' => 'SOR#011'] + $leader]))]);
SwuPgnTestEq(paths($r), ['players.1.leader.id'], '§14: leader id compared');

// ── in-play cards ───────────────────────────────────────────────────────────────────────────────
$card = function (array $over = []) { return SwuPgnTestCard('SOR#095', 'ground', $over + ['power' => 3, 'hp' => 3, 'keywords' => ['raid 1', 'sentinel']]); };
$kfC = SwuPgnTestKeyframe(['cards' => [$card()]]);
$r = KL([RS('R1.start', $kfC), RE('R1.end', $kfC)]);
SwuPgnTestEq($r['mismatches'], [], 'cards: an unchanged card matches');
$r = KL([RS('R1.start', $kfC), RE('R1.end', SwuPgnTestKeyframe(['cards' => [$card(['zone' => 'space', 'damage' => 1, 'exhausted' => true, 'shields' => 1, 'experience' => 2, 'power' => 4, 'hp' => 5])]]))]);
SwuPgnTestEq(paths($r), ['players.1.cards[SOR#095].zone', 'players.1.cards[SOR#095].damage', 'players.1.cards[SOR#095].exhausted', 'players.1.cards[SOR#095].shields', 'players.1.cards[SOR#095].experience', 'players.1.cards[SOR#095].power', 'players.1.cards[SOR#095].hp'], '§14: zone, damage, exhausted, shields, experience, power, hp compared per card');
$r = KL([RS('R1.start', $kfC), RE('R1.end', SwuPgnTestKeyframe(['cards' => [$card(['keywords' => ['sentinel', 'raid 1']])]]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: keywords compared as a set');
$r = KL([RS('R1.start', $kfC), ['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'advantage', 'count' => 1], RE('R1.end', $kfC)]);
SwuPgnTestEq(paths($r), ['players.1.cards[SOR#095].statusTokens'], '§14: statusTokens compared');
$r = KL([RS('R1.start', $kfC), ['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'advantage', 'count' => 1], ['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'advantage', 'count' => -1], RE('R1.end', $kfC)]);
SwuPgnTestEq($r['mismatches'], [], 'token rule 4: gain then removal passes against a keyframe\'s {} (the key was deleted)');
$r = KL([RS('R1.start', SwuPgnTestKeyframe(['cards' => [$card(['statusTokens' => ['advantage' => 0]])]])), RE('R1.end', $kfC)]);
SwuPgnTestEq(paths($r), ['players.1.cards[SOR#095].statusTokens'], 'token rule 4: {"advantage":0} is NOT equal to {}');
$r = KL([RS('R1.start', SwuPgnTestKeyframe(['cards' => [$card(['upgrades' => ['A#1', 'A#2'], 'captured' => ['B#1']])]])), RE('R1.end', SwuPgnTestKeyframe(['cards' => [$card(['upgrades' => ['A#2', 'A#1'], 'captured' => ['B#1']])]]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: upgrades and captured compared as sets');
$r = KL([RS('R1.start', SwuPgnTestKeyframe(['cards' => [$card(['captured' => null])]])), RE('R1.end', SwuPgnTestKeyframe(['cards' => [$card(['captured' => []])]]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: a missing captured list counts as empty');
$r = KL([RS('R1.start', $kfC), ['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'A#1', 'target' => 'SOR#095'], RE('R1.end', $kfC)]);
SwuPgnTestEq(paths($r), ['players.1.cards[SOR#095].upgrades'], '§14: upgrades are gated');
$r = KL([RS('R1.start', $kfC), RE('R1.end', SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095')]]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: a keyframe card without power/hp/keywords skips them');
$r = KL([RS('R1.start', SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095')]])), RE('R1.end', $kfC)]);
SwuPgnTestEq($r['mismatches'], [['seq' => 'R1.end', 'path' => 'players.1.cards[SOR#095].power', 'expected' => 3], ['seq' => 'R1.end', 'path' => 'players.1.cards[SOR#095].hp', 'expected' => 3], ['seq' => 'R1.end', 'path' => 'players.1.cards[SOR#095].keywords', 'expected' => ['raid 1', 'sentinel']]], '§14: stats the fold never received are mismatches without `got`');
$r = KL([RS('R1.start', $kfC), $m('SOR#095', 'ground', 'discard'), $m('SOR#096', 'hand', 'space', 2), RE('R1.end', SwuPgnTestKeyframe(['cards' => [$card()], 'discard' => ['SOR#095']]))]);
SwuPgnTestEq($r['mismatches'], [
    ['seq' => 'R1.end', 'path' => 'players.1.cards[SOR#095]', 'expected' => 'in play', 'got' => 'not in play'],
    ['seq' => 'R1.end', 'path' => 'players.2.cards[SOR#096]', 'expected' => 'not in play', 'got' => 'in play'],
], '§14: a card in one side but not the other is reported both ways');
$r = KL([RS('R1.start', $kfC), ['t' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'SOR#095', 'zone' => 'ground'], RE('R1.end', $kfC)]);
SwuPgnTestEq(paths($r), ['players.1.cards[SOR#095]', 'players.2.cards[SOR#095]'], '§14: cards are matched per seat — a controller change shows up');

// ── damaged keyframes (§13/§14) ─────────────────────────────────────────────────────────────────
$r = K([RS('R1.start', SwuPgnTestKeyframe()), $m('A#1', 'deck', 'hand'), RE('R1.end', SwuPgnTestKeyframe(['hand' => 'A#1'])), RS('R2.start', SwuPgnTestKeyframe(['handSize' => 1, 'hand' => ['A#1']]))]);
SwuPgnTestEq(count($r['mismatches']), 1, '§14: a damaged keyframe is ONE mismatch and is not compared field by field');
SwuPgnTestEq([$r['mismatches'][0]['seq'], $r['mismatches'][0]['path']], ['R1.end', 'keyframe'], '§14: … with path "keyframe"');
SwuPgnTestCheck(is_string($r['mismatches'][0]['got'] ?? null), 'the damaged-keyframe mismatch says what is wrong in `got`');
$r = K([RS('R1.start', SwuPgnTestKeyframe()), RE('R1.end', ['players' => new stdClass()]), $m('A#1', 'deck', 'hand'), RS('R2.start', SwuPgnTestKeyframe(['handSize' => 1, 'hand' => ['A#1']]))]);
SwuPgnTestEq(paths($r), ['keyframe'], '§13: after a damaged keyframe folding carries on from the running state (no snap to the empty players)');

// ── recovery: a keyframe re-syncs after under-recording ─────────────────────────────────────────
$r = K([RS('R1.start', SwuPgnTestKeyframe()), RE('R1.end', SwuPgnTestKeyframe(['handSize' => 2, 'hand' => ['A#1', 'A#2']])), $m('A#3', 'deck', 'hand'), RS('R2.start', SwuPgnTestKeyframe(['handSize' => 3, 'hand' => ['A#1', 'A#2', 'A#3']]))]);
SwuPgnTestEq(array_column($r['mismatches'], 'seq'), ['R1.end', 'R1.end'], '§13: the snap after a mismatch makes the next keyframe line up again');

// §12.2 "ROUND_START → initiativeTaken = false": a ROUND_START keyframe describes the board AFTER its own
// rule, so a round that claimed the initiative does not make the next round's keyframe mismatch.
$claimed = SwuPgnTestKeyframe([], [], ['initiativeTaken' => true]);
$fresh = SwuPgnTestKeyframe([], [], ['initiativeTaken' => false]);
$r = KL([RS('R1.start', $fresh), ['t' => 'CLAIM_INITIATIVE', 'p' => 1], RE('R1.end', $claimed), ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => $fresh]]);
SwuPgnTestEq($r['mismatches'], [], '§12.2: the next ROUND_START keyframe (initiative available again) does not mismatch a claimed round');
$r = KL([RS('R1.start', $fresh), ['t' => 'CLAIM_INITIATIVE', 'p' => 1], RE('R1.end', $fresh)]);
SwuPgnTestEq(paths($r), ['initiativeTaken'], 'a ROUND_END keyframe that forgets the claim still mismatches');

SwuPgnTestFinish();
