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
//   - "as a set" is a multiset (both lists sorted, compared entry for entry): hand, resources, foreignResources,
//     baseUpgrades, baseCaptured, upgrades, captured, keywords, traits, aspects; discard in order;
//   - baseDefeated, and a card's blank / leaderUnit, compared with absent read as false;
//   - the leader only when both sides have one; effects as a set by id (one "effects" mismatch); abilityUses
//     keyed by card + ability (one mismatch per seat), unknown `per` left out on both sides;
//   - before a ROUND_START keyframe the running board gets that event's resets: initiativeTaken false and
//     no phase / round abilityUses;
//   - a keyframe card WITH traits has all its attributes compared; a seat WITH foreignResources has every
//     card's owner / upgradeControllers compared (absent = the seat owns / controls it);
//   - a damaged keyframe is one mismatch with path "keyframe", is not compared, and is not snapped.
// Round, phase, initiative, active, baseMaxHp and seat are not compared.

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
            // §14: before comparing a ROUND_START keyframe, the running initiativeTaken is false and the
            // running phase / round abilityUses are gone (that event's own rule, §12.2).
            if (($e['t'] ?? null) === 'ROUND_START') $state = _SwuPgnDropUses(_SwuPgnSetRoot($state, 'initiativeTaken', false), ['phase', 'round']);
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

// §14 "as a set" = multiset: both lists sorted and compared entry for entry.
function _SwuPgnSameSet($a, $b): bool
{
    $norm = function ($l): array {
        $out = [];
        foreach (_SwuPgnList($l) as $x) $out[] = is_string($x) ? $x : json_encode($x);
        sort($out, SORT_STRING);
        return $out;
    };
    return $norm($a) === $norm($b);
}

// §14 effects: a set by id; each entry's src, p, seat, duration, and targets as a set.
function _SwuPgnSameEffects($a, $b): bool
{
    $byId = function ($l): array {
        $m = [];
        foreach (_SwuPgnList($l) as $x) {
            if (is_array($x) && is_string($x['id'] ?? null) && !array_key_exists($x['id'], $m)) $m[$x['id']] = $x;
        }
        return $m;
    };
    $ma = $byId($a);
    $mb = $byId($b);
    if (count($ma) !== count($mb)) return false;
    foreach ($ma as $id => $x) {
        if (!array_key_exists($id, $mb)) return false;
        $y = $mb[$id];
        foreach (['src', 'p', 'seat', 'duration'] as $f) {
            if (array_key_exists($f, $x) !== array_key_exists($f, $y) || (array_key_exists($f, $x) && !_SwuPgnSame($x[$f], $y[$f]))) return false;
        }
        if (!_SwuPgnSameSet($x['targets'] ?? [], $y['targets'] ?? [])) return false;
    }
    return true;
}

// §14 abilityUses: keyed by card + ability; each entry's count, per and max. An entry whose `per` the
// reader does not know is left out.
function _SwuPgnSameUses($a, $b): bool
{
    $byKey = function ($l): array {
        $m = [];
        foreach (_SwuPgnList($l) as $u) {
            if (!is_array($u) || !is_string($u['card'] ?? null) || !is_string($u['ability'] ?? null)) continue;
            if (!in_array($u['per'] ?? null, ['phase', 'round', 'game'], true)) continue;
            $k = $u['card'] . "\0" . $u['ability'];
            if (!array_key_exists($k, $m)) $m[$k] = $u;
        }
        return $m;
    };
    $ma = $byKey($a);
    $mb = $byKey($b);
    if (count($ma) !== count($mb)) return false;
    foreach ($ma as $k => $u) {
        if (!array_key_exists($k, $mb)) return false;
        foreach (['count', 'per', 'max'] as $f) {
            if (array_key_exists($f, $u) !== array_key_exists($f, $mb[$k]) || (array_key_exists($f, $u) && !_SwuPgnSame($u[$f], $mb[$k][$f]))) return false;
        }
    }
    return true;
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
    // Active effects, as a set by id; an absent list on the fold's side counts as empty. One mismatch.
    if (array_key_exists('effects', $kf) && !_SwuPgnSameEffects($kf['effects'], $fold['effects'] ?? [])) {
        $out[] = _SwuPgnMm($seq, 'effects', true, $kf['effects'], array_key_exists('effects', $fold), $fold['effects'] ?? null);
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
        foreach (['resources', 'foreignResources', 'baseUpgrades', 'baseCaptured'] as $f) {     // absent in the fold is []
            if (array_key_exists($f, $kp) && !_SwuPgnSameSet($kp[$f], $fp[$f] ?? [])) {
                $out[] = _SwuPgnMm($seq, $pre . $f, true, $kp[$f], array_key_exists($f, $fp), $fp[$f] ?? null);
            }
        }
        // Written only when true: absent on either side reads false (§11), so it is compared at every keyframe.
        $kd = ($kp['baseDefeated'] ?? false) === true;
        $fd = ($fp['baseDefeated'] ?? false) === true;
        if ($kd !== $fd) $out[] = _SwuPgnMm($seq, $pre . 'baseDefeated', true, $kd, true, $fd);
        if (array_key_exists('baseEpicActionUsed', $kp)) {
            // The fold only ever sets it true; never having seen it means "not used".
            $got = array_key_exists('baseEpicActionUsed', $fp) ? $fp['baseEpicActionUsed'] : false;
            if (!_SwuPgnSame($kp['baseEpicActionUsed'], $got)) $out[] = _SwuPgnMm($seq, $pre . 'baseEpicActionUsed', true, $kp['baseEpicActionUsed'], true, $got);
        }
        foreach (['resourcesReady', 'resourcesExhausted', 'credits', 'hasForce'] as $f) {
            if (array_key_exists($f, $kp)) _SwuPgnCompareField($out, $seq, $pre . $f, $kp, $fp, $f);
        }

        // The leader: only when both the keyframe and the fold have one for that seat.
        if (array_key_exists('leader', $kp) && array_key_exists('leader', $fp)) {
            $kl = $kp['leader'];
            if (is_array($kl) && is_array($fp['leader'])) {
                foreach (['id', 'deployed', 'exhausted', 'epicActionUsed', 'onStartingSide'] as $f) {
                    if (array_key_exists($f, $kl)) _SwuPgnCompareField($out, $seq, $pre . "leader.$f", $kl, $fp['leader'], $f);
                }
            } elseif (!_SwuPgnSame($kl, $fp['leader'])) {
                $out[] = _SwuPgnMm($seq, $pre . 'leader', true, $kl, true, $fp['leader']);
            }
        }

        // Limited-ability uses, keyed by card + ability; one mismatch per seat.
        if (array_key_exists('abilityUses', $kp) && !_SwuPgnSameUses($kp['abilityUses'], $fp['abilityUses'] ?? [])) {
            $out[] = _SwuPgnMm($seq, $pre . 'abilityUses', true, $kp['abilityUses'], array_key_exists('abilityUses', $fp), $fp['abilityUses'] ?? null);
        }

        $control = array_key_exists('foreignResources', $kp);      // [W-57]: absent owner / upgradeControllers then mean "none"
        foreach (_SwuPgnCompareCards(_SwuPgnList($kp['cards'] ?? []), _SwuPgnList($fp['cards'] ?? []), $seq, $pre, $s, $control) as $m) $out[] = $m;
    }
    return $out;
}

