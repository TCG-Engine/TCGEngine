<?php
// SWU-PGN fold — events → board (SWU-PGN/1.0 spec §11 ReducedState, §12 folding, §13 keyframes).
//
// The board is plain PHP arrays shaped exactly like §11's JSON, keys in the spec's order. The
// reducer never takes references into the state: every update is copy-on-write, so a saved board
// (a timeline checkpoint, a caller's copy) is never changed behind its back.
//
// "Absent" is kept distinct from zero/empty wherever §11/§14 distinguish them: deckSize, resources,
// baseEpicActionUsed, initiativeTaken, leader, power/hp/keywords and onStartingSide are only ever
// created by the record or keyframe that supplies them.
//
// Untrusted input: every field is type-checked before use, a record the fold cannot apply is
// skipped (with a warning where the spec asks for one), and every list is capped (SWUPGN_MAX_*).

function SwuPgnEmptyState(): array
{
    $seat = function (int $n): array {
        return ['seat' => $n, 'baseHp' => 30, 'baseMaxHp' => 30, 'handSize' => 0, 'hand' => [],
            'resourcesReady' => 0, 'resourcesExhausted' => 0, 'credits' => 0, 'hasForce' => false,
            'discard' => [], 'cards' => []];
    };
    return ['round' => 0, 'phase' => 'setup', 'initiative' => null, 'players' => [1 => $seat(1), 2 => $seat(2)]];
}

// §6.2 — the complete zone vocabulary.
function SwuPgnZones(): array
{
    return ['deck', 'hand', 'resource', 'ground', 'space', 'discard', 'base', 'outsideTheGame', 'capture'];
}

function SwuPgnIsArena($zone): bool
{
    return $zone === 'ground' || $zone === 'space';
}

function _SwuPgnIsZone($zone): bool
{
    return is_string($zone) && in_array($zone, SwuPgnZones(), true);
}

// §10.2 notes: the folder reads them and does nothing.
function _SwuPgnNoteTypes(): array
{
    return ['ATTACK', 'PASS', 'CHOICE', 'MODAL_CHOICE', 'MULLIGAN', 'KEEP_HAND', 'SHUFFLE', 'SEARCH',
        'REVEAL', 'TRIGGER', 'PHASE_END', 'ROUND_END', 'GAME_END', 'UNDO', 'RESOURCE'];
}

// ── small helpers ────────────────────────────────────────────────────────────────────────────────

function _SwuPgnWarn(array &$w, $event, string $msg): void
{
    if (count($w) >= SWUPGN_MAX_WARNINGS) return;
    $seq = (is_array($event) && is_string($event['seq'] ?? null)) ? $event['seq'] : null;
    $w[] = ['seq' => $seq, 'message' => $msg];
}

// A count read from the state (which a keyframe may have filled with anything).
function _SwuPgnNum($v): int
{
    if (is_int($v)) return $v;
    if (is_float($v) && is_finite($v) && floor($v) === $v && abs($v) < 1e15) return (int)$v;
    return 0;
}

function _SwuPgnList($v): array
{
    return (is_array($v) && array_is_list($v)) ? $v : [];
}

// Adds once by id (§12.1: every id is unique for the whole game).
function _SwuPgnAddOnce($list, $id, int $cap, array &$w, $event): array
{
    $list = _SwuPgnList($list);
    if (in_array($id, $list, true)) return $list;
    if (count($list) >= $cap) {
        _SwuPgnWarn($w, $event, 'list limit reached; ' . (is_string($id) ? $id : 'id') . ' not recorded');
        return $list;
    }
    $list[] = $id;
    return $list;
}

function _SwuPgnRemoveId($list, $id): array
{
    $list = _SwuPgnList($list);
    if (!in_array($id, $list, true)) return $list;
    return array_values(array_filter($list, function ($x) use ($id) { return $x !== $id; }));
}

function _SwuPgnNewCard(string $id, string $zone): array
{
    return ['id' => $id, 'zone' => $zone, 'damage' => 0, 'exhausted' => false, 'upgrades' => [],
        'shields' => 0, 'experience' => 0, 'statusTokens' => [], 'captured' => []];
}

function _SwuPgnHasSeat(array $state, $p): bool
{
    return ($p === 1 || $p === 2) && is_array($state['players'][$p] ?? null);
}

