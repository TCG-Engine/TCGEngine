<?php
// Untrusted input: users upload .swupgn files. Beyond SwuPgnParse()'s four documented errors
// (SWU-PGN/1.0 spec §4) nothing may throw — not even a PHP warning (the test helper promotes
// warnings to exceptions) — and no crafted file may make a list grow without bound.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_untrusted.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

// Runs every reader entry point over a parsed doc; returns the first throwable's message or null.
function exerciseAll(array $doc): ?string
{
    try {
        $w = null;
        SwuPgnFold($doc['events'], $w);
        SwuPgnCheckKeyframes($doc['events']);
        SwuPgnValidate($doc);
        SwuPgnRender($doc);
        SwuPgnStoryMatches($doc);
        $tl = SwuPgnTimeline($doc['events']);
        for ($i = -1; $i <= $tl['count']; $i += 3) SwuPgnTimelineStateAt($tl, $i);
        foreach (array_slice($doc['events'], 0, 40) as $e) if (is_array($e) && is_string($e['seq'] ?? null)) SwuPgnStateAt($doc['events'], $e['seq']);
        SwuPgnStateToJson(SwuPgnFold($doc['events']));
        return null;
    } catch (Throwable $e) {
        return get_class($e) . ': ' . $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine();
    }
}

// ── type fuzz: every known type, every field, hostile values ────────────────────────────────────
$types = array_merge(array_keys(_SwuPgnEventShapes()), ['NEW_THING', 'INIT', '', '0']);
$fields = ['seq', 't', 'p', 'card', 'from', 'to', 'kind', 'attachedTo', 'exhausted', 'zone', 'cost', 'target', 'epic', 'onStartingSide',
    'by', 'amount', 'token', 'power', 'hp', 'src', 'tgt', 'amt', 'damageType', 'reason', 'defeatedBy', 'keywords', 'count', 'cards',
    'round', 'phase', 'winner', 'at', 'offered', 'chose', 'found', 'ms', 'for', 'atk', 'def', 'defenderType', 'keyframe'];
$hostile = [null, true, false, 0, -1, 1, 2, 3, PHP_INT_MAX, -PHP_INT_MAX, 1.5, 1e300, '', '1', 'ground', 'space', 'resource', 'base', 'hand',
    'capture', 'discard', 'deck', 'outsideTheGame', 'base@1', 'base@2', 'base@99', 'base@', 'TOKEN:', 'TOKEN:credit', 'TOKEN:the-force#1',
    'TOKEN:credit#1:2', 'SOR#108', 'SOR#108:2', 'upgrade', 'unit', [], [1, 2], ['a' => 1], ['SOR#108', null, 5], new stdClass(),
    str_repeat('x', 300), "\xff\xfe", 'Draw', 'epic', 'action', 'advantage', '__proto__', 'constructor', '0'];
mt_srand(99);
$failures = [];
for ($doc = 0; $doc < 150; $doc++) {
    $events = [];
    for ($k = 0; $k < 60; $k++) {
        $e = [];
        if (mt_rand(0, 9)) $e['seq'] = 'R1.A.' . mt_rand(0, 40);
        if (mt_rand(0, 19)) $e['t'] = $types[mt_rand(0, count($types) - 1)];
        $nf = mt_rand(0, 8);
        for ($f = 0; $f < $nf; $f++) $e[$fields[mt_rand(0, count($fields) - 1)]] = $hostile[mt_rand(0, count($hostile) - 1)];
        if (mt_rand(0, 3) === 0) $e['card'] = ['SOR#108', 'SOR#108:2', 'SOR#045', 'TOKEN:credit#1', 'base@1'][mt_rand(0, 4)];
        $events[] = $e;
    }
    $text = SwuPgnTestFile($events, ['cards' => [['id' => 'SOR#108', 'name' => 'Wampa']], 'story' => ['x'], 'annotations' => [['ref' => 'R1.A.1', 'line' => 5]]]);
    try {
        $parsed = SwuPgnParse($text);
    } catch (Throwable $e) {
        $failures[] = 'parse: ' . $e->getMessage();
        continue;
    }
    $err = exerciseAll($parsed);
    if ($err !== null) $failures[] = $err;
}
SwuPgnTestEq(array_slice(array_unique($failures), 0, 5), [], '150 files × 60 records with hostile field values: fold, integrity, validate, render, timeline and stateAt never throw or warn');

