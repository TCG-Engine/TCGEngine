<?php
// Two board snapshots (SwuPgnSnapshot) → the changes between them, as plain data. This is the recorder's truth:
// game-log notes only explain these changes (Matcher.php), they never add to them.
//
// Cards in play are matched by UniqueID; everything else by CardID multiset per seat and zone, so a shuffle or a
// reorder is no change. A card that left one place and arrived at another is ONE move; a card with no partner came
// from (or went to) outsideTheGame — a token. Zones use the SWU-PGN names (§6.2).
//
// Change kinds, each ['k' => kind, 'seat' => int, ...]:
//   move {cardID, from, to, uid?, host? + kind:'upgrade' (+ fromHost?), exhausted? (arrived), fromExhausted? (left a
//         resource row), fromSeat/toSeat?}  capture {cardID, uid, captor, from}
//   rescue {cardID, captor, uid, to, exhausted?} (seat = the owner it returns to)  control {uid, cardID, from, to}  damage|heal {target, amount, hp}
//   token {uid, kind, delta}  exhaust|ready {target, cardID}  resources {exhaustedDelta}  credit {delta}  force {has}
//   stats {uid, power, hp, keywords} (also for every unit entering play)  leader {field, value}  baseEpic {}  initiative {taken}
// "target" / "captor" is a UID, base@N or leader@N. Zone "temp" (TempZone, a pick's staging) is internal: the matcher
// keeps it out of the file.

const SWUPGN_ARENAS = ['ground', 'space'];

function _SwuPgnUnitsBySeat(array $snap): array {
    $out = [];
    foreach ($snap['seats'] as $seat => $s) foreach ($s['units'] as $uid => $u) $out[intval($uid)] = $u + ['_seat' => intval($seat)];
    return $out;
}

// Multiset difference of two CardID lists: [removed CardIDs, added CardIDs], each in list order.
function _SwuPgnMultisetDiff(array $before, array $after): array {
    $count = [];
    foreach ($after as $c) $count[$c] = ($count[$c] ?? 0) + 1;
    $removed = [];
    foreach ($before as $c) { if (($count[$c] ?? 0) > 0) $count[$c]--; else $removed[] = $c; }
    $count = [];
    foreach ($before as $c) $count[$c] = ($count[$c] ?? 0) + 1;
    $added = [];
    foreach ($after as $c) { if (($count[$c] ?? 0) > 0) $count[$c]--; else $added[] = $c; }
    return [$removed, $added];
}

