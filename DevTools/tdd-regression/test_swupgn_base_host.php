<?php
// SWU-PGN/1.0 (base-host revision): a base is a host. An upgrade played onto it (Fortify) is attached to `base@N`
// and held in that seat's baseUpgrades; a unit a base captures (Arrest) is held in baseCaptured until rescued.
// Fold (§12), the keyframe check (§14), validate and the story (§16).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_base_host.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

function BF(array $events, ?array $kf = null): array
{
    if ($kf !== null) array_unshift($events, ['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf]);
    $doc = SwuPgnParse(SwuPgnTestFile(SwuPgnTestSeq($events)));
    return SwuPgnFold($doc['events']);
}
function BP(array $s, int $seat): array { return $s['players'][$seat]; }
function BM(string $card, string $from, string $to, ?int $p = 2, array $x = []): array
{
    $e = ['t' => 'MOVE', 'card' => $card, 'from' => $from, 'to' => $to];
    if ($p !== null) $e['p'] = $p;
    return $e + $x;
}
$fortify = [BM('HMW#206', 'hand', 'base', 2, ['kind' => 'upgrade', 'attachedTo' => 'base@2']),
    ['t' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'HMW#206', 'zone' => 'base', 'target' => 'base@2', 'cost' => 1]];
$kf = SwuPgnTestKeyframe([], ['hand' => ['HMW#206'], 'handSize' => 1]);

// ── fold: the upgrade on the base ───────────────────────────────────────────────────────────────
$s = BF($fortify, $kf);
SwuPgnTestEq([BP($s, 2)['baseUpgrades'] ?? null, BP($s, 2)['handSize'], BP($s, 2)['cards']], [['HMW#206'], 0, []],
    '§12.1 step 3: an upgrade MOVE into base attachedTo base@2 goes onto seat 2\'s baseUpgrades, once (PLAY_UPGRADE beside it is idempotent), never into cards');
SwuPgnTestCheck(!array_key_exists('baseUpgrades', BP($s, 1)), 'the other seat\'s baseUpgrades stays absent (absent reads as [])');
$s = BF([$fortify[0]], $kf);
SwuPgnTestEq(BP($s, 2)['baseUpgrades'] ?? null, ['HMW#206'], '§12.1 step 3: the attaching MOVE alone puts it on the base');
$s = BF([['t' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'HMW#206', 'zone' => 'base', 'target' => 'base@2']], $kf);
SwuPgnTestEq(BP($s, 2)['baseUpgrades'] ?? null, ['HMW#206'], '§12.2 PLAY_UPGRADE with target base@N attaches on its own');
$s = BF(array_merge($fortify, [BM('HMW#206', 'base', 'discard', 2, ['kind' => 'upgrade']), ['t' => 'DEFEAT', 'card' => 'HMW#206', 'reason' => 'ability']]), $kf);
SwuPgnTestEq([BP($s, 2)['baseUpgrades'] ?? null, BP($s, 2)['discard']], [[], ['HMW#206']], '§12.1 step 0: a MOVE out of base detaches it; the discard pile gets it');
$s = BF(array_merge($fortify, [BM('HMW#206', 'base', 'hand', 2, ['kind' => 'upgrade'])]), $kf);
SwuPgnTestEq([BP($s, 2)['baseUpgrades'] ?? null, BP($s, 2)['hand']], [[], ['HMW#206']], '§12.1 step 0: the MOVE out of base alone (returned to hand) detaches it');
$s = BF(array_merge($fortify, [['t' => 'DEFEAT', 'card' => 'HMW#206', 'reason' => 'ability']]), $kf);
SwuPgnTestEq(BP($s, 2)['baseUpgrades'] ?? null, [], '§12.2 DEFEAT detaches from baseUpgrades too');
$s = BF([BM('TOKEN:credit#1', 'outsideTheGame', 'base', 2), BM('TOKEN:credit#1', 'base', 'outsideTheGame', 2)], $kf);
SwuPgnTestEq([BP($s, 2)['credits'], array_key_exists('baseUpgrades', BP($s, 2))], [0, false], 'a Credit token through base touches credits only');

// ── fold: a unit the base captured ──────────────────────────────────────────────────────────────
$kfCap = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095')]], ['cards' => [SwuPgnTestCard('SOR#045')]]);
$cap = [BM('SOR#045', 'ground', 'capture', 2), ['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045', 'by' => 'base@1']];
$s = BF($cap, $kfCap);
SwuPgnTestEq([BP($s, 1)['baseCaptured'] ?? null, BP($s, 2)['cards']], [['SOR#045'], []], '§12.2 CAPTURE by base@1: the unit leaves play and is held on seat 1\'s baseCaptured');
$s = BF(array_merge($cap, [['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045', 'by' => 'base@1']]), $kfCap);
SwuPgnTestEq(BP($s, 1)['baseCaptured'] ?? null, ['SOR#045'], 'idempotently');
$s = BF(array_merge($cap, [['t' => 'RESCUE', 'p' => 2, 'card' => 'SOR#045'], BM('SOR#045', 'capture', 'ground', 2)]), $kfCap);
SwuPgnTestEq([BP($s, 1)['baseCaptured'] ?? null, count(BP($s, 2)['cards'])], [[], 1], 'RESCUE detaches it from the base; the MOVE out of capture places it again');
$s = BF(array_merge($cap, [BM('SOR#045', 'capture', 'ground', 2)]), $kfCap);
SwuPgnTestEq(BP($s, 1)['baseCaptured'] ?? null, [], '§12.1 step 0: the MOVE out of capture alone also takes it off the base');

// ── keyframes: snapped, and checked as sets ─────────────────────────────────────────────────────
$kfHeld = SwuPgnTestKeyframe(['baseCaptured' => ['SOR#045']], ['baseUpgrades' => ['HMW#206', 'HMW#172']]);
$s = BF([], $kfHeld);
SwuPgnTestEq([BP($s, 1)['baseCaptured'], BP($s, 2)['baseUpgrades']], [['SOR#045'], ['HMW#206', 'HMW#172']], 'a keyframe supplies baseUpgrades / baseCaptured');
$chk = function (array $events): array {
    $doc = SwuPgnParse(SwuPgnTestFile(SwuPgnTestSeq($events)));
    return SwuPgnCheckKeyframes($doc['events']);
};
$end = fn(array $kf2) => ['t' => 'ROUND_END', 'round' => 1, 'keyframe' => $kf2];
$kf0 = SwuPgnTestKeyframe();   // the first keyframe is compared against the empty board, so it starts empty
$r = $chk(array_merge([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf0]], $fortify, [$end(SwuPgnTestKeyframe([], ['baseUpgrades' => ['HMW#206']]))]));
SwuPgnTestEq([$r['ok'], $r['mismatches']], [true, []], '§14: a fold that matches the keyframe\'s baseUpgrades passes');
$r = $chk([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf0], $end(SwuPgnTestKeyframe([], ['baseUpgrades' => ['HMW#206']]))]);
SwuPgnTestEq(array_column($r['mismatches'], 'path'), ['players.2.baseUpgrades'], '§14: a baseUpgrades the deltas never built is a mismatch');
$r = $chk([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf0], $end(SwuPgnTestKeyframe(['baseCaptured' => ['SOR#045']]))]);
SwuPgnTestEq(array_column($r['mismatches'], 'path'), ['players.1.baseCaptured'], '§14: baseCaptured is compared too');
$r = $chk(array_merge([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf0]], $fortify,
    [BM('HMW#172', 'hand', 'base', 2, ['kind' => 'upgrade', 'attachedTo' => 'base@2']), $end(SwuPgnTestKeyframe([], ['baseUpgrades' => ['HMW#172', 'HMW#206']]))]));