// ── keyframe fuzz: complete-shaped keyframes with hostile contents ──────────────────────────────
$failures = [];
mt_srand(5);
$seatFields = ['seat', 'baseHp', 'baseMaxHp', 'handSize', 'hand', 'deckSize', 'resourcesReady', 'resourcesExhausted', 'resources', 'credits', 'hasForce', 'discard', 'leader', 'baseEpicActionUsed'];
$cardFields = ['id', 'zone', 'damage', 'exhausted', 'upgrades', 'shields', 'experience', 'statusTokens', 'captured', 'power', 'hp', 'keywords'];
for ($doc = 0; $doc < 150; $doc++) {
    $kf = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#108'), SwuPgnTestCard('SOR#045', 'space')]], ['cards' => [SwuPgnTestCard('SOR#200')]]);
    for ($k = 0; $k < 6; $k++) {
        $s = (string)mt_rand(1, 2);
        if (mt_rand(0, 1)) {
            $f = $seatFields[mt_rand(0, count($seatFields) - 1)];
            $kf['players'][$s][$f] = $hostile[mt_rand(0, count($hostile) - 1)];
        } elseif ($kf['players'][$s]['cards'] && is_array($kf['players'][$s]['cards'])) {
            $f = $cardFields[mt_rand(0, count($cardFields) - 1)];
            $kf['players'][$s]['cards'][0][$f] = $hostile[mt_rand(0, count($hostile) - 1)];
        }
        if (mt_rand(0, 5) === 0) $kf[['round', 'phase', 'initiative', 'initiativeTaken', 'active'][mt_rand(0, 4)]] = $hostile[mt_rand(0, count($hostile) - 1)];
    }
    $events = SwuPgnTestSeq([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf],
        ['t' => 'MOVE', 'card' => 'SOR#108', 'from' => 'ground', 'to' => 'discard', 'p' => 1], ['t' => 'MOVE', 'card' => 'SOR#300', 'from' => 'deck', 'to' => 'hand', 'p' => 2],
        ['t' => 'MOVE', 'card' => 'SOR#301', 'from' => 'hand', 'to' => 'resource', 'p' => 1], ['t' => 'MOVE', 'card' => 'SOR#301', 'from' => 'resource', 'to' => 'hand', 'p' => 1, 'exhausted' => true],
        ['t' => 'MOVE', 'card' => 'TOKEN:credit#1', 'from' => 'outsideTheGame', 'to' => 'base', 'p' => 1],
        ['t' => 'DAMAGE', 'src' => 'x', 'tgt' => 'SOR#045', 'amt' => 3, 'damageType' => 'combat', 'hp' => 1], ['t' => 'HEAL', 'tgt' => 'SOR#200', 'amt' => 1, 'hp' => 1],
        ['t' => 'STATUS_TOKEN', 'card' => 'SOR#045', 'token' => 'advantage', 'count' => 1], ['t' => 'SHIELD_GAIN', 'card' => 'SOR#045'], ['t' => 'EXPERIENCE_GAIN', 'card' => 'SOR#200', 'count' => 1],
        ['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'U#1', 'target' => 'SOR#045'], ['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#200', 'by' => 'SOR#045'], ['t' => 'RESCUE', 'p' => 2, 'card' => 'SOR#200'],
        ['t' => 'EXHAUST', 'card' => 'SOR#010'], ['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010'], ['t' => 'LEADER_FLIP', 'p' => 2, 'card' => 'SOR#005', 'onStartingSide' => false],
        ['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'epic' => true], ['t' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'SOR#045', 'zone' => 'space'],
        ['t' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'R', 'zone' => 'resource', 'from' => 1], ['t' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2], ['t' => 'READY_RESOURCES', 'p' => 1, 'amount' => 2],
        ['t' => 'DRAW', 'p' => 1, 'count' => 1, 'cards' => ['SOR#400']], ['t' => 'DISCARD', 'p' => 2, 'cards' => ['SOR#401']], ['t' => 'STATS', 'card' => 'SOR#045', 'power' => 1, 'hp' => 2, 'keywords' => ['b', 'a']],
        ['t' => 'ROUND_END', 'round' => 1, 'keyframe' => $kf]]);
    $err = exerciseAll(SwuPgnParse(SwuPgnTestFile($events)));
    if ($err !== null) $failures[] = $err;
}
SwuPgnTestEq(array_slice(array_unique($failures), 0, 5), [], '150 keyframes with hostile field contents: every entry point survives every board rule');

// ── bounded growth ──────────────────────────────────────────────────────────────────────────────
function timed(callable $fn): float { $t = microtime(true); $fn(); return microtime(true) - $t; }
$n = SWUPGN_MAX_EVENTS;
$ev = [];
for ($i = 0; $i < $n; $i++) $ev[] = ['seq' => "R1.A.$i", 't' => 'MOVE', 'card' => "X#$i", 'from' => 'deck', 'to' => 'hand', 'p' => 1];
$secs = timed(function () use ($ev, &$s, &$w) { $s = SwuPgnFold($ev, $w); });
SwuPgnTestCheck(count($s['players'][1]['hand']) === SWUPGN_MAX_LIST && $s['players'][1]['handSize'] === $n, "$n distinct draws: hand[] capped at " . SWUPGN_MAX_LIST . ' (handSize still counts)');
SwuPgnTestCheck(count($w) <= SWUPGN_MAX_WARNINGS, 'fold warnings are capped (' . count($w) . ')');
SwuPgnTestCheck($secs < 3.0, sprintf('… folded in %.2fs', $secs));

$ev = [];
for ($i = 0; $i < $n; $i++) $ev[] = ['seq' => "R1.A.$i", 't' => 'PLAY', 'p' => 1 + $i % 2, 'card' => "X#$i"];
$secs = timed(function () use ($ev, &$s) { $s = SwuPgnFold($ev); });
SwuPgnTestCheck(count($s['players'][1]['cards']) === SWUPGN_MAX_CARDS_PER_SEAT && count($s['players'][2]['cards']) === SWUPGN_MAX_CARDS_PER_SEAT && $secs < 3.0, sprintf('%d plays: in-play cards capped at %d per seat (%.2fs)', $n, SWUPGN_MAX_CARDS_PER_SEAT, $secs));

// Worst case for detach: every card loaded with attachments, then thousands of exits.
$ev = [];
for ($c = 0; $c < SWUPGN_MAX_CARDS_PER_SEAT; $c++) {
    $ev[] = ['seq' => "R1.A.p$c", 't' => 'PLAY', 'p' => 1, 'card' => "H#$c"];
    for ($u = 0; $u < SWUPGN_MAX_ATTACHED + 3; $u++) $ev[] = ['seq' => "R1.A.u$c-$u", 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => "U#$c-$u", 'target' => "H#$c"];
}
for ($i = count($ev); $i < $n; $i++) $ev[] = ['seq' => "R1.A.x$i", 't' => 'MOVE', 'card' => "Z#$i", 'from' => 'ground', 'to' => 'discard', 'p' => 2];
$secs = timed(function () use ($ev, &$s) { $s = SwuPgnFold($ev); });
SwuPgnTestCheck(count($s['players'][1]['cards'][0]['upgrades']) === SWUPGN_MAX_ATTACHED, 'upgrades per card capped at ' . SWUPGN_MAX_ATTACHED);
SwuPgnTestCheck($secs < 5.0, sprintf('%d events of worst-case detach work fold in %.2fs', $n, $secs));

$ev = [['seq' => 'R1.A.0', 't' => 'PLAY', 'p' => 1, 'card' => 'H#1']];
for ($i = 1; $i < 200; $i++) $ev[] = ['seq' => "R1.A.$i", 't' => 'STATUS_TOKEN', 'card' => 'H#1', 'token' => "tok$i", 'count' => 1];
$ev[] = ['seq' => 'R1.A.k', 't' => 'STATS', 'card' => 'H#1', 'power' => 1, 'hp' => 1, 'keywords' => array_map('strval', range(1, SWUPGN_MAX_KEYWORDS + 1))];
$s = SwuPgnFold($ev);
SwuPgnTestCheck(count($s['players'][1]['cards'][0]['statusTokens']) === SWUPGN_MAX_TOKEN_KINDS && !isset($s['players'][1]['cards'][0]['keywords']), 'statusTokens kinds capped; an over-long keywords list is not taken');

$huge = SwuPgnTestKeyframe(['cards' => array_map(function ($i) { return SwuPgnTestCard("K#$i"); }, range(1, SWUPGN_MAX_CARDS_PER_SEAT + 1))]);
SwuPgnTestCheck(SwuPgnKeyframeProblem($huge) !== null, 'a keyframe beyond the reader\'s limits is refused like a damaged one');

// Same card id in both seats / CAPTURE loops / duplicate seqs do not loop forever.
$ev = SwuPgnTestSeq([['t' => 'PLAY', 'p' => 1, 'card' => 'D#1'], ['t' => 'ROUND_END', 'round' => 1, 'keyframe' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('D#1'), SwuPgnTestCard('D#1')]], ['cards' => [SwuPgnTestCard('D#1')]])], ['t' => 'CAPTURE', 'p' => 1, 'card' => 'D#1', 'by' => 'D#1']]);
$s = SwuPgnFold(SwuPgnParse(SwuPgnTestFile($ev))['events']);
SwuPgnTestCheck($s['players'][1]['cards'] === [] && $s['players'][2]['cards'] === [], 'CAPTURE removes every copy of a duplicated id and terminates');

SwuPgnTestFinish();
