<?php
// SWU-PGN keyframe integrity check (SWU-PGN/1.0 spec §14 checkKeyframes()).
//
// Folds forward from the empty board; at every keyframe compares the fold against the photo,
// records each difference, then snaps to the photo and carries on. `expected` is the keyframe (the
// engine's truth), `got` is the fold. A side that does not have the field at all leaves its key
// out of the mismatch — absent is never reported as 0 / false / [].
//
// Rules, from §14:
//   - a field the keyframe does not carry is skipped ("absent means not recorded");
//   - baseHp and deckSize are skipped at the FIRST keyframe — the stream cannot carry them before
//     it. The first keyframe here is the first USABLE one (a damaged keyframe supplies nothing);
//   - hand, resources, upgrades, captured and keywords compare as sets; discard in order;
//   - a damaged keyframe is one mismatch with path "keyframe", is not compared, and is not snapped.
// Round, phase, initiative, baseMaxHp, seat and active are not compared (see the report notes).

function SwuPgnCheckKeyframes(array $events): array
{
    $state = SwuPgnEmptyState();
    $w = [];
    $mismatches = [];
    $first = true;
    $n = 0;
    foreach ($events as $e) {
        if (++$n > SWUPGN_MAX_EVENTS) break;
        if (_SwuPgnIsKeyframeEvent($e)) {
            $seq = is_string($e['seq'] ?? null) ? $e['seq'] : '';
            $problem = SwuPgnKeyframeProblem($e['keyframe']);
            if ($problem !== null) {
                _SwuPgnMismatch($mismatches, ['seq' => $seq, 'path' => 'keyframe', 'expected' => 'a complete keyframe', 'got' => $problem]);
                $state = SwuPgnReduce($state, $e, $w);
                continue;
            }
            // A keyframe describes the board at its own seq, i.e. after its event's own rule: §12.2
            // "ROUND_START → initiativeTaken = false" — the initiative counter is available again.
            if (($e['t'] ?? null) === 'ROUND_START') $state['initiativeTaken'] = false;
            foreach (_SwuPgnCompareKeyframe($state, $e['keyframe'], $seq, $first) as $m) _SwuPgnMismatch($mismatches, $m);
            $state = _SwuPgnSnap($e['keyframe']);
            $first = false;
            continue;
        }
        $state = SwuPgnReduce($state, $e, $w);
    }
    return ['ok' => !$mismatches, 'mismatches' => $mismatches];
}

function _SwuPgnMismatch(array &$list, array $m): void
{
    if (count($list) < SWUPGN_MAX_MISMATCHES) $list[] = $m;
}

// One mismatch entry; `expected` / `got` omitted for a side that lacks the field.
function _SwuPgnMm(string $seq, string $path, bool $hasExp, $exp, bool $hasGot, $got): array
{
    $m = ['seq' => $seq, 'path' => $path];
    if ($hasExp) $m['expected'] = $exp;
    if ($hasGot) $m['got'] = $got;
    return $m;
}

// Value equality as JSON sees it: numbers by value, an empty object equals an empty array, objects
// by key (any order), arrays in order.
function _SwuPgnSame($a, $b): bool
{
    if ($a instanceof stdClass) $a = get_object_vars($a);
    if ($b instanceof stdClass) $b = get_object_vars($b);
    if ((is_int($a) || is_float($a)) && (is_int($b) || is_float($b))) return $a == $b;
    if (is_array($a) && is_array($b)) {
        if (count($a) !== count($b)) return false;
        $aList = array_is_list($a);
        if ($a !== [] && $aList !== array_is_list($b)) return false;
        if ($aList) {
            foreach ($a as $i => $v) if (!_SwuPgnSame($v, $b[$i])) return false;
            return true;
        }
        foreach ($a as $k => $v) {
            if (!array_key_exists($k, $b) || !_SwuPgnSame($v, $b[$k])) return false;
        }
        return true;
    }
    return $a === $b;
}

function _SwuPgnSameSet($a, $b): bool
{
    $norm = function ($l): array {
        $out = [];
        foreach (_SwuPgnList($l) as $x) $out[] = is_string($x) ? $x : json_encode($x);
        $out = array_values(array_unique($out));
        sort($out, SORT_STRING);
        return $out;
    };
    return $norm($a) === $norm($b);
}

