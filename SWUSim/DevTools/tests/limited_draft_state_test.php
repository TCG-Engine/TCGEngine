<?php
// Limited Draft rules — the leader draft, the card rounds, pass directions, queues, the pick clock, aspect-lean auto-picks,
// dropped seats, the kick rule. docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §2.4, §3.2, §3.6.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/limited_draft_state_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/Limited/LimitedDraftState.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$now = 1_800_000_000;
// Fake ids. Leaders L<seat>_<n>; round-r cards C<seat>r<r>_<k> (14 a pack). Aspects cycle through five shapes.
$shapes = [['Vigilance', 'Heroism'], ['Aggression'], [], ['Command', 'Villainy'], ['Cunning']];
function room(array $pids, int $packs = 3): array {
    $lt = LTNew('draft');
    foreach ($pids as $p) $lt['seats'][$p] = ['name' => "p$p", 'deck' => null, 'submitted' => false, 'ready' => false, 'dropped' => false];
    return $lt;
}
function packsFor(array $pids, int $packs, array $shapes): array {
    $leaders = []; $cards = []; $aspects = [];
    foreach ($pids as $p) {
        for ($n = 1; $n <= $packs; $n++) { $id = "L{$p}_$n"; $leaders[$p][] = $id; $aspects[$id] = ['Vigilance', 'Heroism']; }
        for ($r = 1; $r <= $packs; $r++) {
            $pack = [];
            for ($k = 0; $k < 14; $k++) { $id = "C{$p}r{$r}_$k"; $pack[] = $id; $aspects[$id] = $shapes[$k % 5]; }
            $cards[$p][] = $pack;
        }
    }
    return [$leaders, $cards, $aspects];
}
function begin(array $pids, int $packs, array $shapes, int $now, int $pickSeconds = 300): array {
    [$l, $c, $a] = packsFor($pids, $packs, $shapes);
    return LTDraftBegin(room($pids, $packs), $pids, $l, $c, $a, 4242, $pickSeconds, $now);
}
// Pick the first card of every live seat's front, round-robin, ticking after each, until the draft is over.
function driveToEnd(array $lt, int $now, array $skip = []): array {
    for ($guard = 0; $guard < 5000 && $lt['phase'] === 'drafting'; $guard++) {
        $moved = false;
        foreach ($lt['draft']['order'] as $p) {
            if (in_array($p, $skip, true) || !empty($lt['seats'][$p]['dropped'])) continue;
            $f = LTDraftFront($lt['draft'], $p);
            if ($f === null) continue;
            $lt = LTDraftPick($lt, $p, $f['id'], $f['cards'][0], $now);
            $lt = LTDraftTick($lt, [], $now);
            $moved = true;
            if ($lt['phase'] !== 'drafting') break;
        }
        if (!$moved) $lt = LTDraftTick($lt, [], $now);
    }
    return $lt;
}

echo "NoCardDictionary:\n";
$check(!function_exists('CardTitle') && !function_exists('GetAllCardIds'), 'the draft rules never load the card dictionary');

echo "BeginNeedsThreeToEight:\n";
[$l2, $c2, $a2] = packsFor([1, 2], 3, $shapes);
$check(LTDraftBegin(room([1, 2]), [1, 2], $l2, $c2, $a2, 1, 300, $now) === null, '2 seats cannot draft');
$nine = range(1, 9); [$l9, $c9, $a9] = packsFor($nine, 3, $shapes);
$check(LTDraftBegin(room($nine), $nine, $l9, $c9, $a9, 1, 300, $now) === null, '9 seats cannot draft');
$sealed = room([1, 2, 3]); $sealed['mode'] = 'sealed'; [$l3, $c3, $a3] = packsFor([1, 2, 3], 3, $shapes);
$check(LTDraftBegin($sealed, [1, 2, 3], $l3, $c3, $a3, 1, 300, $now) === null, 'a Sealed room does not draft');
$d = begin([1, 2, 3], 3, $shapes, $now);
$check($d !== null && $d['phase'] === 'drafting' && $d['draft']['stage'] === 'leaders', '3 seats: drafting, leader stage');
$check(count(LTDraftFront($d['draft'], 1)['cards']) === 3 && LTDraftFront($d['draft'], 1)['cards'] === ['L1_1', 'L1_2', 'L1_3'], 'each seat starts on its own 3 leaders');