// §12.2 findCard(): player 1's cards, then player 2's. [seat, index] or null — never a crash.
function _SwuPgnFindCard(array $state, $id): ?array
{
    if (!is_string($id)) return null;
    foreach ([1, 2] as $seat) {
        $cards = $state['players'][$seat]['cards'] ?? null;
        if (!is_array($cards)) continue;
        foreach ($cards as $i => $c) {
            if (is_array($c) && ($c['id'] ?? null) === $id) return [$seat, $i];
        }
    }
    return null;
}

// Removes the first card entry with this id from whichever seat holds it.
function _SwuPgnTakeCard(array $state, $id, ?array &$card = null, ?int &$seat = null): array
{
    $card = null;
    $seat = null;
    $f = _SwuPgnFindCard($state, $id);
    if ($f === null) return $state;
    [$seat, $i] = $f;
    $cards = $state['players'][$seat]['cards'];
    $card = $cards[$i];
    unset($cards[$i]);
    $state['players'][$seat]['cards'] = array_values($cards);
    return $state;
}

// "place": §12.2 PLAY / DEPLOY_LEADER / CREATE_TOKEN and §12.1 step 3. Idempotent by id: a tracked
// card just takes the zone. Arena zones only — "the fold places arena zones only" (§10.1).
function _SwuPgnPlace(array $state, int $p, $id, $zone, array &$w, $event): array
{
    if (!is_string($id) || !SwuPgnIsArena($zone) || !_SwuPgnHasSeat($state, $p)) return $state;
    $f = _SwuPgnFindCard($state, $id);
    if ($f !== null) {
        $state['players'][$f[0]]['cards'][$f[1]]['zone'] = $zone;
        return $state;
    }
    $cards = _SwuPgnList($state['players'][$p]['cards'] ?? null);
    if (count($cards) >= SWUPGN_MAX_CARDS_PER_SEAT) {
        _SwuPgnWarn($w, $event, "in-play limit reached; $id not placed");
        return $state;
    }
    $cards[] = _SwuPgnNewCard($id, $zone);
    $state['players'][$p]['cards'] = $cards;
    return $state;
}

// §12.2 attach(): a TOKEN: id is a counter, never an upgrade; otherwise push onto the tracked
// host's upgrades, idempotently.
function _SwuPgnAttach(array $state, $hostId, $id, array &$w, $event): array
{
    if (!is_string($id) || strncmp($id, 'TOKEN:', 6) === 0) return $state;
    $f = _SwuPgnFindCard($state, $hostId);
    if ($f === null) return $state;
    $host = $state['players'][$f[0]]['cards'][$f[1]];
    $state['players'][$f[0]]['cards'][$f[1]]['upgrades'] = _SwuPgnAddOnce($host['upgrades'] ?? null, $id, SWUPGN_MAX_ATTACHED, $w, $event);
    return $state;
}

// §12.2 detach(): off every card's upgrades and captured lists.
function _SwuPgnDetach(array $state, $id): array
{
    if (!is_string($id)) return $state;
    foreach ([1, 2] as $seat) {
        $cards = $state['players'][$seat]['cards'] ?? null;
        if (!is_array($cards)) continue;
        foreach ($cards as $i => $c) {
            if (!is_array($c)) continue;
            foreach (['upgrades', 'captured'] as $k) {
                if (is_array($c[$k] ?? null) && in_array($id, $c[$k], true)) {
                    $state['players'][$seat]['cards'][$i][$k] = _SwuPgnRemoveId($c[$k], $id);
                }
            }
        }
    }
    return $state;
}

// Applies $fn to the tracked card's entry (if any) and writes it back.
function _SwuPgnUpdateCard(array $state, $id, callable $fn): array
{
    $f = _SwuPgnFindCard($state, $id);
    if ($f === null) return $state;
    $state['players'][$f[0]]['cards'][$f[1]] = $fn($state['players'][$f[0]]['cards'][$f[1]]);
    return $state;
}

// Sets a leader flag on every seat whose leader.id is $id.
function _SwuPgnUpdateLeaderById(array $state, $id, string $field, $value): array
{
    if (!is_string($id)) return $state;
    foreach ([1, 2] as $seat) {
        $l = $state['players'][$seat]['leader'] ?? null;
        if (is_array($l) && ($l['id'] ?? null) === $id) $state['players'][$seat]['leader'][$field] = $value;
    }
    return $state;
}

// ── keyframes (§13) ──────────────────────────────────────────────────────────────────────────────