// Places a card left (sources) and arrived at (sinks), outside the UID-matched units.
function _SwuPgnPlaces(array $before, array $after): array {
    $src = []; $snk = [];
    $ub = _SwuPgnUnitsBySeat($before); $ua = _SwuPgnUnitsBySeat($after);
    foreach ([1, 2] as $p) {
        $sb = $before['seats'][$p]; $sa = $after['seats'][$p];
        foreach (['hand', 'deck', 'discard'] as $z) {
            [$rm, $ad] = _SwuPgnMultisetDiff($sb[$z], $sa[$z]);
            foreach ($rm as $c) $src[] = ['seat' => $p, 'zone' => $z, 'cardID' => $c, 'pair' => $p];
            foreach ($ad as $c) $snk[] = ['seat' => $p, 'zone' => $z, 'cardID' => $c, 'pair' => $p];
        }
        // Resources (Credits excluded — they are counted, not moved). Which copy of a duplicate left is unknowable;
        // the trailing entries are taken, and the resources change below stays consistent with that pick.
        $rb = array_values(array_filter($sb['resources'], fn($r) => !$r['credit']));
        $ra = array_values(array_filter($sa['resources'], fn($r) => !$r['credit']));
        [$rm, $ad] = _SwuPgnMultisetDiff(array_column($rb, 'cardID'), array_column($ra, 'cardID'));
        foreach ([[$rm, $rb, &$src], [$ad, $ra, &$snk]] as [$ids, $rows, &$list]) {
            $need = array_count_values($ids);
            foreach (array_reverse($rows) as $r) {
                if (($need[$r['cardID']] ?? 0) <= 0) continue;
                $need[$r['cardID']]--;
                $list[] = ['seat' => $p, 'zone' => 'resource', 'cardID' => $r['cardID'], 'pair' => $p, 'ready' => $r['ready']];
            }
            unset($list);
        }
        $lb = $sb['leader']; $la = $sa['leader'];
        $homeB = $lb !== null && !$lb['deployed'] ? [$lb['cardID']] : [];
        $homeA = $la !== null && !$la['deployed'] ? [$la['cardID']] : [];
        [$rm, $ad] = _SwuPgnMultisetDiff($homeB, $homeA);
        foreach ($rm as $c) $src[] = ['seat' => $p, 'zone' => 'base', 'cardID' => $c, 'pair' => $p];
        foreach ($ad as $c) $snk[] = ['seat' => $p, 'zone' => 'base', 'cardID' => $c, 'pair' => $p, 'ready' => $la['ready']];
        // Base upgrades (Fortify) sit in the base area too.
        [$rm, $ad] = _SwuPgnMultisetDiff($sb['base']['upgrades'] ?? [], $sa['base']['upgrades'] ?? []);
        foreach ($rm as $c) $src[] = ['seat' => $p, 'zone' => 'base', 'cardID' => $c, 'pair' => $p];
        foreach ($ad as $c) $snk[] = ['seat' => $p, 'zone' => 'base', 'cardID' => $c, 'pair' => $p];
        [$rm, $ad] = _SwuPgnMultisetDiff($sb['base']['captives'] ?? [], $sa['base']['captives'] ?? []);
        foreach ($rm as $c) $src[] = ['seat' => $p, 'zone' => 'capture', 'cardID' => $c, 'pair' => null, 'captor' => "base@$p"];
        foreach ($ad as $c) $snk[] = ['seat' => $p, 'zone' => 'capture', 'cardID' => $c, 'pair' => null, 'captor' => "base@$p"];
    }
    // Units leaving / entering play. A leaving unit's own move is listed AFTER its upgrades' (below): the engine discards
    // a defeated unit's upgrades first, and the discard pile is ordered.
    $unitSrc = [];
    foreach ([[$ub, $ua, &$unitSrc], [$ua, $ub, &$snk]] as [$mine, $other, &$list]) {
        foreach ($mine as $uid => $u) {
            if (!isset($other[$uid])) {
                $list[] = ['seat' => $u['_seat'], 'zone' => $u['arena'], 'cardID' => $u['cardID'], 'pair' => intval($u['owner']),
                    'uid' => $uid, 'ready' => $u['ready']];
            }
        }
        unset($list);
    }
    // Upgrades and captives: per host, multiset against the same host on the other side (empty if it is gone).
    foreach (array_unique(array_merge(array_keys($ub), array_keys($ua))) as $uid) {
        $hb = $ub[$uid] ?? null; $ha = $ua[$uid] ?? null;
        $host = $ha ?? $hb;
        [$rm, $ad] = _SwuPgnMultisetDiff(array_column($hb['upgrades'] ?? [], 'cardID'), array_column($ha['upgrades'] ?? [], 'cardID'));
        foreach ($rm as $c) $src[] = ['seat' => $hb['_seat'], 'zone' => $hb['arena'], 'cardID' => $c, 'pair' => $hb['_seat'], 'host' => $uid];
        foreach ($ad as $c) $snk[] = ['seat' => $ha['_seat'], 'zone' => $ha['arena'], 'cardID' => $c, 'pair' => $ha['_seat'], 'host' => $uid];
        [$rm, $ad] = _SwuPgnMultisetDiff($hb['captives'] ?? [], $ha['captives'] ?? []);
        foreach ($rm as $c) $src[] = ['seat' => $hb['_seat'], 'zone' => 'capture', 'cardID' => $c, 'pair' => null, 'captor' => $uid];
        foreach ($ad as $c) $snk[] = ['seat' => $host['_seat'], 'zone' => 'capture', 'cardID' => $c, 'pair' => null, 'captor' => $uid];
    }
    array_push($src, ...$unitSrc);
    // Staging (TempZone) last, so a real destination claims a card before a staged copy of it can.
    foreach ([1, 2] as $p) {
        [$rm, $ad] = _SwuPgnMultisetDiff($before['seats'][$p]['temp'] ?? [], $after['seats'][$p]['temp'] ?? []);
        foreach ($rm as $c) $src[] = ['seat' => $p, 'zone' => 'temp', 'cardID' => $c, 'pair' => $p];
        foreach ($ad as $c) $snk[] = ['seat' => $p, 'zone' => 'temp', 'cardID' => $c, 'pair' => $p];
    }
    return [$src, $snk];
}

