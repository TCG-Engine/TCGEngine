<?php
// SWU-PGN fold — events → board (SWU-PGN/1.0 spec §11 ReducedState, §12 folding, §13 keyframes).
//
// The board is plain PHP arrays shaped exactly like §11's JSON, keys in the spec's order. The
// reducer never takes references into the state: every update is copy-on-write, so a saved board
// (a timeline checkpoint, a caller's copy) is never changed behind its back.
//
// "Absent" is kept distinct from zero/empty wherever §11/§14 distinguish them: deckSize, resources,
// baseEpicActionUsed, baseUpgrades, baseCaptured, baseDefeated, leader, active, effects, abilityUses,
// foreignResources, power/hp/keywords and the other STATS attributes, owner, upgradeControllers and
// onStartingSide are only ever created by the record or keyframe that supplies them.
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
    return ['round' => 0, 'phase' => 'setup', 'initiative' => null, 'initiativeTaken' => false, 'players' => [1 => $seat(1), 2 => $seat(2)]];
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

// A JSON object read as a PHP map (an empty `{}` stays a stdClass in a parsed file); anything else is empty.
function _SwuPgnMap($v): array
{
    if ($v instanceof stdClass) return get_object_vars($v);
    return (is_array($v) && !array_is_list($v)) ? $v : [];
}

// §23: every property of Object.prototype, and `prototype`. The fold never makes one a key.
function _SwuPgnFoldReservedName($s): bool
{
    static $names = ['__proto__', '__defineGetter__', '__defineSetter__', '__lookupGetter__', '__lookupSetter__', 'constructor',
        'hasOwnProperty', 'isPrototypeOf', 'propertyIsEnumerable', 'toLocaleString', 'toString', 'valueOf', 'prototype'];
    return is_string($s) && in_array($s, $names, true);
}

// A list of strings taken from a record (≤ SWUPGN_MAX_KEYWORDS), or null when it is not one.
function _SwuPgnStrings($v): ?array
{
    if (!is_array($v) || !array_is_list($v) || count($v) > SWUPGN_MAX_KEYWORDS) return null;
    foreach ($v as $x) if (!is_string($x)) return null;
    return $v;
}

// Sets a root key, a new one in §11's order (round, phase, initiative, initiativeTaken, active, players, effects).
function _SwuPgnSetRoot(array $state, string $key, $value): array
{
    if (array_key_exists($key, $state)) {
        $state[$key] = $value;
        return $state;
    }
    $order = ['round', 'phase', 'initiative', 'initiativeTaken', 'active', 'players', 'effects'];
    $pos = array_search($key, $order, true);
    $out = [];
    foreach ($state as $k => $v) {
        $ki = array_search($k, $order, true);
        if ($pos !== false && $ki !== false && $ki > $pos && !array_key_exists($key, $out)) $out[$key] = $value;
        $out[$k] = $v;
    }
    if (!array_key_exists($key, $out)) $out[$key] = $value;
    return $out;
}

// A card's keys in §11's order (unknown keys after) — so a STATS / control change reads like a keyframe.
function _SwuPgnCardOrder(array $c): array
{
    static $order = ['id', 'zone', 'damage', 'exhausted', 'upgrades', 'shields', 'experience', 'statusTokens', 'captured', 'power', 'hp',
        'keywords', 'traits', 'blank', 'leaderUnit', 'copyOf', 'name', 'cost', 'aspects', 'owner', 'upgradeControllers'];
    $out = [];
    foreach ($order as $k) if (array_key_exists($k, $c)) $out[$k] = $c[$k];
    return $out + $c;
}

// §12 / [F-05]: upgrades and captives held by one seat's cards between them.
function _SwuPgnSeatAttached(array $state, int $seat): int
{
    $n = 0;
    $cards = $state['players'][$seat]['cards'] ?? null;
    if (!is_array($cards)) return 0;
    foreach ($cards as $c) {
        if (!is_array($c)) continue;
        if (is_array($c['upgrades'] ?? null)) $n += count($c['upgrades']);
        if (is_array($c['captured'] ?? null)) $n += count($c['captured']);
    }
    return $n;
}

// §12.1 "Whose pile": the seat whose $key list holds $id — p's if there, else the other seat's, else p's.
function _SwuPgnHolder(array $state, int $p, string $key, string $id): int
{
    foreach ([$p, 3 - $p] as $s) {
        $l = $state['players'][$s][$key] ?? null;
        if (is_array($l) && in_array($id, $l, true)) return $s;
    }
    return $p;
}

// §11 upgradeControllers: the host at [seat, i] notes $ctl as the controller of upgrade $id — an entry
// when $ctl is not the holder, none when it is; a host left with no entry has no upgradeControllers.
function _SwuPgnSetController(array $state, int $seat, int $i, string $id, int $ctl): array
{
    if (_SwuPgnFoldReservedName($id)) return $state;           // §23
    $c = $state['players'][$seat]['cards'][$i];
    $uc = _SwuPgnMap($c['upgradeControllers'] ?? null);
    if ($ctl !== $seat) $uc[$id] = $ctl; else unset($uc[$id]);
    if ($uc) $c['upgradeControllers'] = $uc; else unset($c['upgradeControllers']);
    $state['players'][$seat]['cards'][$i] = _SwuPgnCardOrder($c);
    return $state;
}