// Null when the keyframe can be snapped to; otherwise why it is damaged. §13: missing a seat, or a
// `cards` / `hand` / `discard` that is not an array, or a `cards` entry that is not an object.
// Beyond the spec, a keyframe whose lists exceed the reader's limits is refused the same way.
function SwuPgnKeyframeProblem($kf): ?string
{
    if (!is_array($kf) || array_is_list($kf)) return 'keyframe is not an object';
    $players = $kf['players'] ?? null;
    if (!is_array($players)) return 'keyframe has no seats';
    foreach ([1, 2] as $s) {
        $seat = $players[$s] ?? null;
        if (!is_array($seat) || array_is_list($seat)) return "keyframe is missing seat $s";
        foreach (['cards', 'hand', 'discard'] as $k) {
            if (!is_array($seat[$k] ?? null) || !array_is_list($seat[$k])) return "players.$s.$k is not an array";
        }
        foreach ($seat['cards'] as $c) {
            if (!($c instanceof stdClass) && (!is_array($c) || array_is_list($c))) return "players.$s.cards has an entry that is not an object";
        }
        if (count($seat['cards']) > SWUPGN_MAX_CARDS_PER_SEAT || count($seat['hand']) > SWUPGN_MAX_LIST
            || count($seat['discard']) > SWUPGN_MAX_LIST || (is_array($seat['resources'] ?? null) && count($seat['resources']) > SWUPGN_MAX_LIST)) {
            return "players.$s exceeds the reader's limits";
        }
        foreach ($seat['cards'] as $c) {
            if (!is_array($c)) continue;
            if ((is_array($c['upgrades'] ?? null) && count($c['upgrades']) > SWUPGN_MAX_ATTACHED)
                || (is_array($c['captured'] ?? null) && count($c['captured']) > SWUPGN_MAX_ATTACHED)
                || (is_array($c['statusTokens'] ?? null) && count($c['statusTokens']) > SWUPGN_MAX_TOKEN_KINDS)
                || (is_array($c['keywords'] ?? null) && count($c['keywords']) > SWUPGN_MAX_KEYWORDS)) {
                return "players.$s.cards has an entry beyond the reader's limits";
            }
        }
    }
    return null;
}

function _SwuPgnIsKeyframeEvent($e): bool
{
    return is_array($e) && (($e['t'] ?? null) === 'ROUND_START' || ($e['t'] ?? null) === 'ROUND_END')
        && array_key_exists('keyframe', $e);
}

// §12: `state = deepCopy(e.keyframe)`. PHP arrays copy by value, so the keyframe itself is the
// copy. (An empty JSON object inside it — `statusTokens: {}` — stays a stdClass; nothing ever writes
// into one: the reducer replaces it with an array when it first counts a token.)
function _SwuPgnSnap(array $kf): array
{
    return $kf;
}

// One fold step: snap to a usable keyframe, otherwise reduce (a damaged keyframe is warned about
// and the event's own rule still applies).
function _SwuPgnStep(array $state, $e, array &$w): array
{
    if (_SwuPgnIsKeyframeEvent($e)) {
        $problem = SwuPgnKeyframeProblem($e['keyframe']);
        if ($problem === null) return _SwuPgnSnap($e['keyframe']);
        _SwuPgnWarn($w, $e, "damaged keyframe ignored: $problem");
    }
    return SwuPgnReduce($state, $e, $w);
}

// ── fold entry points (§12, §12.3) ───────────────────────────────────────────────────────────────

// $start: the board before the first event (default SwuPgnEmptyState()). A reader with card data
// can supply what no event carries — each base's starting HP, each deck's size (§11 note).
function SwuPgnFold(array $events, ?array &$warnings = null, ?array $start = null): array
{
    $w = [];
    $state = $start ?? SwuPgnEmptyState();
    $n = 0;
    foreach ($events as $e) {
        if (++$n > SWUPGN_MAX_EVENTS) {
            _SwuPgnWarn($w, null, 'event limit reached; the rest of the file was not folded');
            break;
        }
        $state = _SwuPgnStep($state, $e, $w);
    }
    $warnings = $w;
    return $state;
}