// Pair each source with a sink of the same card: first one the same seat/owner claims, then any. In tiers: a unit
// leaving play first — the discard pile before anywhere else, as it is usually defeated — then every other real
// place, and the staging area (a pick's TempZone, which may hold only a shown copy) last — so a real move never pairs
// with a staged copy while a real partner is left.
function _SwuPgnPair(array $src, array $snk): array {
    $pairs = []; $usedSnk = []; $pairedSrc = [];
    foreach ([['units', true], ['units', false], ['real', true], ['real', false], ['all', true], ['all', false]] as [$tier, $strict]) {
        foreach ($src as $i => $s) {
            if (isset($pairedSrc[$i])) continue;
            $leavingPlay = isset($s['uid']) && !isset($s['host']) && in_array($s['zone'], SWUPGN_ARENAS, true);
            if ($tier === 'units' && !$leavingPlay) continue;
            if ($tier !== 'all' && $s['zone'] === 'temp') continue;
            $order = array_keys($snk);
            if ($tier === 'units') usort($order, fn($a, $b) => ($snk[$b]['zone'] === 'discard') <=> ($snk[$a]['zone'] === 'discard') ?: $a <=> $b);
            foreach ($order as $j) {
                $t = $snk[$j];
                if (isset($usedSnk[$j]) || $t['cardID'] !== $s['cardID']) continue;
                if ($tier !== 'all' && $t['zone'] === 'temp') continue;
                if ($strict && ($s['pair'] === null || $s['pair'] !== $t['pair'])) continue;
                $pairs[$i] = $j; $usedSnk[$j] = true; $pairedSrc[$i] = true;
                break;
            }
        }
    }
    ksort($pairs);
    return [$pairs, $usedSnk];
}