// §12.1 step 3 / §12.2: "note p as its controller on the host" — only when the printed card did go onto
// the host's upgrades.
function _SwuPgnNoteController(array $state, $hostId, $id, ?int $p): array
{
    if ($p === null || !is_string($id)) return $state;
    $f = _SwuPgnFindCard($state, $hostId);
    if ($f === null) return $state;
    $ups = $state['players'][$f[0]]['cards'][$f[1]]['upgrades'] ?? null;
    if (!is_array($ups) || !in_array($id, $ups, true)) return $state;
    return _SwuPgnSetController($state, $f[0], $f[1], $id, $p);
}

// Removes every seat's abilityUses entries whose `per` is one of $pers (§12.2 ROUND_START / PHASE_START).
function _SwuPgnDropUses(array $state, array $pers): array
{
    foreach ([1, 2] as $s) {
        $uses = $state['players'][$s]['abilityUses'] ?? null;
        if (!is_array($uses)) continue;
        $state['players'][$s]['abilityUses'] = array_values(array_filter($uses, function ($u) use ($pers) {
            return !(is_array($u) && in_array($u['per'] ?? null, $pers, true));
        }));
    }
    return $state;
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

// §12.2 attach(): a TOKEN: id is a counter, never an upgrade; otherwise push onto the host's upgrades,
// idempotently. A base host is not attach()'s: PLAY_UPGRADE and §12.1 step 2c put it on baseUpgrades.
function _SwuPgnAttach(array $state, $hostId, $id, array &$w, $event): array
{
    if (!is_string($id) || strncmp($id, 'TOKEN:', 6) === 0) return $state;
    $f = _SwuPgnFindCard($state, $hostId);
    if ($f === null) return $state;
    $ups = _SwuPgnList($state['players'][$f[0]]['cards'][$f[1]]['upgrades'] ?? null);
    if (in_array($id, $ups, true)) return $state;
    if (_SwuPgnSeatAttached($state, $f[0]) >= SWUPGN_MAX_SEAT_ATTACHED) {   // §12: a seat's cards hold ≤ 1000 between them
        _SwuPgnWarn($w, $event, "seat attachment limit reached; $id not attached");
        return $state;
    }
    $state['players'][$f[0]]['cards'][$f[1]]['upgrades'] = _SwuPgnAddOnce($ups, $id, SWUPGN_MAX_ATTACHED, $w, $event);
    return $state;
}

// A card a base holds: onto that seat's baseUpgrades or baseCaptured, idempotently.
function _SwuPgnBaseHold(array $state, int $seat, string $key, string $id, array &$w, $event): array
{
    if (!_SwuPgnHasSeat($state, $seat)) return $state;
    $state['players'][$seat][$key] = _SwuPgnAddOnce($state['players'][$seat][$key] ?? null, $id, SWUPGN_MAX_ATTACHED, $w, $event);
    return $state;
}

// §12.2 detach(): off every card's upgrades, upgradeControllers and captured, and every seat's baseCaptured.
// A base's upgrades come off on their own transition (§12.1 step 2c) — _SwuPgnBaseRelease.
function _SwuPgnDetach(array $state, $id): array
{
    if (!is_string($id)) return $state;
    foreach ([1, 2] as $seat) {
        $held = $state['players'][$seat]['baseCaptured'] ?? null;
        if (is_array($held) && in_array($id, $held, true)) $state['players'][$seat]['baseCaptured'] = _SwuPgnRemoveId($held, $id);
        $cards = $state['players'][$seat]['cards'] ?? null;
        if (!is_array($cards)) continue;
        foreach ($cards as $i => $c) {
            if (!is_array($c)) continue;
            foreach (['upgrades', 'captured'] as $k) {
                if (is_array($c[$k] ?? null) && in_array($id, $c[$k], true)) {
                    $state['players'][$seat]['cards'][$i][$k] = _SwuPgnRemoveId($c[$k], $id);
                }
            }
            $uc = $c['upgradeControllers'] ?? null;
            if (is_array($uc) && array_key_exists($id, $uc)) {
                unset($uc[$id]);
                if ($uc) $state['players'][$seat]['cards'][$i]['upgradeControllers'] = $uc;
                else unset($state['players'][$seat]['cards'][$i]['upgradeControllers']);
            }
        }
    }
    return $state;
}

// Off every seat's baseUpgrades (§12.1 step 2c, §12.2 DEFEAT).
function _SwuPgnBaseRelease(array $state, $id): array
{
    foreach ([1, 2] as $seat) {
        $held = $state['players'][$seat]['baseUpgrades'] ?? null;
        if (is_array($held) && in_array($id, $held, true)) $state['players'][$seat]['baseUpgrades'] = _SwuPgnRemoveId($held, $id);
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

// Null when the keyframe can be snapped to; otherwise why it is damaged — the §13 list, condition by
// condition. (In a parsed file an empty `{}` is a stdClass and `[]` an empty array; a keyframe built in PHP
// cannot tell the two apart, so an empty array is accepted where §13 asks for an object.)
function SwuPgnKeyframeProblem($kf): ?string
{
    $isList = function ($v): bool { return is_array($v) && array_is_list($v); };
    $isObj = function ($v): bool { return $v instanceof stdClass || (is_array($v) && (!array_is_list($v) || $v === [])); };
    $isNum = function ($v): bool { return is_int($v) || is_float($v); };
    if (!is_array($kf) || array_is_list($kf)) return 'keyframe is not an object';
    $players = $kf['players'] ?? null;
    if (!is_array($players)) return 'keyframe has no seats';
    if (array_is_list($players) && $players !== []) return 'keyframe players is not an object';
    foreach ([1, 2] as $s) {
        $seat = $players[$s] ?? null;
        if (!is_array($seat) || array_is_list($seat)) return "keyframe is missing seat $s";
        foreach (['cards', 'hand', 'discard'] as $k) {
            if (!$isList($seat[$k] ?? null)) return "players.$s.$k is not an array";
        }
        foreach (['baseUpgrades', 'baseCaptured', 'resources', 'foreignResources'] as $k) {
            if (array_key_exists($k, $seat) && !$isList($seat[$k])) return "players.$s.$k is not an array";
        }
        foreach (['cards' => SWUPGN_MAX_CARDS_PER_SEAT, 'hand' => SWUPGN_MAX_LIST, 'discard' => SWUPGN_MAX_LIST, 'resources' => SWUPGN_MAX_LIST,
            'foreignResources' => SWUPGN_MAX_LIST, 'baseUpgrades' => SWUPGN_MAX_ATTACHED, 'baseCaptured' => SWUPGN_MAX_ATTACHED] as $k => $cap) {
            if (is_array($seat[$k] ?? null) && count($seat[$k]) > $cap) return "players.$s.$k holds more than $cap entries";
        }
        $attached = 0;
        foreach ($seat['cards'] as $c) {
            if ($c instanceof stdClass) $c = [];
            elseif (!is_array($c) || (array_is_list($c) && $c !== [])) return "players.$s.cards has an entry that is not an object";
            if (!$isList($c['upgrades'] ?? null) || count($c['upgrades']) > SWUPGN_MAX_ATTACHED) return "players.$s.cards has an entry without an upgrades array of at most " . SWUPGN_MAX_ATTACHED;
            if (!$isObj($c['statusTokens'] ?? null)) return "players.$s.cards has an entry without a statusTokens object";
            if (is_array($c['statusTokens']) && count($c['statusTokens']) > SWUPGN_MAX_TOKEN_KINDS) return "players.$s.cards has an entry over the statusTokens cap";
            foreach (['damage', 'shields', 'experience'] as $k) {
                if (!$isNum($c[$k] ?? null)) return "players.$s.cards has an entry without a number $k";
            }
            if (array_key_exists('captured', $c) && (!$isList($c['captured']) || count($c['captured']) > SWUPGN_MAX_ATTACHED)) return "players.$s.cards has an entry whose captured is not an array of at most " . SWUPGN_MAX_ATTACHED;
            if (array_key_exists('upgradeControllers', $c) && !$isObj($c['upgradeControllers'])) return "players.$s.cards has an entry whose upgradeControllers is not an object";
            $attached += count($c['upgrades']) + (is_array($c['captured'] ?? null) ? count($c['captured']) : 0);
        }
        if ($attached > SWUPGN_MAX_SEAT_ATTACHED) return "players.$s.cards hold more than " . SWUPGN_MAX_SEAT_ATTACHED . ' upgrades and captives';
        if (array_key_exists('abilityUses', $seat)) {
            $uses = $seat['abilityUses'];
            if (!$isList($uses) || count($uses) > SWUPGN_MAX_ABILITY_USES) return "players.$s.abilityUses is not an array of at most " . SWUPGN_MAX_ABILITY_USES;
            foreach ($uses as $u) {
                if (!is_array($u) || array_is_list($u) || !is_string($u['card'] ?? null) || !is_string($u['ability'] ?? null) || !$isNum($u['count'] ?? null)) {
                    return "players.$s.abilityUses has an entry without a string card, a string ability and a number count";
                }
            }
        }
    }
    if (array_key_exists('effects', $kf)) {
        $fx = $kf['effects'];
        if (!$isList($fx) || count($fx) > SWUPGN_MAX_EFFECTS) return 'effects is not an array of at most ' . SWUPGN_MAX_EFFECTS;
        foreach ($fx as $x) {
            if (!is_array($x) || array_is_list($x) || !is_string($x['id'] ?? null)) return 'effects has an entry without a string id';
            if (array_key_exists('targets', $x) && (!$isList($x['targets']) || count($x['targets']) > SWUPGN_MAX_KEYWORDS)) return 'effects has an entry whose targets is not an array of at most ' . SWUPGN_MAX_KEYWORDS;
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

// JSON for a board, with `{}` wherever §11 has an object: `players`, each `statusTokens` and each
// `upgradeControllers` (which is never emitted empty — absent when no upgrade has another controller).
function SwuPgnStateToJson(array $state, bool $pretty = false): string
{
    if (is_array($state['players'] ?? null)) {
        foreach ($state['players'] as $s => $pl) {
            if (!is_array($pl) || !is_array($pl['cards'] ?? null)) continue;
            foreach ($pl['cards'] as $i => $c) {
                if (!is_array($c)) continue;
                if (is_array($c['statusTokens'] ?? null)) $state['players'][$s]['cards'][$i]['statusTokens'] = (object)$c['statusTokens'];
                if (array_key_exists('upgradeControllers', $c)) {
                    $uc = _SwuPgnMap($c['upgradeControllers']);
                    if ($uc) $state['players'][$s]['cards'][$i]['upgradeControllers'] = (object)$uc;
                    else unset($state['players'][$s]['cards'][$i]['upgradeControllers']);
                }
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
            $state = _SwuPgnDropUses(_SwuPgnSetRoot($state, 'initiativeTaken', false), ['phase', 'round']);
            $active = _SwuPgnSeat($e['active'] ?? null);
            if ($active !== null) {                    // CR 5.4.1.b: the active player starts with the initiative
                $state = _SwuPgnSetRoot($state, 'active', $active);
                $state['initiative'] = $active;
            }
            return $state;

        case 'PHASE_START':
            if (is_string($e['phase'] ?? null)) $state['phase'] = $e['phase'];
            $state = _SwuPgnDropUses($state, ['phase']);
            $active = _SwuPgnSeat($e['active'] ?? null);
            return $active === null ? $state : _SwuPgnSetRoot($state, 'active', $active);

        case 'CLAIM_INITIATIVE':
            if ($p !== null) {
                $state['initiative'] = $p;
                $state = _SwuPgnSetRoot($state, 'initiativeTaken', true);
            }
            return $state;

        case 'PLAY':
        case 'PLAY_SMUGGLE':
            if ($p === null || $card === null) return $state;
            return _SwuPgnPlace($state, $p, $card, $e['zone'] ?? 'ground', $warnings, $e);

        case 'PLAY_EVENT':
            if (!_SwuPgnHasSeat($state, $p) || $card === null) return $state;
            foreach ([1, 2] as $s) {                   // "unless a seat's discard already holds it"
                $d = $state['players'][$s]['discard'] ?? null;
                if (is_array($d) && in_array($card, $d, true)) return $state;
            }
            $state['players'][$p]['discard'] = _SwuPgnAddOnce($state['players'][$p]['discard'] ?? null, $card, SWUPGN_MAX_LIST, $warnings, $e);
            return $state;

        case 'PLAY_UPGRADE':
            if (!is_string($e['target'] ?? null) || $card === null) return $state;
            $baseSeat = SwuPgnSeatOfBaseRef($e['target']);
            if ($baseSeat !== null) return _SwuPgnBaseHold($state, $baseSeat, 'baseUpgrades', $card, $warnings, $e);
            return _SwuPgnNoteController(_SwuPgnAttach($state, $e['target'], $card, $warnings, $e), $e['target'], $card, $p);

        case 'DEPLOY_LEADER':
            if (!_SwuPgnHasSeat($state, $p) || $card === null) return $state;
            $prev = $state['players'][$p]['leader'] ?? null;
            $used = is_array($prev) && ($prev['epicActionUsed'] ?? null) === true;
            $state['players'][$p]['leader'] = ['id' => $card, 'deployed' => true, 'exhausted' => false,
                'epicActionUsed' => $used || ($e['epic'] ?? null) === true];
            if (($e['kind'] ?? null) === 'upgrade') {
                return _SwuPgnNoteController(_SwuPgnAttach($state, $e['target'] ?? null, $card, $warnings, $e), $e['target'] ?? null, $card, $p);
            }
            return _SwuPgnPlace($state, $p, $card, $e['zone'] ?? 'ground', $warnings, $e);

        case 'ABILITY_ACTIVATE':
            $state = _SwuPgnCountUse($state, $e, $p, $card, $warnings);
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

        case 'ABILITY_RESET':
            if ($card === null) return $state;
            $ability = is_string($e['ability'] ?? null) ? $e['ability'] : null;
            foreach ([1, 2] as $s) {
                $uses = $state['players'][$s]['abilityUses'] ?? null;
                if (!is_array($uses)) continue;
                $state['players'][$s]['abilityUses'] = array_values(array_filter($uses, function ($u) use ($card, $ability) {
                    return !(is_array($u) && ($u['card'] ?? null) === $card && ($ability === null || ($u['ability'] ?? null) === $ability));
                }));
            }
            return $state;

        case 'EFFECT_START':
            return _SwuPgnEffectStart($state, $e, $p, $warnings);

        case 'EFFECT_END':
            return _SwuPgnEffectEnd($state, $e);

        case 'TAKE_CONTROL':
            return _SwuPgnReduceTakeControl($state, $e, $p, $card, $warnings);

        case 'CAPTURE':
            if ($card === null) return $state;
            do {
                $state = _SwuPgnTakeCard($state, $card, $taken);
            } while ($taken !== null);
            if (strncmp($card, 'TOKEN:', 6) === 0) return $state;           // a token is held nowhere
            $by = $e['by'] ?? null;
            $baseSeat = SwuPgnSeatOfBaseRef($by);
            if ($baseSeat !== null) return _SwuPgnBaseHold($state, $baseSeat, 'baseCaptured', $card, $warnings, $e);   // held on the base until rescued
            $f = _SwuPgnFindCard($state, $by);
            if ($f === null) return $state;
            $held = _SwuPgnList($state['players'][$f[0]]['cards'][$f[1]]['captured'] ?? null);
            if (in_array($card, $held, true)) return $state;
            if (_SwuPgnSeatAttached($state, $f[0]) >= SWUPGN_MAX_SEAT_ATTACHED) {   // §12: ≤ 1000 upgrades + captives per seat
                _SwuPgnWarn($warnings, $e, "seat attachment limit reached; $card not held");
                return $state;
            }
            $state['players'][$f[0]]['cards'][$f[1]]['captured'] = _SwuPgnAddOnce($held, $card, SWUPGN_MAX_ATTACHED, $warnings, $e);
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
            // §10.1: a base is defeated with a DEFEAT too — by damage or outright (its HP may still stand).
            $baseSeat = SwuPgnSeatOfBaseRef($card);
            if ($baseSeat !== null) {
                if (_SwuPgnHasSeat($state, $baseSeat)) $state['players'][$baseSeat]['baseDefeated'] = true;
                return $state;
            }
            $state = _SwuPgnBaseRelease(_SwuPgnDetach($state, $card), $card);
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
                $kw = _SwuPgnStrings($e['keywords'] ?? null);
                if ($kw !== null) {
                    sort($kw, SORT_STRING);
                    $c['keywords'] = $kw;
                }
                // The other seven are stated all at once: set what the record carries, remove what it leaves out.
                $traits = _SwuPgnStrings($e['traits'] ?? null);
                if ($traits !== null) sort($traits, SORT_STRING);
                $aspects = _SwuPgnStrings($e['aspects'] ?? null);
                $set = ['traits' => $traits, 'blank' => ($e['blank'] ?? null) === true ? true : null,
                    'leaderUnit' => ($e['leaderUnit'] ?? null) === true ? true : null,
                    'copyOf' => is_string($e['copyOf'] ?? null) ? $e['copyOf'] : null, 'name' => is_string($e['name'] ?? null) ? $e['name'] : null,
                    'cost' => is_int($e['cost'] ?? null) ? $e['cost'] : null, 'aspects' => $aspects];
                foreach ($set as $k => $v) {
                    if ($v === null) unset($c[$k]); else $c[$k] = $v;
                }
                return _SwuPgnCardOrder($c);
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
            if (!is_int($count) || !is_string($token) || _SwuPgnFoldReservedName($token)) return $state;   // §23
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

// §12.2 ABILITY_ACTIVATE `limit`: one use of a limited ability, counted in players[p].abilityUses under
// card + ability (created at the first use; count + 1, the latest per and max). `per` is closed.
function _SwuPgnCountUse(array $state, array $e, ?int $p, ?string $card, array &$w): array
{
    $limit = $e['limit'] ?? null;
    $ability = $e['ability'] ?? null;
    if (!is_array($limit) || array_is_list($limit) || $card === null || !is_string($ability) || !_SwuPgnHasSeat($state, $p)) return $state;
    $per = $limit['per'] ?? null;
    if (!in_array($per, ['phase', 'round', 'game'], true)) return $state;
    $max = $limit['max'] ?? null;
    if (!is_int($max) && !is_float($max)) $max = 0;
    $uses = _SwuPgnList($state['players'][$p]['abilityUses'] ?? null);
    foreach ($uses as $i => $u) {
        if (is_array($u) && ($u['card'] ?? null) === $card && ($u['ability'] ?? null) === $ability) {
            $uses[$i]['count'] = _SwuPgnNum($u['count'] ?? 0) + 1;
            $uses[$i]['per'] = $per;
            $uses[$i]['max'] = $max;
            $state['players'][$p]['abilityUses'] = $uses;
            return $state;
        }
    }
    if (count($uses) >= SWUPGN_MAX_ABILITY_USES) _SwuPgnWarn($w, $e, 'ability use limit reached; use not counted');
    else $uses[] = ['card' => $card, 'ability' => $ability, 'count' => 1, 'per' => $per, 'max' => $max];
    $state['players'][$p]['abilityUses'] = $uses;
    return $state;
}

// §12.2 EFFECT_START: { id, src, p, targets?, seat?, duration } — never untilText / ability / text. The same
// id is replaced in place. `seat` is kept only when 1 or 2 (the table's guard).
function _SwuPgnEffectStart(array $state, array $e, ?int $p, array &$w): array
{
    $id = $e['id'] ?? null;
    $src = $e['src'] ?? null;
    $duration = $e['duration'] ?? null;
    if (!is_string($id) || !is_string($src) || !is_string($duration) || $p === null) return $state;
    $fx = ['id' => $id, 'src' => $src, 'p' => $p];
    $targets = $e['targets'] ?? null;
    if (is_array($targets) && array_is_list($targets)) {
        $fx['targets'] = [];
        foreach ($targets as $t) {
            if (is_string($t) && count($fx['targets']) < SWUPGN_MAX_KEYWORDS) $fx['targets'][] = $t;
        }
    }
    $seat = _SwuPgnSeat($e['seat'] ?? null);
    if ($seat !== null) $fx['seat'] = $seat;
    $fx['duration'] = $duration;
    $effects = _SwuPgnList($state['effects'] ?? null);
    $at = null;
    foreach ($effects as $i => $x) {
        if (is_array($x) && ($x['id'] ?? null) === $id) { $at = $i; break; }
    }
    if ($at !== null) $effects[$at] = $fx;
    elseif (count($effects) >= SWUPGN_MAX_EFFECTS) _SwuPgnWarn($w, $e, "effect limit reached; $id not recorded");
    else $effects[] = $fx;
    return _SwuPgnSetRoot($state, 'effects', $effects);
}

// §12.2 EFFECT_END: no targets (or empty) removes the effect; targets trims them, and an effect left with
// none is removed; an effect that has no targets is left as it is. A reader never drops one on its own.
function _SwuPgnEffectEnd(array $state, array $e): array
{
    $id = $e['id'] ?? null;
    $effects = $state['effects'] ?? null;
    if (!is_string($id) || !is_array($effects)) return $state;
    foreach ($effects as $i => $x) {
        if (!is_array($x) || ($x['id'] ?? null) !== $id) continue;
        $gone = $e['targets'] ?? null;
        if (is_array($gone) && $gone !== []) {
            $had = $x['targets'] ?? null;
            if (!is_array($had) || $had === []) return $state;
            $left = array_values(array_filter($had, function ($t) use ($gone) { return !in_array($t, $gone, true); }));
            if ($left !== []) {
                $state['effects'][$i]['targets'] = $left;
                return $state;
            }
        }
        unset($effects[$i]);
        $state['effects'] = array_values($effects);
        return $state;
    }
    return $state;
}

// §12.1 — the MOVE rule.
function _SwuPgnReduceMove(array $state, array $e, array &$w): array
{
    $card = $e['card'] ?? null;
    $from = $e['from'] ?? null;
    $to = $e['to'] ?? null;
    // §10.1: empty, unknown or identical zones make the record non-conformant — ignore it. The one MOVE that
    // names the same zone twice is an attachment rebound inside its arena (the binding rule), carrying its new role.
    $rebind = $from === $to && SwuPgnIsArena($to) && is_string($e['kind'] ?? null);
    if (!is_string($card) || $card === '' || !_SwuPgnIsZone($from) || !_SwuPgnIsZone($to) || ($from === $to && !$rebind)) {
        _SwuPgnWarn($w, $e, 'non-conformant MOVE ignored');
        return $state;
    }
    $p = _SwuPgnSeat($e['p'] ?? null);
    $isUpgrade = ($e['kind'] ?? null) === 'upgrade';

    // Step 0 — detach. Exits are host-less: leaving an arena for a zone outside them, or leaving capture, frees the card.
    if ((SwuPgnIsArena($from) && !SwuPgnIsArena($to)) || $from === 'capture') $state = _SwuPgnDetach($state, $card);

    // Step 2c — base attachments (Fortify), keyed on the zone transition; no p needed when attachedTo names the base.
    if ($from === 'base' && $to !== 'base') $state = _SwuPgnBaseRelease($state, $card);
    if ($to === 'base' && $from !== 'base' && $isUpgrade && strncmp($card, 'TOKEN:', 6) !== 0) {
        $hostSeat = SwuPgnSeatOfBaseRef($e['attachedTo'] ?? null) ?? $p;   // a file before attachedTo on base moves: p's base
        if ($hostSeat !== null) $state = _SwuPgnBaseHold($state, $hostSeat, 'baseUpgrades', $card, $w, $e);
    }

    if (!_SwuPgnHasSeat($state, $p)) {
        // No seat to attribute counts to: just update a tracked card's zone.
        $f = _SwuPgnFindCard($state, $card);
        if ($f !== null) $state['players'][$f[0]]['cards'][$f[1]]['zone'] = $to;
        return $state;
    }
    $other = 3 - $p;

    // Whose pile: arriving goes to p's; leaving hand / discard / resource is taken (with the count) from the seat
    // whose list holds it — p's if there, else the other seat's, else p's. What that pile says about the owner
    // (step 3) is read here, before the piles change.
    $src = $p;
    $ownedByOther = false;
    if ($from === 'hand' || $from === 'discard') {
        $src = _SwuPgnHolder($state, $p, $from, $card);
        $ownedByOther = $src !== $p;
    } elseif ($from === 'resource') {
        $src = _SwuPgnHolder($state, $p, 'resources', $card);
        $foreign = function (int $s) use ($state, $card): bool {
            $l = $state['players'][$s]['foreignResources'] ?? null;
            return is_array($l) && in_array($card, $l, true);
        };
        $ownedByOther = $src !== $p ? !$foreign($src) : $foreign($p);
    }

    // Step 1 — the hand, count AND contents.
    if ($to === 'hand') {
        $state['players'][$p]['handSize'] = _SwuPgnNum($state['players'][$p]['handSize'] ?? 0) + 1;
        $state['players'][$p]['hand'] = _SwuPgnAddOnce($state['players'][$p]['hand'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
    } elseif ($from === 'hand') {
        $state['players'][$src]['handSize'] = max(0, _SwuPgnNum($state['players'][$src]['handSize'] ?? 0) - 1);
        $state['players'][$src]['hand'] = _SwuPgnRemoveId($state['players'][$src]['hand'] ?? null, $card);
    }

    // Step 1a — the discard pile (the MOVE is its author, not DEFEAT).
    if ($to === 'discard') {
        $state['players'][$p]['discard'] = _SwuPgnAddOnce($state['players'][$p]['discard'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
    } elseif ($from === 'discard') {
        $state['players'][$src]['discard'] = _SwuPgnRemoveId($state['players'][$src]['discard'] ?? null, $card);
    }

    // Step 1b — the deck count, on p, only once a keyframe has supplied it.
    if (is_int($state['players'][$p]['deckSize'] ?? null)) {
        if ($to === 'deck') $state['players'][$p]['deckSize']++;
        elseif ($from === 'deck') $state['players'][$p]['deckSize'] = max(0, $state['players'][$p]['deckSize'] - 1);
    }

    // Step 1c — the leader by its id: arena → base undeploys it, base → arena deploys it (CR 3.4.4.b).
    if (SwuPgnIsArena($from) && $to === 'base') $state = _SwuPgnUpdateLeaderById($state, $card, 'deployed', false);
    elseif ($from === 'base' && SwuPgnIsArena($to)) $state = _SwuPgnUpdateLeaderById($state, $card, 'deployed', true);

    // Step 2 — the resource row: two counts plus membership (and foreignResources on the way out).
    if ($to === 'resource') {
        $bucket = ($e['exhausted'] ?? null) === true ? 'resourcesExhausted' : 'resourcesReady';
        $state['players'][$p][$bucket] = _SwuPgnNum($state['players'][$p][$bucket] ?? 0) + 1;
        $state['players'][$p]['resources'] = _SwuPgnAddOnce($state['players'][$p]['resources'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
    } elseif ($from === 'resource') {
        $bucket = ($e['exhausted'] ?? null) === true ? 'resourcesExhausted' : 'resourcesReady';
        $state['players'][$src][$bucket] = max(0, _SwuPgnNum($state['players'][$src][$bucket] ?? 0) - 1);
        foreach (['resources', 'foreignResources'] as $k) {
            if (array_key_exists($k, $state['players'][$src])) $state['players'][$src][$k] = _SwuPgnRemoveId($state['players'][$src][$k], $card);
        }
    }

    // Step 2b — credits and the Force.
    $reserved = _SwuPgnReservedToken($card);
    if ($reserved === 'credit') {
        if ($to === 'base') $state['players'][$p]['credits'] = _SwuPgnNum($state['players'][$p]['credits'] ?? 0) + 1;
        elseif ($from === 'base') $state['players'][$p]['credits'] = max(0, _SwuPgnNum($state['players'][$p]['credits'] ?? 0) - 1);
    } elseif ($reserved === 'the-force') {
        if ($to === 'base') $state['players'][$p]['hasForce'] = true;
        elseif ($from === 'base') $state['players'][$p]['hasForce'] = false;
    }

    // Step 3 — the in-play list.
    if ($isUpgrade) {
        // Onto a unit: an upgrade has one host, and a unit attached as a pilot stops being a unit. p is its controller.
        if (SwuPgnIsArena($to) && is_string($e['attachedTo'] ?? null)) {
            $state = _SwuPgnDetach($state, $card);
            do {
                $state = _SwuPgnTakeCard($state, $card, $taken);
            } while ($taken !== null);
            return _SwuPgnNoteController(_SwuPgnAttach($state, $e['attachedTo'], $card, $w, $e), $e['attachedTo'], $card, $p);
        }
        $f = _SwuPgnFindCard($state, $card);
        if ($f !== null) $state['players'][$f[0]]['cards'][$f[1]]['zone'] = $to;
        return $state;
    }
    // Into an arena: a unit is nobody's upgrade — a pilot ejected arena to arena never passed step 0. Its owner
    // is the other seat when the pile it just left says so.
    if (SwuPgnIsArena($to)) {
        $state = _SwuPgnPlace(_SwuPgnDetach($state, $card), $p, $card, $to, $w, $e);
        $f = $ownedByOther ? _SwuPgnFindCard($state, $card) : null;
        if ($f !== null && $f[0] !== $other) {
            $c = $state['players'][$f[0]]['cards'][$f[1]];
            $c['owner'] = $other;
            $state['players'][$f[0]]['cards'][$f[1]] = _SwuPgnCardOrder($c);
        }
        return $state;
    }
    if (SwuPgnIsArena($from)) return _SwuPgnTakeCard($state, $card);
    $f = _SwuPgnFindCard($state, $card);
    if ($f !== null) $state['players'][$f[0]]['cards'][$f[1]]['zone'] = $to;
    return $state;
}

// §10.1 TAKE_CONTROL — a control change is not a zone change; this record re-seats the card itself.
function _SwuPgnReduceTakeControl(array $state, array $e, ?int $p, ?string $card, array &$w): array
{
    if (!_SwuPgnHasSeat($state, $p) || $card === null) return $state;
    $zone = $e['zone'] ?? null;
    $other = 3 - $p;

    if (SwuPgnIsArena($zone)) {
        // A unit entry in the other seat's cards: move it, with everything on it.
        $cards = $state['players'][$other]['cards'] ?? null;
        foreach (is_array($cards) ? $cards : [] as $i => $c) {
            if (!is_array($c) || ($c['id'] ?? null) !== $card) continue;
            if (count(_SwuPgnList($state['players'][$p]['cards'] ?? null)) >= SWUPGN_MAX_CARDS_PER_SEAT) {
                _SwuPgnWarn($w, $e, "in-play limit reached; $card not re-seated");
                return $state;
            }
            $ups = _SwuPgnList($c['upgrades'] ?? null);
            $load = count($ups) + count(_SwuPgnList($c['captured'] ?? null));
            if ($load > 0 && _SwuPgnSeatAttached($state, $p) + $load > SWUPGN_MAX_SEAT_ATTACHED) {   // §12
                _SwuPgnWarn($w, $e, "seat attachment limit reached; $card not re-seated");
                return $state;
            }
            // Owner: absent → the seat it came from; then none when that is p (it went home).
            $owner = _SwuPgnSeat($c['owner'] ?? null) ?? $other;
            if ($owner === $p) unset($c['owner']); else $c['owner'] = $owner;
            // Each upgrade keeps its own controller (its entry, else the seat the host came from), rebuilt against p.
            $old = _SwuPgnMap($c['upgradeControllers'] ?? null);
            $uc = [];
            foreach ($ups as $u) {
                if (!is_string($u) || _SwuPgnFoldReservedName($u)) continue;
                $ctl = _SwuPgnSeat($old[$u] ?? null) ?? $other;
                if ($ctl !== $p) $uc[$u] = $ctl;
            }
            if ($uc) $c['upgradeControllers'] = $uc; else unset($c['upgradeControllers']);
            unset($cards[$i]);
            $state['players'][$other]['cards'] = array_values($cards);
            $mine = _SwuPgnList($state['players'][$p]['cards'] ?? null);
            $mine[] = _SwuPgnCardOrder($c);
            $state['players'][$p]['cards'] = $mine;
            return $state;
        }
        // The upgrade form: nothing moves; the host notes p as the upgrade's controller.
        foreach ([1, 2] as $s) {
            foreach (_SwuPgnList($state['players'][$s]['cards'] ?? null) as $i => $c) {
                if (is_array($c) && is_array($c['upgrades'] ?? null) && in_array($card, $c['upgrades'], true)) {
                    return _SwuPgnSetController($state, $s, $i, $card, $p);
                }
            }
        }
        return $state;
    }

    $from = _SwuPgnSeat($e['from'] ?? null);
    if ($zone === 'resource' && $from === null) {
        // No counts move (a MOVE carried them): only who owns the card in p's row.
        if (_SwuPgnHasSeat($state, $other) && is_array($state['players'][$other]['foreignResources'] ?? null)) {
            $state['players'][$other]['foreignResources'] = _SwuPgnRemoveId($state['players'][$other]['foreignResources'], $card);
        }
        $owner = _SwuPgnSeat($e['owner'] ?? null);
        if ($owner !== null && $owner !== $p) {
            $state['players'][$p]['foreignResources'] = _SwuPgnAddOnce($state['players'][$p]['foreignResources'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
        } elseif ($owner === $p && is_array($state['players'][$p]['foreignResources'] ?? null)) {
            $state['players'][$p]['foreignResources'] = _SwuPgnRemoveId($state['players'][$p]['foreignResources'], $card);
        }
        return $state;
    }
    if ($from === null || $from === $p || !_SwuPgnHasSeat($state, $from)) return $state;

    if ($zone === 'resource') {
        $bucket = ($e['exhausted'] ?? null) === true ? 'resourcesExhausted' : 'resourcesReady';
        $state['players'][$from][$bucket] = max(0, _SwuPgnNum($state['players'][$from][$bucket] ?? 0) - 1);
        $state['players'][$p][$bucket] = _SwuPgnNum($state['players'][$p][$bucket] ?? 0) + 1;
        if (array_key_exists('resources', $state['players'][$from])) {
            $state['players'][$from]['resources'] = _SwuPgnRemoveId($state['players'][$from]['resources'], $card);
        }
        $state['players'][$p]['resources'] = _SwuPgnAddOnce($state['players'][$p]['resources'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
        // Ownership: a card in from's foreignResources is going home; any other is now foreign in p's row.
        $ff = $state['players'][$from]['foreignResources'] ?? null;
        if (is_array($ff) && in_array($card, $ff, true)) {
            $state['players'][$from]['foreignResources'] = _SwuPgnRemoveId($ff, $card);
        } else {
            $state['players'][$p]['foreignResources'] = _SwuPgnAddOnce($state['players'][$p]['foreignResources'] ?? null, $card, SWUPGN_MAX_LIST, $w, $e);
        }
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