SwuPgnTestEq($r['mismatches'], [], '§14: as sets — order does not matter');
SwuPgnTestCheck(SwuPgnKeyframeProblem(SwuPgnTestViaJson(SwuPgnTestKeyframe([], ['baseUpgrades' => array_map(fn($i) => "SOR#$i", range(1, SWUPGN_MAX_ATTACHED + 1))]))) !== null,
    'a keyframe baseUpgrades beyond the reader\'s limits is refused like any other list');

// ── validate ────────────────────────────────────────────────────────────────────────────────────
$codes = function (array $events): array {
    $v = SwuPgnValidate(SwuPgnParse(SwuPgnTestFile(SwuPgnTestSeq($events))));
    $all = array_column(array_merge($v['errors'], $v['warnings']), 'code');
    return array_values(array_unique(array_filter($all, fn($c) => $c !== 'section.missing')));   // the fixture has no DECKS
};
SwuPgnTestEq($codes($fortify), [], 'validate: the base-host MOVE + PLAY_UPGRADE are clean');
SwuPgnTestCheck(in_array('schema', $codes([BM('HMW#206', 'hand', 'base', 2, ['kind' => 'upgrade'])]), true),
    'validate: an upgrade MOVE into base must name its host');
SwuPgnTestCheck(in_array('schema', $codes([BM('HMW#206', 'base', 'discard', 2, ['kind' => 'upgrade', 'attachedTo' => 'base@2'])]), true),
    'validate: a MOVE out of base is host-less');
SwuPgnTestCheck($codes([['t' => 'ROUND_START', 'round' => 1, 'active' => 1, 'keyframe' => SwuPgnTestKeyframe([], ['baseUpgrades' => 'HMW#206'])]]) !== [], 'validate: a keyframe baseUpgrades that is not a list of ids is an error');

// ── story ───────────────────────────────────────────────────────────────────────────────────────
$story = SwuPgnRender(SwuPgnParse(SwuPgnTestFile(SwuPgnTestSeq($fortify))), ['HMW#206' => 'Fortify']);
SwuPgnTestCheck(str_contains($story, "Player 2 plays Fortify on Player 2's base (cost 1)"), '§16: PLAY_UPGRADE onto a base reads "on Player N\'s base"', $story);

SwuPgnTestFinish();