echo "LeaderDraftPassesRight:\n";
$check(LTDraftNeighbour($d['draft'], 1, 'right') === 3 && LTDraftNeighbour($d['draft'], 1, 'left') === 2, 'order [1,2,3]: right of 1 is 3, left is 2');
$f = LTDraftFront($d['draft'], 1);
$p = LTDraftPick($d, 1, $f['id'], 'L1_2', $now + 5);
$check($p['draft']['leaders'][1] === ['L1_2'] && LTDraftFront($p['draft'], 1) === null, 'seat 1 keeps the leader it picked');
$check(count($p['draft']['queues'][3]) === 2 && $p['draft']['queues'][3][1]['cards'] === ['L1_1', 'L1_3'], 'the other two leaders go right, to seat 3');

echo "LeaderStageEndsWithNLeadersEach:\n";
$x = $d;
for ($i = 0; $i < 3; $i++) foreach ([1, 2, 3] as $s) { $f = LTDraftFront($x['draft'], $s); if ($f) $x = LTDraftPick($x, $s, $f['id'], $f['cards'][0], $now); }
$check($x['draft']['stage'] === 'cards' && $x['draft']['round'] === 1, 'after every leader pack is empty: the card draft, round 1');
$check(count($x['draft']['leaders'][1]) === 3 && count($x['draft']['leaders'][2]) === 3 && count($x['draft']['leaders'][3]) === 3, 'everyone holds 3 leaders');
$check(LTDraftFront($x['draft'], 2)['cards'][0] === 'C2r1_0' && count(LTDraftFront($x['draft'], 2)['cards']) === 14, 'each seat opens its own round-1 pack of 14');

echo "CardRoundsAlternate:\n";
$dir = function (array $draft, string $stage, int $round) { $draft['stage'] = $stage; $draft['round'] = $round; return LTDraftDirection($draft); };
$check([$dir($d['draft'], 'leaders', 0), $dir($d['draft'], 'cards', 1), $dir($d['draft'], 'cards', 2), $dir($d['draft'], 'cards', 3)] === ['right', 'left', 'right', 'left'],
    'leaders right, then left, right, left');
$f = LTDraftFront($x['draft'], 1);
$y = LTDraftPick($x, 1, $f['id'], 'C1r1_0', $now);
$check(count($y['draft']['queues'][2]) === 2 && $y['draft']['queues'][2][1]['cards'][0] === 'C1r1_1', 'round 1: seat 1 passes left, to seat 2');

echo "QueuesAndFrontClock:\n";
$q = $x; $t = $now + 100;
foreach ([1, 3] as $s) { $f = LTDraftFront($q['draft'], $s); $q = LTDraftPick($q, $s, $f['id'], $f['cards'][0], $t); }
$check($q['draft']['frontSince'][2] === $now, 'packs queuing behind seat 2\'s front leave its clock alone');
// Round 1 passes left (1→2→3→1): seat 3 only receives from seat 2, which has not picked yet.
$f = LTDraftFront($q['draft'], 3);
$check($f === null, 'seat 3 waits on seat 2');
$f1 = LTDraftFront($q['draft'], 1);   // seat 3's pack, passed to seat 1
$q = LTDraftPick($q, 1, $f1['id'], $f1['cards'][0], $t + 1);
$check(count($q['draft']['queues'][2]) === 3, 'seat 2, the slow one, now holds 3 packs');
$f2 = LTDraftFront($q['draft'], 2);
$q = LTDraftPick($q, 2, $f2['id'], $f2['cards'][0], $t + 50);
$check($q['draft']['frontSince'][2] === $t + 50, 'its next pack\'s clock starts when it reaches the front');

echo "NextRoundWaitsForEveryPack:\n";
$w = $x;
// Everyone picks until only seat 3 still holds a round-1 card.
for ($guard = 0; $guard < 500; $guard++) {
    $left = 0; foreach ([1, 2, 3] as $s) $left += array_sum(array_map(fn($pk) => count($pk['cards']), $w['draft']['queues'][$s]));
    if ($left === 1) break;
    foreach ([1, 2, 3] as $s) {
        $f = LTDraftFront($w['draft'], $s);
        if ($f === null) continue;
        $all = 0; foreach ([1, 2, 3] as $z) $all += array_sum(array_map(fn($pk) => count($pk['cards']), $w['draft']['queues'][$z]));
        if ($all === 1) break 2;
        $w = LTDraftPick($w, $s, $f['id'], $f['cards'][0], $now);
    }
}
$holder = 0; foreach ([1, 2, 3] as $s) if (LTDraftFront($w['draft'], $s) !== null) $holder = $s;
$roundTwo = false; foreach ([1, 2, 3] as $s) foreach ($w['draft']['queues'][$s] as $pk) foreach ($pk['cards'] as $c) if (strpos($c, 'r2_') !== false) $roundTwo = true;
$check($w['draft']['round'] === 1 && $holder > 0 && !$roundTwo, "one card left (seat $holder): still round 1, nobody sees a round-2 card");
$f = LTDraftFront($w['draft'], $holder);
$w = LTDraftPick($w, $holder, $f['id'], $f['cards'][0], $now);
$check($w['draft']['round'] === 2 && LTDraftFront($w['draft'], 1)['cards'][0] === 'C1r2_0', 'the last pick opens round 2 for everyone');