function _SwuPgnMoveChange(?array $s, ?array $t): array {
    if ($t !== null && $t['zone'] === 'capture' && $s !== null) {
        return ['k' => 'capture', 'seat' => $t['seat'], 'cardID' => $s['cardID'], 'uid' => $s['uid'] ?? null,
            'captor' => $t['captor'], 'from' => $s['zone']];
    }
    if ($s !== null && $s['zone'] === 'capture' && $t !== null) {
        $c = ['k' => 'rescue', 'seat' => $t['seat'], 'cardID' => $s['cardID'], 'captor' => $s['captor'], 'uid' => $t['uid'] ?? null, 'to' => $t['zone']];
        if (isset($t['ready'])) $c['exhausted'] = !$t['ready'];
        return $c;
    }
    $from = $s['zone'] ?? 'outsideTheGame'; $to = $t['zone'] ?? 'outsideTheGame';
    $offBoard = fn($x) => $x !== null && !in_array($x['zone'], SWUPGN_ARENAS, true);
    $seat = $offBoard($s) ? $s['seat'] : ($offBoard($t) ? $t['seat'] : ($s['seat'] ?? $t['seat']));
    $c = ['k' => 'move', 'seat' => $seat, 'cardID' => ($s ?? $t)['cardID'], 'from' => $from, 'to' => $to];
    $uid = $s['uid'] ?? $t['uid'] ?? null;
    if ($uid !== null) $c['uid'] = $uid;
    if (($s['zone'] ?? $t['zone'] ?? null) === 'capture') $c['captor'] = ($s ?? $t)['captor'];   // into or out of capture, unpaired
    // A card that changes seats (an opponent-owned card played into your arena, a stolen resource going home) says so.
    if ($s !== null && $t !== null && $s['seat'] !== $t['seat']) { $c['fromSeat'] = $s['seat']; $c['toSeat'] = $t['seat']; }
    if ($s !== null && $from === 'resource') $c['fromExhausted'] = !$s['ready'];   // how it left the row (the row's counts)
    $host = $t['host'] ?? $s['host'] ?? null;
    if ($host !== null) {
        $c['host'] = $host; $c['kind'] = 'upgrade';
        if (isset($s['host'], $t['host']) && $s['host'] !== $t['host']) $c['fromHost'] = $s['host'];   // moved to another host
        unset($c['fromSeat'], $c['toSeat']);   // an attachment follows its host, whoever controls it
        // A role change: a pilot ejected to a unit of its own (Eject), or a unit in play made a pilot (L3-37).
        $isUnit = fn($x) => $x !== null && !isset($x['host']) && isset($x['uid']) && in_array($x['zone'], SWUPGN_ARENAS, true);
        if (isset($s['host']) && $isUnit($t)) {
            $c['detach'] = true;
            if (isset($t['ready'])) $c['exhausted'] = !$t['ready'];
        } elseif (isset($t['host']) && $isUnit($s)) {
            $c['attachFromPlay'] = true;
        }
        if ($t !== null && isset($t['ready']) && $to === 'base') $c['exhausted'] = !$t['ready'];   // a Pilot leader coming home
        return $c;
    }
    // How it arrived (exhausted) — apart from how it left a resource row (fromExhausted, above).
    if ($t !== null && isset($t['ready']) && in_array($to, ['ground', 'space', 'resource', 'base'], true)) $c['exhausted'] = !$t['ready'];
    return $c;
}