function _SwuPgnCompareField(array &$out, string $seq, string $path, array $k, array $f, string $field): void
{
    $hasGot = array_key_exists($field, $f);
    if ($hasGot && _SwuPgnSame($k[$field], $f[$field])) return;
    $out[] = _SwuPgnMm($seq, $path, true, $k[$field], $hasGot, $hasGot ? $f[$field] : null);
}

// In-play cards matched by id, per seat. $seat: the seat whose cards these are; $control: compare every card's
// owner / upgradeControllers (the keyframe's seat carries foreignResources).
function _SwuPgnCompareCards(array $kCards, array $fCards, string $seq, string $pre, int $seat = 0, bool $control = false): array
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
        // The other attributes: all of them when the keyframe card carries traits, else only those it carries.
        $all = array_key_exists('traits', $kc);
        foreach (['traits', 'blank', 'leaderUnit', 'copyOf', 'name', 'cost', 'aspects'] as $field) {
            $hasExp = array_key_exists($field, $kc);
            if (!$all && !$hasExp) continue;
            $hasGot = array_key_exists($field, $fc);
            if ($field === 'blank' || $field === 'leaderUnit') {       // absent reads false
                $kb = ($kc[$field] ?? false) === true;
                $fb = ($fc[$field] ?? false) === true;
                if ($kb !== $fb) $out[] = _SwuPgnMm($seq, "$path.$field", true, $kb, true, $fb);
                continue;
            }
            if ($hasExp !== $hasGot) $same = false;
            elseif (!$hasExp) $same = true;
            elseif ($field === 'traits' || $field === 'aspects') $same = _SwuPgnSameSet($kc[$field], $fc[$field]);
            else $same = _SwuPgnSame($kc[$field], $fc[$field]);
            if (!$same) $out[] = _SwuPgnMm($seq, "$path.$field", $hasExp, $kc[$field] ?? null, $hasGot, $fc[$field] ?? null);
        }
        // Owner (absent = the holder owns it; an owner naming the holder reads the same) and upgradeControllers (missing = {}).
        $ownerOf = function (array $c, bool $has) use ($seat) {
            return ($has && $c['owner'] !== $seat) ? $c['owner'] : null;
        };
        $hasExp = array_key_exists('owner', $kc);
        if ($control || $hasExp) {
            $hasGot = array_key_exists('owner', $fc);
            if (!_SwuPgnSame($ownerOf($kc, $hasExp), $ownerOf($fc, $hasGot))) $out[] = _SwuPgnMm($seq, "$path.owner", $hasExp, $kc['owner'] ?? null, $hasGot, $fc['owner'] ?? null);
        }
        $hasExp = array_key_exists('upgradeControllers', $kc);
        if ($control || $hasExp) {
            $hasGot = array_key_exists('upgradeControllers', $fc);
            if (!_SwuPgnSame(_SwuPgnMap($kc['upgradeControllers'] ?? null), _SwuPgnMap($fc['upgradeControllers'] ?? null))) {
                $out[] = _SwuPgnMm($seq, "$path.upgradeControllers", $hasExp, $kc['upgradeControllers'] ?? null, $hasGot, $fc['upgradeControllers'] ?? null);
            }
        }
    }
    foreach ($f as $id => $fc) {
        if (!array_key_exists($id, $k)) $out[] = _SwuPgnMm($seq, $pre . "cards[$id]", true, 'not in play', true, 'in play');
    }
    return $out;
}