echo "DoublePickRefused:\n";
$f = LTDraftFront($x['draft'], 1);
$once = LTDraftPick($x, 1, $f['id'], $f['cards'][0], $now);
$check(LTDraftPick($once, 1, $f['id'], $f['cards'][1], $now) === null, 'the same pack cannot be picked twice');
$check(LTDraftPick($x, 1, 99999, $f['cards'][0], $now) === null, 'a pack that is not at the front is refused');
$check(LTDraftPick($x, 1, $f['id'], 'NOT_IN_PACK', $now) === null, 'a card that is not in the pack is refused');

echo "TimeoutAutoPicksTheLean:\n";
// Seat 1 leans Vigilance/Heroism (its leaders). Its front: one Vig·Her card, one Aggression card, one aspect-less card.
$z = $x;
$z['draft']['queues'][1][0]['cards'] = ['C1r1_0', 'C1r1_1', 'C1r1_2'];   // shapes 0 (Vig·Her), 1 (Aggression), 2 (none)
$since = $z['draft']['frontSince'][1];
$early = LTDraftTick($z, [], $since + 299);
$check(count($early['draft']['picks'][1]) === 0, '299 s in: no auto-pick yet');
$late = LTDraftTick($z, [], $since + 300);
$got = $late['draft']['picks'][1][0] ?? '';
$check(in_array($got, ['C1r1_0', 'C1r1_2'], true) && $late['draft']['autoPicks'][1] === 1, "at 300 s the auto-pick fits the lean ($got), and counts as a timeout");
$check(LTDraftLean($late, 1)['pair'] === ['Vigilance', 'Heroism'], 'the lean pair is Vigilance / Heroism');

echo "AutoPickFallsBack:\n";
$fb = $x;
$fb['draft']['leaders'][1] = ['L1_1', 'L1_2', 'L1_3'];
$fb['draft']['aspects']['X_VA'] = ['Vigilance', 'Aggression']; $fb['draft']['aspects']['X_V'] = ['Vigilance']; $fb['draft']['aspects']['X_CU'] = ['Cunning'];
$fb['draft']['queues'][1][0]['cards'] = ['X_VA', 'X_V'];
$check(LTDraftAutoPickCard($fb, 1) === 'X_V', 'nothing fits the pair → a card that fits the top aspect alone');
$fb['draft']['queues'][1][0]['cards'] = ['X_CU', 'X_VA'];
$check(in_array(LTDraftAutoPickCard($fb, 1), ['X_CU', 'X_VA'], true), 'nothing fits at all → any card');
$fb['draft']['leaders'][1] = []; $fb['draft']['picks'][1] = [];
$check(in_array(LTDraftAutoPickCard($fb, 1), ['X_CU', 'X_VA'], true), 'no picks at all → any card');
$check(in_array(LTDraftAutoPickCard($d, 1), ['L1_1', 'L1_2', 'L1_3'], true), 'in the leader draft → a leader');

echo "AutoPickIsDeterministic:\n";
$check(LTDraftTick($z, [], $since + 300) === $late, 'the same state ticked twice picks the same card');

echo "DroppedSeatAutoPicksToTheEnd:\n";
$dr = $d; $dr['seats'][2]['dropped'] = true;
$dr = LTDraftTick($dr, [], $now);
$end = driveToEnd($dr, $now);
$check($end['phase'] === 'building', 'the draft finishes with seat 2 dropped');
$check(count($end['pools'][2] ?? []) === 3 + 42 && count($end['pools'][1]) === 45 && count($end['pools'][3]) === 45, 'seat 2\'s pool is complete, like the others: 3 leaders + 42 cards');
$check($end['buildDeadline'] === $now + LT_BUILD_SECONDS, 'the build clock starts');