function SwuPgnDiff(array $before, array $after): array {
    $out = ['move' => [], 'damage' => [], 'token' => [], 'exhaust' => [], 'resources' => [], 'credit' => [], 'force' => [],
        'stats' => [], 'leader' => [], 'baseEpic' => [], 'initiative' => []];
    [$src, $snk] = _SwuPgnPlaces($before, $after);
    [$pairs, $usedSnk] = _SwuPgnPair($src, $snk);
    foreach ($src as $i => $s) $out['move'][] = _SwuPgnMoveChange($s, isset($pairs[$i]) ? $snk[$pairs[$i]] : null);
    foreach ($snk as $j => $t) if (!isset($usedSnk[$j])) $out['move'][] = _SwuPgnMoveChange(null, $t);

    $ub = _SwuPgnUnitsBySeat($before); $ua = _SwuPgnUnitsBySeat($after);
    foreach ($ua as $uid => $a) {
        $b = $ub[$uid] ?? null;
        $seat = $a['_seat'];
        foreach ($a['tokens'] as $kind => $n) {
            $d = $n - ($b['tokens'][$kind] ?? 0);
            if ($d !== 0) $out['token'][] = ['k' => 'token', 'seat' => $seat, 'uid' => $uid, 'kind' => $kind, 'delta' => $d];
        }
        if ($b === null) {
            $out['stats'][] = ['k' => 'stats', 'seat' => $seat, 'uid' => $uid, 'power' => $a['power'], 'hp' => $a['hp'], 'keywords' => $a['keywords']];
            continue;
        }
        if ($b['_seat'] !== $seat) {
            $out['move'][] = ['k' => 'control', 'seat' => $seat, 'uid' => $uid, 'cardID' => $a['cardID'], 'from' => $b['_seat'], 'to' => $seat];
        }
        if ($b['arena'] !== $a['arena']) {
            $out['move'][] = ['k' => 'move', 'seat' => $seat, 'cardID' => $a['cardID'], 'from' => $b['arena'], 'to' => $a['arena'], 'uid' => $uid];
        }
        $d = $a['damage'] - $b['damage'];
        if ($d !== 0) $out['damage'][] = ['k' => $d > 0 ? 'damage' : 'heal', 'seat' => $seat, 'target' => $uid, 'amount' => abs($d), 'hp' => $a['hp'] - $a['damage']];
        if ($a['ready'] !== $b['ready']) $out['exhaust'][] = ['k' => $a['ready'] ? 'ready' : 'exhaust', 'seat' => $seat, 'target' => $uid, 'cardID' => $a['cardID']];
        if ($a['power'] !== $b['power'] || $a['hp'] !== $b['hp'] || $a['keywords'] !== $b['keywords']) {
            $out['stats'][] = ['k' => 'stats', 'seat' => $seat, 'uid' => $uid, 'power' => $a['power'], 'hp' => $a['hp'], 'keywords' => $a['keywords']];
        }
    }

    foreach ([1, 2] as $p) {
        $sb = $before['seats'][$p]; $sa = $after['seats'][$p];
        $d = ($sa['base']['damage'] ?? 0) - ($sb['base']['damage'] ?? 0);
        if ($d !== 0) $out['damage'][] = ['k' => $d > 0 ? 'damage' : 'heal', 'seat' => $p, 'target' => "base@$p", 'amount' => abs($d),
            'hp' => $sa['base']['maxHp'] - $sa['base']['damage']];
        if (!($sb['base']['epicUsed'] ?? false) && ($sa['base']['epicUsed'] ?? false)) $out['baseEpic'][] = ['k' => 'baseEpic', 'seat' => $p];

        $exhausted = fn($rows) => count(array_filter($rows, fn($r) => !$r['credit'] && !$r['ready']));
        $eIn = count(array_filter($snk, fn($t) => $t['zone'] === 'resource' && $t['seat'] === $p && !$t['ready']));
        $eOut = count(array_filter($src, fn($s) => $s['zone'] === 'resource' && $s['seat'] === $p && !$s['ready']));
        $d = $exhausted($sa['resources']) - $exhausted($sb['resources']) - $eIn + $eOut;
        if ($d !== 0) $out['resources'][] = ['k' => 'resources', 'seat' => $p, 'exhaustedDelta' => $d];
        $credits = fn($rows) => count(array_filter($rows, fn($r) => $r['credit']));
        $d = $credits($sa['resources']) - $credits($sb['resources']);
        if ($d !== 0) $out['credit'][] = ['k' => 'credit', 'seat' => $p, 'delta' => $d];
        if ($sa['force'] !== $sb['force']) $out['force'][] = ['k' => 'force', 'seat' => $p, 'has' => $sa['force']];

        $lb = $sb['leader']; $la = $sa['leader'];
        if ($lb !== null && $la !== null) {
            if (!$lb['deployed'] && !$la['deployed'] && $lb['ready'] !== $la['ready']) {
                $out['exhaust'][] = ['k' => $la['ready'] ? 'ready' : 'exhaust', 'seat' => $p, 'target' => "leader@$p", 'cardID' => $la['cardID']];
            }
            foreach (['deployed', 'epicUsed', 'onStartingSide'] as $f) {
                if (!array_key_exists($f, $lb) || !array_key_exists($f, $la)) continue;
                if ($lb[$f] !== $la[$f]) $out['leader'][] = ['k' => 'leader', 'seat' => $p, 'field' => $f, 'value' => $la[$f]];
            }
        }
    }
    if ($before['initiative'] !== $after['initiative'] || $before['initiativeTaken'] !== $after['initiativeTaken']) {
        $out['initiative'][] = ['k' => 'initiative', 'seat' => $after['initiative'], 'taken' => $after['initiativeTaken']];
    }
    return array_merge(...array_values($out));
}