function _SwuPgnCompareKeyframe(array $fold, array $kf, string $seq, bool $first): array
{
    $out = [];

    // The initiative counter's status. The fold leaves it absent until a round starts or someone
    // claims, which means "not taken".
    if (array_key_exists('initiativeTaken', $kf)) {
        $got = array_key_exists('initiativeTaken', $fold) ? $fold['initiativeTaken'] : false;
        if (!_SwuPgnSame($kf['initiativeTaken'], $got)) $out[] = _SwuPgnMm($seq, 'initiativeTaken', true, $kf['initiativeTaken'], true, $got);
    }

    foreach ([1, 2] as $s) {
        $kp = $kf['players'][$s];
        $fp = is_array($fold['players'][$s] ?? null) ? $fold['players'][$s] : [];
        $pre = "players.$s.";

        // Plain values: [field, skipped at the first keyframe, the fold's value when absent].
        foreach ([['baseHp', true, null], ['deckSize', true, null], ['handSize', false, null]] as [$f, $skipFirst]) {
            if (!array_key_exists($f, $kp) || ($first && $skipFirst)) continue;
            _SwuPgnCompareField($out, $seq, $pre . $f, $kp, $fp, $f);
        }
        if (array_key_exists('hand', $kp) && !_SwuPgnSameSet($kp['hand'], $fp['hand'] ?? [])) {
            $out[] = _SwuPgnMm($seq, $pre . 'hand', true, $kp['hand'], array_key_exists('hand', $fp), $fp['hand'] ?? null);
        }
        if (array_key_exists('discard', $kp) && !_SwuPgnSame(_SwuPgnList($kp['discard']), _SwuPgnList($fp['discard'] ?? []))) {
            $out[] = _SwuPgnMm($seq, $pre . 'discard', true, $kp['discard'], array_key_exists('discard', $fp), $fp['discard'] ?? null);
        }
        if (array_key_exists('resources', $kp) && !_SwuPgnSameSet($kp['resources'], $fp['resources'] ?? [])) {
            $out[] = _SwuPgnMm($seq, $pre . 'resources', true, $kp['resources'], array_key_exists('resources', $fp), $fp['resources'] ?? null);
        }
        if (array_key_exists('baseEpicActionUsed', $kp)) {
            // The fold only ever sets it true; never having seen it means "not used".
            $got = array_key_exists('baseEpicActionUsed', $fp) ? $fp['baseEpicActionUsed'] : false;
            if (!_SwuPgnSame($kp['baseEpicActionUsed'], $got)) $out[] = _SwuPgnMm($seq, $pre . 'baseEpicActionUsed', true, $kp['baseEpicActionUsed'], true, $got);
        }
        foreach (['resourcesReady', 'resourcesExhausted', 'credits', 'hasForce'] as $f) {
            if (array_key_exists($f, $kp)) _SwuPgnCompareField($out, $seq, $pre . $f, $kp, $fp, $f);
        }

        if (array_key_exists('leader', $kp)) {
            $kl = $kp['leader'];
            $hasFl = array_key_exists('leader', $fp);
            if (!$hasFl) {
                // The stream cannot name the leader before the first keyframe (A.4).
                if (!$first) $out[] = _SwuPgnMm($seq, $pre . 'leader', true, $kl, false, null);
            } elseif (is_array($kl) && is_array($fp['leader'])) {
                foreach (['id', 'deployed', 'exhausted', 'epicActionUsed', 'onStartingSide'] as $f) {
                    if (array_key_exists($f, $kl)) _SwuPgnCompareField($out, $seq, $pre . "leader.$f", $kl, $fp['leader'], $f);
                }
            } elseif (!_SwuPgnSame($kl, $fp['leader'])) {
                $out[] = _SwuPgnMm($seq, $pre . 'leader', true, $kl, true, $fp['leader']);
            }
        }

        foreach (_SwuPgnCompareCards(_SwuPgnList($kp['cards'] ?? []), _SwuPgnList($fp['cards'] ?? []), $seq, $pre) as $m) $out[] = $m;
    }
    return $out;
}

function _SwuPgnCompareField(array &$out, string $seq, string $path, array $k, array $f, string $field): void
{
    $hasGot = array_key_exists($field, $f);
    if ($hasGot && _SwuPgnSame($k[$field], $f[$field])) return;
    $out[] = _SwuPgnMm($seq, $path, true, $k[$field], $hasGot, $hasGot ? $f[$field] : null);
}

// In-play cards matched by id, per seat.
function _SwuPgnCompareCards(array $kCards, array $fCards, string $seq, string $pre): array
{
    $byId = function (array $cards): array {
        $m = [];
        foreach ($cards as $c) {
            if (is_array($c) && is_string($c['id'] ?? null) && !array_key_exists($c['id'], $m)) $m[$c['id']] = $c;
        }
        return $m;
    };
    $k = $byId($kCards);
    $f = $byId($fCards);
    $out = [];
    foreach ($k as $id => $kc) {
        $path = $pre . "cards[$id]";
        if (!array_key_exists($id, $f)) {
            $out[] = _SwuPgnMm($seq, $path, true, 'in play', true, 'not in play');
            continue;
        }
        $fc = $f[$id];
        foreach (['zone', 'damage', 'exhausted', 'shields', 'experience'] as $field) {
            if (array_key_exists($field, $kc)) _SwuPgnCompareField($out, $seq, "$path.$field", $kc, $fc, $field);
        }
        if (array_key_exists('statusTokens', $kc)) {
            $kt = $kc['statusTokens'];
            $ft = array_key_exists('statusTokens', $fc) ? $fc['statusTokens'] : [];
            if (!_SwuPgnSame($kt, $ft)) $out[] = _SwuPgnMm($seq, "$path.statusTokens", true, $kt, true, $ft);
        }
        foreach (['upgrades', 'captured'] as $field) {        // a missing list counts as empty
            if (!_SwuPgnSameSet($kc[$field] ?? [], $fc[$field] ?? [])) {
                $out[] = _SwuPgnMm($seq, "$path.$field", true, $kc[$field] ?? [], true, $fc[$field] ?? []);
            }
        }
        foreach (['power', 'hp'] as $field) {
            if (array_key_exists($field, $kc)) _SwuPgnCompareField($out, $seq, "$path.$field", $kc, $fc, $field);
        }
        if (array_key_exists('keywords', $kc)) {
            $hasGot = array_key_exists('keywords', $fc);
            if (!$hasGot || !_SwuPgnSameSet($kc['keywords'], $fc['keywords'])) {
                $out[] = _SwuPgnMm($seq, "$path.keywords", true, $kc['keywords'], $hasGot, $fc['keywords'] ?? null);
            }
        }
    }
    foreach ($f as $id => $fc) {
        if (!array_key_exists($id, $k)) $out[] = _SwuPgnMm($seq, $pre . "cards[$id]", true, 'not in play', true, 'in play');
    }
    return $out;
}