// §12.3: everything up to and including the event with that seq (the whole list if none has it),
// starting from the last usable keyframe at or before it — identical result, linear scrubbing.
function SwuPgnStateAt(array $events, string $seq, ?array &$warnings = null): array
{
    $events = array_values(array_slice($events, 0, SWUPGN_MAX_EVENTS));
    $target = null;
    foreach ($events as $i => $e) {
        if (is_array($e) && ($e['seq'] ?? null) === $seq) {
            $target = $i;
            break;
        }
    }
    if ($target === null) return SwuPgnFold($events, $warnings);

    $w = [];
    $state = SwuPgnEmptyState();
    $start = 0;
    for ($i = $target; $i >= 0; $i--) {
        if (_SwuPgnIsKeyframeEvent($events[$i]) && SwuPgnKeyframeProblem($events[$i]['keyframe']) === null) {
            $start = $i;
            break;
        }
    }
    for ($i = $start; $i <= $target; $i++) $state = _SwuPgnStep($state, $events[$i], $w);
    $warnings = $w;
    return $state;
}

// A scrubbing timeline: one forward fold that keeps a checkpoint every SWUPGN_TIMELINE_STRIDE
// events (copy-on-write, so checkpoints share structure). Any position is then at most
// STRIDE−1 steps away.
function SwuPgnTimeline(array $events, ?array $start = null): array
{
    $events = array_values(array_slice($events, 0, SWUPGN_MAX_EVENTS));
    $w = [];
    $start = $start ?? SwuPgnEmptyState();
    $state = $start;
    $checkpoints = [];
    $seqIndex = [];
    $keyframes = [];
    foreach ($events as $i => $e) {
        $state = _SwuPgnStep($state, $e, $w);
        if (is_array($e) && is_string($e['seq'] ?? null) && !array_key_exists($e['seq'], $seqIndex)) $seqIndex[$e['seq']] = $i;
        if (_SwuPgnIsKeyframeEvent($e) && SwuPgnKeyframeProblem($e['keyframe']) === null) $keyframes[] = $i;
        if ($i % SWUPGN_TIMELINE_STRIDE === SWUPGN_TIMELINE_STRIDE - 1) $checkpoints[$i] = $state;
    }
    return ['events' => $events, 'count' => count($events), 'checkpoints' => $checkpoints,
        'seqIndex' => $seqIndex, 'keyframes' => $keyframes, 'final' => $state, 'warnings' => $w, 'start' => $start];
}

// The board after event #$pos (0-based); -1 is the empty board before any event. Out-of-range
// positions clamp.
function SwuPgnTimelineStateAt(array $tl, int $pos): array
{
    $count = $tl['count'];
    $start = $tl['start'] ?? SwuPgnEmptyState();
    if ($pos < 0 || $count === 0) return $start;
    if ($pos >= $count - 1) return $tl['final'];
    $cp = intdiv($pos + 1, SWUPGN_TIMELINE_STRIDE) * SWUPGN_TIMELINE_STRIDE - 1;
    $state = $cp >= 0 ? $tl['checkpoints'][$cp] : $start;
    $w = [];
    for ($i = $cp + 1; $i <= $pos; $i++) $state = _SwuPgnStep($state, $tl['events'][$i], $w);
    return $state;
}

function SwuPgnTimelineIndexOf(array $tl, string $seq): ?int
{
    return $tl['seqIndex'][$seq] ?? null;
}

// JSON for a board, with `{}` wherever §11 has an object: `players` and each `statusTokens`.
function SwuPgnStateToJson(array $state, bool $pretty = false): string
{
    if (is_array($state['players'] ?? null)) {
        foreach ($state['players'] as $s => $pl) {
            if (!is_array($pl) || !is_array($pl['cards'] ?? null)) continue;
            foreach ($pl['cards'] as $i => $c) {
                if (is_array($c) && is_array($c['statusTokens'] ?? null)) $state['players'][$s]['cards'][$i]['statusTokens'] = (object)$c['statusTokens'];
            }
        }
        $state['players'] = (object)$state['players'];
    }
    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE;
    return (string)json_encode($state, $flags | ($pretty ? JSON_PRETTY_PRINT : 0));
}

// ── the reducer (§12.1, §12.2) ───────────────────────────────────────────────────────────────────