echo "EveryCardLandsOnce:\n";
$eight = range(1, 8);
[$l8, $c8, $a8] = packsFor($eight, 3, $shapes);
$e = driveToEnd(LTDraftBegin(room($eight), $eight, $l8, $c8, $a8, 7, 300, $now), $now);
$opened = []; foreach ($eight as $p) { $opened = array_merge($opened, $l8[$p]); foreach ($c8[$p] as $pk) $opened = array_merge($opened, $pk); }
$pooled = []; foreach ($eight as $p) $pooled = array_merge($pooled, $e['pools'][$p] ?? []);
sort($opened); sort($pooled);
$check($e['phase'] === 'building' && $pooled === $opened && count($pooled) === 8 * 45, '8 seats × 3 packs: every opened card lands in exactly one pool (' . count($pooled) . ')');

echo "KickableWhileDrafting:\n";
$seen = [1 => $now, 2 => $now, 3 => $now];
$check(LTDraftKickable($d, $seen, $now + 10) === [], 'connected, no auto-pick: nobody is kickable');
$k = LTDraftTick($d, $seen, $now + 300);   // every leader pack times out
$fresh = [1 => $now + 300, 2 => $now + 300, 3 => $now + 300];
$check(in_array(1, LTDraftKickable($k, $fresh, $now + 300), true), 'after a timeout auto-pick, a connected seat is kickable');
$check(LTDraftKickable($d, [1 => $now + 31, 2 => $now, 3 => $now + 31], $now + 31) === [2], 'silent 31 s → kickable');
$k['kick'][1] = ['votes' => [], 'waitUntil' => $now + 320];
$check(!in_array(1, LTDraftKickable($k, $fresh, $now + 310), true), 'inside a Wait: not kickable');
// Final review #3: one timeout must not brand a seat kickable for the rest of the draft — picking again clears it.
$k2 = $k; unset($k2['kick'][1]);
$f1 = LTDraftFront($k2['draft'], 1);
if ($f1 !== null) $k2 = LTDraftPick($k2, 1, $f1['id'], $f1['cards'][0], $now + 301);
$check($f1 !== null && !in_array(1, LTDraftKickable($k2, $fresh, $now + 301), true), 'a manual pick after a timeout makes the seat not kickable again');
$check(intval($k2['draft']['autoPicks'][1]) === 1, 'its auto-pick count is kept (the pod still shows it was auto-picked)');

echo "IconsPacksInADraft:\n";   // owner 2026-11-05: Host's Choice may mix in IC27 Icons packs — a pack may have no leader
// and 8–9 cards. Seat 2's packs opened no leader: its leader pack is empty, and the draft must not wait on it.
[$l, $c, $a] = packsFor([1, 2, 3], 3, $shapes);
$l[2] = [];
$c[1][1] = array_slice($c[1][1], 0, 9); $c[2][1] = array_slice($c[2][1], 0, 8); $c[3][1] = array_slice($c[3][1], 0, 9);   // round 2: Icons packs
$e = LTDraftBegin(room([1, 2, 3]), [1, 2, 3], $l, $c, $a, 4242, 300, $now);
$check($e['draft']['queues'][2] === [] && $e['draft']['frontSince'][2] === null && $e['draft']['stage'] === 'leaders', 'an empty leader pack is not queued, so nothing waits on it');
$check(intval($e['draft']['queues'][1][0]['size'] ?? 0) === 3, 'every pack records the size it opened at (a leader pack of 3)');
$done = driveToEnd($e, $now);
$sizes = array_map(fn($p) => count($done['pools'][$p] ?? []), [1, 2, 3]);
$check($done['phase'] === 'building' && array_sum(array_map(fn($p) => count($done['draft']['leaders'][$p]), [1, 2, 3])) === 6 && array_sum($sizes) === 6 + 3 * 14 + (9 + 8 + 9) + 3 * 14,   // leaders + round 1 + the Icons round + round 3
       'the draft still finishes: all 6 leaders and every card drafted: ' . json_encode($sizes));
$none = LTDraftBegin(room([1, 2, 3]), [1, 2, 3], [1 => [], 2 => [], 3 => []], $c, $a, 4242, 300, $now);
$check($none['draft']['stage'] === 'cards' && $none['draft']['round'] === 1 && count($none['draft']['queues'][1]) === 1 && intval($none['draft']['queues'][1][0]['size']) === 14,
       'no leader opened anywhere: the leader draft is skipped and pack 1 opens at once');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