function SwuPgnReduce(array $state, $e, ?array &$warnings = null): array
{
    if ($warnings === null) $warnings = [];
    if (!is_array($e) || !is_string($e['t'] ?? null)) {
        _SwuPgnWarn($warnings, $e, 'record has no event type; ignored');
        return $state;
    }
    $t = $e['t'];
    $p = _SwuPgnSeat($e['p'] ?? null);
    $card = is_string($e['card'] ?? null) ? $e['card'] : null;

    switch ($t) {
        case 'MOVE':
            return _SwuPgnReduceMove($state, $e, $warnings);

        case 'ROUND_START':
            if (is_int($e['round'] ?? null)) $state['round'] = $e['round'];
            $state['initiativeTaken'] = false;
            return $state;

        case 'PHASE_START':
            if (is_string($e['phase'] ?? null)) $state['phase'] = $e['phase'];
            return $state;

        case 'CLAIM_INITIATIVE':
            if ($p !== null) {
                $state['initiative'] = $p;
                $state['initiativeTaken'] = true;
            }
            return $state;

        case 'PLAY':
        case 'PLAY_SMUGGLE':
            if ($p === null || $card === null) return $state;
            return _SwuPgnPlace($state, $p, $card, $e['zone'] ?? 'ground', $warnings, $e);

        case 'PLAY_EVENT':
            if (!_SwuPgnHasSeat($state, $p) || $card === null) return $state;
            $state['players'][$p]['discard'] = _SwuPgnAddOnce($state['players'][$p]['discard'] ?? null, $card, SWUPGN_MAX_LIST, $warnings, $e);
            return $state;

        case 'PLAY_UPGRADE':
            if (!is_string($e['target'] ?? null)) return $state;
            return _SwuPgnAttach($state, $e['target'], $card, $warnings, $e);

        case 'DEPLOY_LEADER':
            if (!_SwuPgnHasSeat($state, $p) || $card === null) return $state;
            $prev = $state['players'][$p]['leader'] ?? null;
            $used = is_array($prev) && ($prev['epicActionUsed'] ?? null) === true;
            $state['players'][$p]['leader'] = ['id' => $card, 'deployed' => true, 'exhausted' => false,
                'epicActionUsed' => $used || ($e['epic'] ?? null) === true];
            if (($e['kind'] ?? null) === 'upgrade') return _SwuPgnAttach($state, $e['target'] ?? null, $card, $warnings, $e);
            return _SwuPgnPlace($state, $p, $card, $e['zone'] ?? 'ground', $warnings, $e);

        case 'ABILITY_ACTIVATE':
            // `epic: true` marks an Epic Action; §10.1 calls it "equivalent to kind: epic".
            if (($e['epic'] ?? null) !== true && ($e['kind'] ?? null) !== 'epic') return $state;
            $baseSeat = SwuPgnSeatOfBaseRef($card);
            if ($baseSeat !== null) {
                if (_SwuPgnHasSeat($state, $baseSeat)) $state['players'][$baseSeat]['baseEpicActionUsed'] = true;
                return $state;
            }
            return _SwuPgnUpdateLeaderById($state, $card, 'epicActionUsed', true);

        case 'LEADER_FLIP':
            $face = $e['onStartingSide'] ?? null;
            if (!is_bool($face) || $card === null) return $state;
            $seat = null;
            foreach ([1, 2] as $s) {
                $l = $state['players'][$s]['leader'] ?? null;
                if (is_array($l) && ($l['id'] ?? null) === $card) { $seat = $s; break; }
            }
            if ($seat === null) $seat = $p;
            if (!_SwuPgnHasSeat($state, $seat)) return $state;
            if (!is_array($state['players'][$seat]['leader'] ?? null)) {
                // No leader known yet (no keyframe): a double-sided leader sits in the base zone.
                $state['players'][$seat]['leader'] = ['id' => $card, 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false];
            }
            $state['players'][$seat]['leader']['onStartingSide'] = $face;
            return $state;

        case 'TAKE_CONTROL':
            return _SwuPgnReduceTakeControl($state, $e, $p, $card, $warnings);

        case 'CAPTURE':
            if ($card === null) return $state;
            do {
                $state = _SwuPgnTakeCard($state, $card, $taken);
            } while ($taken !== null);
            $by = $e['by'] ?? null;
            if (is_string($by) && SwuPgnSeatOfBaseRef($by) === null) {
                $state = _SwuPgnUpdateCard($state, $by, function (array $c) use ($card, &$warnings, $e) {
                    $c['captured'] = _SwuPgnAddOnce($c['captured'] ?? null, $card, SWUPGN_MAX_ATTACHED, $warnings, $e);
                    return $c;
                });
            }
            return $state;

        case 'RESCUE':
            return _SwuPgnDetach($state, $card);

        case 'EXHAUST_RESOURCES':
        case 'READY_RESOURCES':
            $amount = $e['amount'] ?? null;
            if (!_SwuPgnHasSeat($state, $p) || !is_int($amount) || $amount < 0) return $state;
            [$fromKey, $toKey] = $t === 'EXHAUST_RESOURCES' ? ['resourcesReady', 'resourcesExhausted'] : ['resourcesExhausted', 'resourcesReady'];
            $have = _SwuPgnNum($state['players'][$p][$fromKey] ?? 0);
            $n = max(0, min($amount, $have));
            $state['players'][$p][$fromKey] = $have - $n;
            $state['players'][$p][$toKey] = _SwuPgnNum($state['players'][$p][$toKey] ?? 0) + $n;
            return $state;

        case 'CREATE_TOKEN':
            $token = $e['token'] ?? null;
            if ($p === null || !is_string($token) || ($e['kind'] ?? null) === 'upgrade') return $state;
            return _SwuPgnPlace($state, $p, $token, $e['zone'] ?? null, $warnings, $e);

        case 'DAMAGE':
        case 'HEAL':
        case 'OVERWHELM':
            $tgt = $e['tgt'] ?? null;
            $baseSeat = SwuPgnSeatOfBaseRef($tgt);
            if ($baseSeat !== null) {
                if (_SwuPgnHasSeat($state, $baseSeat) && is_int($e['hp'] ?? null)) $state['players'][$baseSeat]['baseHp'] = $e['hp'];
                return $state;
            }
            $amt = $e['amt'] ?? null;
            if ($t === 'OVERWHELM' || !is_int($amt)) return $state;
            $sign = $t === 'DAMAGE' ? 1 : -1;
            return _SwuPgnUpdateCard($state, $tgt, function (array $c) use ($amt, $sign) {
                $c['damage'] = max(0, _SwuPgnNum($c['damage'] ?? 0) + $sign * $amt);
                return $c;
            });

        case 'DEFEAT':
            if ($card === null) return $state;
            $state = _SwuPgnDetach($state, $card);
            $state = _SwuPgnTakeCard($state, $card, $taken, $seat);
            if ($taken !== null) {
                $state['players'][$seat]['discard'] = _SwuPgnAddOnce($state['players'][$seat]['discard'] ?? null, $card, SWUPGN_MAX_LIST, $warnings, $e);
            }
            return $state;

        case 'EXHAUST':
        case 'READY':
            $flag = $t === 'EXHAUST';
            $state = _SwuPgnUpdateCard($state, $card, function (array $c) use ($flag) {
                $c['exhausted'] = $flag;
                return $c;
            });
            return _SwuPgnUpdateLeaderById($state, $card, 'exhausted', $flag);

        case 'STATS':
            return _SwuPgnUpdateCard($state, $card, function (array $c) use ($e) {
                if (is_int($e['power'] ?? null)) $c['power'] = $e['power'];
                if (is_int($e['hp'] ?? null)) $c['hp'] = $e['hp'];
                $kw = $e['keywords'] ?? null;
                if (is_array($kw) && array_is_list($kw) && count($kw) <= SWUPGN_MAX_KEYWORDS
                    && count(array_filter($kw, 'is_string')) === count($kw)) {
                    sort($kw, SORT_STRING);
                    $c['keywords'] = $kw;
                }
                return $c;
            });

        case 'DRAW':
        case 'DISCARD':
            $list = $e['cards'] ?? null;
            if (!_SwuPgnHasSeat($state, $p) || !is_array($list)) return $state;
            $key = $t === 'DRAW' ? 'hand' : 'discard';
            $cur = $state['players'][$p][$key] ?? null;
            foreach ($list as $id) {
                if (is_string($id)) $cur = _SwuPgnAddOnce($cur, $id, SWUPGN_MAX_LIST, $warnings, $e);
            }
            $state['players'][$p][$key] = _SwuPgnList($cur);
            return $state;

        case 'SHIELD_GAIN':
        case 'SHIELD_USE':
            $count = $e['count'] ?? 1;          // `count ?? 1`
            if (!is_int($count)) return $state;
            $delta = $t === 'SHIELD_GAIN' ? $count : -$count;
            return _SwuPgnUpdateCard($state, $card, function (array $c) use ($delta) {
                $c['shields'] = max(0, _SwuPgnNum($c['shields'] ?? 0) + $delta);
                return $c;
            });

        case 'EXPERIENCE_GAIN':
            $count = $e['count'] ?? null;
            if (!is_int($count)) return $state;
            return _SwuPgnUpdateCard($state, $card, function (array $c) use ($count) {
                $c['experience'] = max(0, _SwuPgnNum($c['experience'] ?? 0) + $count);
                return $c;
            });

        case 'STATUS_TOKEN':
            $count = $e['count'] ?? null;
            $token = $e['token'] ?? null;
            if (!is_int($count) || !is_string($token)) return $state;
            return _SwuPgnUpdateCard($state, $card, function (array $c) use ($count, $token, &$warnings, $e) {
                $tokens = is_array($c['statusTokens'] ?? null) ? $c['statusTokens'] : [];
                $n = max(0, _SwuPgnNum($tokens[$token] ?? 0) + $count);
                if ($n === 0) {
                    unset($tokens[$token]);                 // token rule 4: a key at zero is DELETED
                } elseif (array_key_exists($token, $tokens) || count($tokens) < SWUPGN_MAX_TOKEN_KINDS) {
                    $tokens[$token] = $n;
                } else {
                    _SwuPgnWarn($warnings, $e, 'status token limit reached');
                }
                $c['statusTokens'] = $tokens;
                return $c;
            });
    }

    if (in_array($t, _SwuPgnNoteTypes(), true)) return $state;
    // §3 / §18: an unknown type is "do nothing" and a warning, once per type.
    $msg = "unknown event type $t ignored";
    foreach ($warnings as $prior) {
        if ($prior['message'] === $msg) return $state;
    }
    _SwuPgnWarn($warnings, $e, $msg);
    return $state;
}

// §12.1 — the MOVE rule.
function _SwuPgnReduceMove(array $state, array $e, array &$w): array
{
    $card = $e['card'] ?? null;
    $from = $e['from'] ?? null;
    $to = $e['to'] ?? null;
    // §10.1: empty, unknown or identical zones make the record non-conformant — ignore it.
    if (!is_string($card) || $card === '' || !_SwuPgnIsZone($from) || !_SwuPgnIsZone($to) || $from === $to) {
        _SwuPgnWarn($w, $e, 'non-conformant MOVE ignored');
        return $state;
    }

    // Step 0 — detach. Exits are host-less: leaving an arena, or leaving capture, frees the card.
    if ((SwuPgnIsArena($from) && !SwuPgnIsArena($to)) || $from === 'capture') $state = _SwuPgnDetach($state, $card);

    $p = _SwuPgnSeat($e['p'] ?? null);
    if (!_SwuPgnHasSeat($state, $p)) {
        // No seat to attribute counts to: just update a tracked card's zone.
        $f = _SwuPgnFindCard($state, $card);
        if ($f !== null) $state['players'][$f[0]]['cards'][$f[1]]['zone'] = $to;
        return $state;
    }

    $pl = $state['players'][$p];

    // Step 1 — the hand, count AND contents.
    if ($to === 'hand') {
        $pl['handSize'] = _SwuPgnNum($pl['handSize'] ?? 0) + 1;
        $pl['hand'] = _SwuPgnAddOnce($pl['hand'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
    } elseif ($from === 'hand') {
        $pl['handSize'] = max(0, _SwuPgnNum($pl['handSize'] ?? 0) - 1);
        $pl['hand'] = _SwuPgnRemoveId($pl['hand'] ?? null, $card);
    }

    // Step 1a — the discard pile (the MOVE is its author, not DEFEAT).
    if ($to === 'discard') {
        $pl['discard'] = _SwuPgnAddOnce($pl['discard'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
    } elseif ($from === 'discard') {
        $pl['discard'] = _SwuPgnRemoveId($pl['discard'] ?? null, $card);
    }

    // Step 1b — the deck count, only once a keyframe has supplied it.
    if (is_int($pl['deckSize'] ?? null)) {
        if ($to === 'deck') $pl['deckSize']++;
        elseif ($from === 'deck') $pl['deckSize'] = max(0, $pl['deckSize'] - 1);
    }

    // Step 1c — the leader coming home.
    if (SwuPgnIsArena($from) && $to === 'base' && is_array($pl['leader'] ?? null) && ($pl['leader']['id'] ?? null) === $card) {
        $pl['leader']['deployed'] = false;
    }

    // Step 2 — the resource row: two counts plus membership.
    if ($to === 'resource') {
        $pl['resourcesReady'] = _SwuPgnNum($pl['resourcesReady'] ?? 0) + 1;
        $pl['resources'] = _SwuPgnAddOnce($pl['resources'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
    } elseif ($from === 'resource') {
        $bucket = ($e['exhausted'] ?? null) === true ? 'resourcesExhausted' : 'resourcesReady';
        $pl[$bucket] = max(0, _SwuPgnNum($pl[$bucket] ?? 0) - 1);
        if (array_key_exists('resources', $pl)) $pl['resources'] = _SwuPgnRemoveId($pl['resources'], $card);
    }

    // Step 2b — credits and the Force.
    $reserved = _SwuPgnReservedToken($card);
    if ($reserved === 'credit') {
        if ($to === 'base') $pl['credits'] = _SwuPgnNum($pl['credits'] ?? 0) + 1;
        elseif ($from === 'base') $pl['credits'] = max(0, _SwuPgnNum($pl['credits'] ?? 0) - 1);
    } elseif ($reserved === 'the-force') {
        if ($to === 'base') $pl['hasForce'] = true;
        elseif ($from === 'base') $pl['hasForce'] = false;
    }

    $state['players'][$p] = $pl;

    // Step 3 — the in-play list.
    if (($e['kind'] ?? null) === 'upgrade') {
        if (SwuPgnIsArena($to) && is_string($e['attachedTo'] ?? null)) $state = _SwuPgnAttach($state, $e['attachedTo'], $card, $w, $e);
        $f = _SwuPgnFindCard($state, $card);
        if ($f !== null) $state['players'][$f[0]]['cards'][$f[1]]['zone'] = $to;
        return $state;
    }
    if (SwuPgnIsArena($to)) return _SwuPgnPlace($state, $p, $card, $to, $w, $e);
    if (SwuPgnIsArena($from)) return _SwuPgnTakeCard($state, $card);
    $f = _SwuPgnFindCard($state, $card);
    if ($f !== null) $state['players'][$f[0]]['cards'][$f[1]]['zone'] = $to;
    return $state;
}

// §10.1 TAKE_CONTROL — a control change is not a zone change; this record re-seats the card.
function _SwuPgnReduceTakeControl(array $state, array $e, ?int $p, ?string $card, array &$w): array
{
    if (!_SwuPgnHasSeat($state, $p) || $card === null) return $state;
    $zone = $e['zone'] ?? null;

    if (SwuPgnIsArena($zone)) {
        $f = _SwuPgnFindCard($state, $card);
        if ($f === null || $f[0] === $p) return $state;
        if (count(_SwuPgnList($state['players'][$p]['cards'] ?? null)) >= SWUPGN_MAX_CARDS_PER_SEAT) {
            _SwuPgnWarn($w, $e, "in-play limit reached; $card not re-seated");
            return $state;
        }
        $state = _SwuPgnTakeCard($state, $card, $entry);
        $cards = _SwuPgnList($state['players'][$p]['cards'] ?? null);
        $cards[] = $entry;
        $state['players'][$p]['cards'] = $cards;
        return $state;
    }

    $from = _SwuPgnSeat($e['from'] ?? null);
    if ($from === null || $from === $p || !_SwuPgnHasSeat($state, $from)) return $state;

    if ($zone === 'resource') {
        $bucket = ($e['exhausted'] ?? null) === true ? 'resourcesExhausted' : 'resourcesReady';
        $state['players'][$from][$bucket] = max(0, _SwuPgnNum($state['players'][$from][$bucket] ?? 0) - 1);
        $state['players'][$p][$bucket] = _SwuPgnNum($state['players'][$p][$bucket] ?? 0) + 1;
        // "re-seats the card itself": the row MEMBERSHIP (§11 resources[], gated by §14) moves too, or
        // every keyframe after a steal reports both rows as mismatched.
        if (array_key_exists('resources', $state['players'][$from])) {
            $state['players'][$from]['resources'] = _SwuPgnRemoveId($state['players'][$from]['resources'], $card);
        }
        $state['players'][$p]['resources'] = _SwuPgnAddOnce($state['players'][$p]['resources'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
        return $state;
    }
    if ($zone === 'base') {
        $reserved = _SwuPgnReservedToken($card);
        if ($reserved === 'credit') {
            $state['players'][$from]['credits'] = max(0, _SwuPgnNum($state['players'][$from]['credits'] ?? 0) - 1);
            $state['players'][$p]['credits'] = _SwuPgnNum($state['players'][$p]['credits'] ?? 0) + 1;
        } elseif ($reserved === 'the-force') {
            $state['players'][$from]['hasForce'] = false;
            $state['players'][$p]['hasForce'] = true;
        }
    }
    return $state;
}
