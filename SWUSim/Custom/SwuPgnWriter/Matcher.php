<?php
// Changes (Diff.php) + notes (Notes.php) → SWU-PGN events. Notes are walked in order; each claims the changes it
// explains and emits its events in the design's Section 5 order. Changes no note claimed are emitted as plain events
// (move → damage/heal → token → exhaust/ready → stats) and returned as announcement gaps. The diff always wins: a
// note that would change the board but claims nothing is dropped. Card refs come from the ledger in $ctx, which this
// keeps current; seq and `for` follow §9.1.
//
// Notes (SWUSim ids: UIDs as ints, CardIDs, base@N):
//   PLAY {p, cardID, how: unit|event|upgrade|pilot|smuggle, uid?, targetUid?, cost}  ATTACK {p, atkUid, def}
//   DAMAGE {src, tgt, amt, combat}  HEAL {src, tgt, amt}  DEFEAT {uid|cardID, reason, by?}  TOKEN {uid, kind, delta}
//   TRIGGER {cardID, p}  ABILITY {p, cardID|base@N, kind, uid?}  CHOICE {p, offered[], chose, prompt?}  DRAW {p, n}
//   DISCARD {p, cardIDs[]}  RESOURCE {p, cardID?}  SEARCH {p, cardIDs[]}  REVEAL {p, zone, cardIDs[]}  CAPTURE {p, cardID, by}
//   RESCUE {p, cardID}  CONTROL {p, uid}  DEPLOY {p, cardID, epic, pilotHostUid?}  FLIP {p, cardID, onStartingSide}
//   PASS {p}  CLAIM {p}  MULLIGAN {p}  KEEP {p}

const SWUPGN_TOKEN_CARDS = ['shield' => 'SOR_T02', 'experience' => 'SOR_T01', 'advantage' => 'ASH_T02', 'weakness' => 'HMW_T02'];
const SWUPGN_ACTION_NOTES = ['PLAY', 'ATTACK', 'DEPLOY', 'PASS', 'CLAIM'];

function SwuPgnMatch(array $changes, array $notes, array &$ctx): array {
    $run = ['ctx' => &$ctx, 'pool' => array_values($changes), 'claimed' => [], 'remaining' => [], 'out' => [],
        'notes' => array_values($notes), 'lastCopy' => [], 'carry' => [], 'gaps' => []];
    foreach ($run['notes'] as $ni => $note) _SwuPgnMNote($run, $ni, $note);
    $gaps = _SwuPgnMUnclaimed($run);
    $events = _SwuPgnMSequence($run);
    return ['events' => $events, 'gaps' => $gaps, 'carry' => $run['carry']];
}

// ── Pool and output ─────────────────────────────────────────────────────────────────────────────

function _SwuPgnMFind(array &$run, callable $pred): ?int {
    foreach ($run['pool'] as $i => $c) if (empty($run['claimed'][$i]) && $pred($c)) return $i;
    return null;
}
function _SwuPgnMTake(array &$run, callable $pred): ?array {
    $i = _SwuPgnMFind($run, $pred);
    if ($i === null) return null;
    $run['claimed'][$i] = true;
    return $run['pool'][$i];
}
function _SwuPgnMEmit(array &$run, string $t, array $fields, ?int $owner, bool $top = false): void {
    $run['out'][] = ['ev' => ['t' => $t] + array_filter($fields, fn($v) => $v !== null), 'top' => $top, 'owner' => $owner];
}

// ── Identity ────────────────────────────────────────────────────────────────────────────────────

function _SwuPgnLedgerLocate(array $ledger, string $copy): ?array {
    foreach ($ledger['zones'] as $seat => $zones) foreach ($zones as $zone => $list) if (in_array($copy, $list, true)) return [intval($seat), $zone];
    return null;
}

// A card ref for something a note names: a UID's bound copy, base@N / rule:… as-is, a CardID's most recent copy.
// A CardID named with the seat (and zone) it is in resolves to THAT seat's copy: the same card can be in both decks,
// and naming the other seat's copy would misattribute it — and, in a player's own view, reveal what the other holds.
function _SwuPgnMRef(array &$run, $x, ?int $seat = null, ?string $zone = null): string {
    if (is_int($x)) { _SwuPgnMEnsureUid($run, $x); return SwuPgnLedgerByUid($run['ctx']['ledger'], $x) ?? 'unknown'; }
    $x = (string)$x;
    if (!preg_match('/^[A-Z0-9]{2,5}_T?\d+$/', $x)) return $x;
    $ref = SwuPgnCardRef($x);
    if ($seat !== null) {
        $mine = [];
        foreach ($run['ctx']['ledger']['zones'][$seat] ?? [] as $z => $list) foreach ($list as $c) if (SwuPgnBaseId($c) === $ref) $mine[] = [$z, $c];
        foreach ($mine as [$z, $c]) if ($zone !== null && $z === $zone) return $c;
        if (isset($run['lastCopy'][$x]) && in_array($run['lastCopy'][$x], array_column($mine, 1), true)) return $run['lastCopy'][$x];
        if ($mine) return $mine[0][1];
    }
    if (isset($run['lastCopy'][$x])) return $run['lastCopy'][$x];
    foreach ($run['ctx']['ledger']['zones'] as $zones) foreach ($zones as $list) foreach ($list as $c) if (SwuPgnBaseId($c) === $ref) return $c;
    return $ref;
}

// MOVE.kind from the card's type (a role, not an identity — an attaching move is always "upgrade").
function _SwuPgnMKind(string $cardID): ?string {
    $type = (string)(CardType($cardID) ?? '');
    if (str_contains($type, 'Upgrade')) return 'upgrade';
    if (str_contains($type, 'Unit') || str_contains($type, 'Leader')) return 'unit';
    return null;
}

function _SwuPgnMIssue(array &$run, string $cardID, string $fallbackName): string {
    $ref = (string)(CardTitle($cardID) ?? '') === '' ? 'TOKEN:' . $fallbackName : SwuPgnCardRef($cardID);
    return _SwuPgnLedgerIssue($run['ctx']['ledger'], $ref);
}

function _SwuPgnMZoneKey(array $c, string $zone): string {
    if (isset($c['host']) && in_array($zone, SWUPGN_ARENAS, true)) return 'attached:' . $c['host'];
    if ($zone === 'capture' && isset($c['captor'])) return 'capture:' . $c['captor'];
    return $zone;
}

// Moves the change's copy through the ledger: [copy id, the seat it was found in].
function _SwuPgnMLedgerMove(array &$run, array $c, string $from, string $to, ?int $toSeat = null): array {
    $L = &$run['ctx']['ledger'];
    $cardID = $c['cardID'];
    $copy = null; $seat = intval($c['seat']);
    if ($from === 'outsideTheGame') {
        $copy = SwuPgnLedgerNewToken($L, $cardID);
    } else {
        $uid = isset($c['host']) && empty($c['attachFromPlay']) ? null : ($c['uid'] ?? null);   // a unit made a pilot left as a unit
        $bound = $uid === null || $from === 'capture' ? null : SwuPgnLedgerByUid($L, intval($uid));
        $loc = $bound === null ? null : _SwuPgnLedgerLocate($L, $bound);
        if ($loc !== null) {
            $copy = SwuPgnLedgerTake($L, $loc[0], $loc[1], $cardID, intval($uid));
            $seat = $loc[0];
        } elseif (($li = _SwuPgnMLimboFind($L, $cardID, $from)) !== null) {   // a held card the engine shows leaving its zone
            ['copy' => $copy, 'seat' => $seat] = $L['limbo'][$li];
            array_splice($L['limbo'], $li, 1);
        } else {
            $fromC = isset($c['fromHost']) ? ['host' => $c['fromHost']] + $c : $c;   // a re-attach leaves its OLD host
            if (!empty($c['attachFromPlay'])) unset($fromC['host']);
            $key = _SwuPgnMZoneKey($fromC, $from);
            foreach ([$seat, 3 - $seat] as $s) {
                $copy = SwuPgnLedgerTake($L, $s, $key, $cardID);
                if ($copy !== null) { $seat = $s; break; }
            }
        }
        $copy ??= SwuPgnLedgerNewToken($L, $cardID);   // a card the ledger never saw (should not happen)
        if (in_array($from, SWUPGN_ARENAS, true) && $uid !== null) {   // its tokens go with it; a real upgrade's own move takes it
            foreach (array_keys($L['zones']) as $s) {
                $left = array_values(array_filter($L['zones'][$s]['attached:' . $uid] ?? [], fn($x) => !str_starts_with($x, 'TOKEN:')));
                if ($left) $L['zones'][$s]['attached:' . $uid] = $left; else unset($L['zones'][$s]['attached:' . $uid]);
            }
        }
    }
    if ($to !== 'outsideTheGame') {
        $toC = $c;
        if (!empty($c['detach'])) unset($toC['host']);   // a pilot ejected to a unit of its own
        $intoPlay = in_array($to, SWUPGN_ARENAS, true) && !isset($toC['host']);
        SwuPgnLedgerPut($L, $toSeat ?? intval($c['seat']), _SwuPgnMZoneKey($toC, $to), $copy, $intoPlay && isset($c['uid']) ? intval($c['uid']) : null);
    }
    $run['lastCopy'][$cardID] = $copy;
    return [$copy, $seat];
}

// ── Emitters ────────────────────────────────────────────────────────────────────────────────────

// Staging (zone "temp", the TempZone a pick lays cards out in) is not a SWU-PGN zone: a card going into it, or back
// from it to the deck, moved nowhere in the file; a staged COPY (an upgrade shown as an image) has no partner at all.
function _SwuPgnMIsStaging(array $c): bool {
    return $c['k'] === 'move' && ($c['to'] === 'temp' || ($c['from'] === 'temp' && in_array($c['to'], ['deck', 'outsideTheGame'], true)));
}
function _SwuPgnMStage(array &$run, array $c): void {
    $L = &$run['ctx']['ledger'];
    if ($c['from'] === 'outsideTheGame') return;                       // a staged copy
    if ($c['from'] === 'temp' && $c['to'] === 'temp') return;          // handed between the seats' staging: still its owner's
    if ($c['to'] === 'outsideTheGame') {   // a real card held out of staging until it is played (limbo); a staged copy just goes
        $copy = SwuPgnLedgerTake($L, intval($c['seat']), 'temp', $c['cardID']);
        if ($copy === null) return;
        $from = $L['tempOrigin'][$copy] ?? 'deck';
        unset($L['tempOrigin'][$copy]);
        $L['limbo'][] = ['copy' => $copy, 'cardID' => $c['cardID'], 'seat' => intval($c['seat']), 'from' => $from, 'key' => $from, 'host' => null, 'deploy' => false];
        return;
    }
    [$copy] = _SwuPgnMLedgerMove($run, $c, $c['from'], $c['to']);
    if ($c['to'] === 'temp') $L['tempOrigin'][$copy] = $c['from'];
    else unset($L['tempOrigin'][$copy]);
}

// MOVE for a move change (attachedTo only on a move into an arena; exhausted only on a move out of resource), with the
// RESOURCE every move into a resource row is followed by (§10.1). A card that changes seats gets a TAKE_CONTROL, since
// one MOVE carries one seat: before the MOVE when it leaves a resource row (the row's counts move), after it when it
// enters another seat's arena or row.
function _SwuPgnMMove(array &$run, array $c, ?int $owner): string {
    $toSeat = isset($c['toSeat']) ? intval($c['toSeat']) : intval($c['seat']);
    $fromSeat = isset($c['fromSeat']) ? intval($c['fromSeat']) : intval($c['seat']);
    $cross = $toSeat !== $fromSeat;
    [$copy] = _SwuPgnMLedgerMove($run, $c, $c['from'], $c['to'], $toSeat);
    if ($c['from'] === 'temp') {   // out of staging: it left the zone it was staged from
        $c['from'] = $run['ctx']['ledger']['tempOrigin'][$copy] ?? 'deck';
        unset($run['ctx']['ledger']['tempOrigin'][$copy]);
    }
    if (!empty($c['detach']) || !empty($c['attachFromPlay'])) {   // a role change: upgrade → unit (Eject), unit → upgrade (L3-37)
        $toUnit = !empty($c['detach']);
        $arrive = ['kind' => $toUnit ? 'unit' : 'upgrade', 'attachedTo' => $toUnit ? null : _SwuPgnMRef($run, intval($c['host']))];
        // Out and back in: the fold detaches (or takes a unit out of play) only on leaving the arenas (§12.1).
        _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => $c['from'], 'to' => 'outsideTheGame', 'p' => intval($c['seat']), 'kind' => $toUnit ? 'upgrade' : 'unit'], $owner);
        _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => 'outsideTheGame', 'to' => $c['to'], 'p' => intval($c['seat'])] + $arrive, $owner);
        return $copy;
    }
    if (isset($c['fromHost'])) {   // moved to another host: off the old one, onto the new (a MOVE's from ≠ to)
        _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => $c['from'], 'to' => 'outsideTheGame', 'p' => intval($c['seat']), 'kind' => 'upgrade'], $owner);
        _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => 'outsideTheGame', 'to' => $c['to'], 'p' => intval($c['seat']), 'kind' => 'upgrade',
            'attachedTo' => _SwuPgnMRef($run, intval($c['host']))], $owner);
        return $copy;
    }
    $exhaustedOut = $c['from'] === 'resource' && !empty($c['fromExhausted']) ? true : null;
    $leavesRow = $cross && $c['from'] === 'resource';
    if ($leavesRow) _SwuPgnMEmit($run, 'TAKE_CONTROL', ['p' => $toSeat, 'card' => $copy, 'zone' => 'resource', 'from' => $fromSeat, 'exhausted' => $exhaustedOut], $owner);
    if ($leavesRow && $c['to'] === 'resource') return $copy;   // stolen into the thief's row: a control change, no MOVE (§10)
    $p = $leavesRow ? $toSeat : ($cross && !in_array($c['to'], SWUPGN_ARENAS, true) && $c['to'] !== 'resource' ? $toSeat : $fromSeat);
    if (!$cross) $p = intval($c['seat']);
    $attaching = isset($c['host']) && in_array($c['to'], SWUPGN_ARENAS, true);
    _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => $c['from'], 'to' => $c['to'], 'p' => $p,
        'kind' => $attaching ? 'upgrade' : _SwuPgnMKind($c['cardID']),
        'attachedTo' => $attaching ? _SwuPgnMRef($run, intval($c['host'])) : null, 'exhausted' => $exhaustedOut], $owner);
    if ($c['to'] === 'resource') _SwuPgnMEmit($run, 'RESOURCE', ['p' => $p, 'card' => $copy], $owner);
    if ($cross && !$leavesRow && !isset($c['host']) && (in_array($c['to'], SWUPGN_ARENAS, true) || $c['to'] === 'resource')) {
        _SwuPgnMEmit($run, 'TAKE_CONTROL', ['p' => $toSeat, 'card' => $copy, 'zone' => $c['to'],
            'from' => $c['to'] === 'resource' ? $fromSeat : null], $owner);
    }
    return $copy;
}

// A unit the ledger has not placed yet — it entered play this frame and its move is still unclaimed — is written now,
// so the note naming it can say which card it is (a Shield on a token created by the same effect).
function _SwuPgnMEnsureUid(array &$run, int $uid): void {
    if ($uid <= 0 || SwuPgnLedgerByUid($run['ctx']['ledger'], $uid) !== null) return;
    $i = _SwuPgnMFind($run, fn($c) => $c['k'] === 'move' && ($c['uid'] ?? null) === $uid && (!isset($c['host']) || !empty($c['detach'])) && in_array($c['to'], SWUPGN_ARENAS, true));
    if ($i === null) return;
    $run['claimed'][$i] = true;
    if (_SwuPgnMPlain($run, $run['pool'][$i], $run['ctx']['phase'] ?? 'A')) $run['gaps'][] = $run['pool'][$i];
}

// What follows an arrival: an exhausted entry into play (or home) is an EXHAUST; into the row, EXHAUST_RESOURCES — in the
// row it arrived in. A resource stolen row to row kept its bucket (the TAKE_CONTROL moved it): only a change of state is
// written.
function _SwuPgnMArrival(array &$run, array $c, string $copy, ?int $owner): void {
    $seat = intval($c['toSeat'] ?? $c['seat']);
    if ($c['to'] === 'resource' && $c['from'] === 'resource') {
        $left = !empty($c['fromExhausted']); $arrived = !empty($c['exhausted']);
        if ($left !== $arrived) _SwuPgnMEmit($run, $arrived ? 'EXHAUST_RESOURCES' : 'READY_RESOURCES', ['p' => $seat, 'amount' => 1], $owner);
        return;
    }
    if (empty($c['exhausted']) || (isset($c['host']) && empty($c['detach']) && in_array($c['to'], SWUPGN_ARENAS, true))) return;
    if (in_array($c['to'], ['ground', 'space', 'base'], true)) _SwuPgnMEmit($run, 'EXHAUST', ['card' => $copy], $owner);
    elseif ($c['to'] === 'resource') _SwuPgnMEmit($run, 'EXHAUST_RESOURCES', ['p' => $seat, 'amount' => 1], $owner);
}

function _SwuPgnMStats(array &$run, $uid, ?int $owner): void {
    if ($uid === null) return;
    $s = _SwuPgnMTake($run, fn($c) => $c['k'] === 'stats' && $c['uid'] === $uid);
    if ($s !== null) _SwuPgnMEmit($run, 'STATS', ['card' => _SwuPgnMRef($run, intval($uid)), 'power' => $s['power'], 'hp' => $s['hp'], 'keywords' => $s['keywords']], $owner);
}

function _SwuPgnMTokenEvents(array &$run, array $c, ?int $owner): void {
    _SwuPgnMEnsureUid($run, intval($c['uid']));
    $L = &$run['ctx']['ledger'];
    $host = SwuPgnLedgerByUid($L, intval($c['uid'])) ?? 'unknown';
    [$seat, $zone] = _SwuPgnLedgerLocate($L, $host) ?? [intval($c['seat']), 'ground'];
    $kind = $c['kind']; $tokenID = SWUPGN_TOKEN_CARDS[$kind] ?? '';
    $key = 'attached:' . $c['uid'];
    $record = fn(int $n) => match ($kind) {
        'shield' => [$n > 0 ? 'SHIELD_GAIN' : 'SHIELD_USE', ['card' => $host]],
        'experience' => ['EXPERIENCE_GAIN', ['card' => $host, 'count' => $n]],
        default => ['STATUS_TOKEN', ['card' => $host, 'token' => $kind, 'count' => $n]],
    };
    for ($i = 0; $i < abs($c['delta']); $i++) {
        if ($c['delta'] > 0) {
            $copy = _SwuPgnMIssue($run, $tokenID, $kind);
            SwuPgnLedgerPut($L, $seat, $key, $copy);
            _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => 'outsideTheGame', 'to' => $zone, 'p' => $seat, 'kind' => 'upgrade', 'attachedTo' => $host], $owner);
            [$t, $f] = $record(1); _SwuPgnMEmit($run, $t, $f, $owner);
        } else {
            $copy = null;
            $list = $L['zones'][$seat][$key] ?? [];
            foreach ($list as $j => $x) {
                if (str_starts_with($x, 'TOKEN:' . $kind)) { $copy = $x; array_splice($list, $j, 1); break; }
            }
            $L['zones'][$seat][$key] = $list;
            $copy ??= _SwuPgnMIssue($run, $tokenID, $kind);
            [$t, $f] = $record(-1); _SwuPgnMEmit($run, $t, $f, $owner);
            _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => $zone, 'to' => 'outsideTheGame', 'p' => $seat, 'kind' => 'upgrade'], $owner);
        }
    }
}

// DAMAGE / HEAL against a change, sharing its amount when several notes hit one target; hp is the running total.
function _SwuPgnMHarm(array &$run, int $ni, array $n, string $kind): void {
    $tgt = $n['tgt'];
    $i = _SwuPgnMFind($run, fn($c) => $c['k'] === $kind && $c['target'] === $tgt);
    $amt = intval($n['amt']);
    if ($i === null) {
        // Lethal damage: the unit left play this frame, so there is no damage change to hold it.
        $died = false;
        foreach ($run['pool'] as $c) {
            if (in_array($c['k'], ['move', 'capture'], true) && ($c['uid'] ?? null) === $tgt && !isset($c['host'])) $died = true;
        }
        if ($kind !== 'damage' || !is_int($tgt) || !$died) return;
        $hp = 0;
    } else {
        $c = $run['pool'][$i];
        $left = ($run['remaining'][$i] ?? $c['amount']) - $amt;
        if ($left < 0) { $amt += $left; $left = 0; }
        $run['remaining'][$i] = $left;
        if ($left === 0) $run['claimed'][$i] = true;
        $hp = $kind === 'damage' ? $c['hp'] + $left : $c['hp'] - $left;
    }
    $fields = ['src' => _SwuPgnMRef($run, $n['src'] ?? 'unknown'), 'tgt' => _SwuPgnMRef($run, $tgt), 'amt' => $amt];
    if ($kind === 'damage') $fields['damageType'] = !empty($n['combat']) ? 'combat' : 'ability';
    $fields['hp'] = $hp;
    _SwuPgnMEmit($run, $kind === 'damage' ? 'DAMAGE' : 'HEAL', $fields, $ni);
}

// ── Note handlers ───────────────────────────────────────────────────────────────────────────────

function _SwuPgnMNote(array &$run, int $ni, array $n): void {
    $p = intval($n['p'] ?? 0);
    switch ($n['t'] ?? '') {
        case 'PLAY': {
            $how = $n['how'] ?? 'unit';
            $mi = _SwuPgnMFind($run, fn($c) => $c['k'] === 'move' && $c['cardID'] === $n['cardID'] && $c['seat'] === $p
                && in_array($c['from'], ['hand', 'resource', 'discard', 'deck'], true)
                && (!isset($n['uid']) || ($c['uid'] ?? null) === $n['uid'] || isset($c['host'])));
            $pay = function () use (&$run, $p, $ni) {
                $ri = _SwuPgnMFind($run, fn($c) => $c['k'] === 'resources' && $c['seat'] === $p && $c['exhaustedDelta'] > 0);
                if ($ri !== null) { $run['claimed'][$ri] = true; _SwuPgnMEmit($run, 'EXHAUST_RESOURCES', ['p' => $p, 'amount' => $run['pool'][$ri]['exhaustedDelta']], $ni); }
            };
            // An event played from the discard pile goes back to it — no zone change the diff can see. It leaves the pile
            // while it is played, and the PLAY_EVENT files it on top again.
            if ($mi === null && $how === 'event' && ($n['from'] ?? null) === 'discard') {
                $L = &$run['ctx']['ledger'];
                $copy = SwuPgnLedgerTake($L, $p, 'discard', $n['cardID']);
                if ($copy !== null) {
                    $pay();
                    _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => 'discard', 'to' => 'outsideTheGame', 'p' => $p], $ni);
                    SwuPgnLedgerPut($L, $p, 'discard', $copy);
                    _SwuPgnMEmit($run, 'PLAY_EVENT', ['p' => $p, 'card' => $copy, 'zone' => 'discard', 'cost' => $n['cost'] ?? null], $ni, true);
                    return;
                }
                unset($L);
            }
            // The card has not moved yet: its host or target is picked in a later request (an upgrade's attach).
            // Carry the note to the next frame, where its move — and usually its payment — lands.
            if ($mi === null) { $run['carry'][] = $n; return; }
            $pay();
            $mv = $run['pool'][$mi]; $run['claimed'][$mi] = true;
            // A played event is filed to discard (§12 PLAY_EVENT); one that ends elsewhere (Resupply) goes on from there.
            $onward = $how === 'event' && $mv['to'] !== 'discard';
            $copy = _SwuPgnMMove($run, $onward ? ['to' => 'discard', 'exhausted' => false, 'toSeat' => $mv['fromSeat'] ?? $mv['seat']] + $mv : $mv, $ni);
            $copy ??= _SwuPgnMRef($run, $n['cardID']);
            $zone = $mv['to'] ?? null;
            if (in_array($how, ['upgrade', 'pilot'], true)) {
                $hostUid = $n['targetUid'] ?? ($mv['host'] ?? null);
                _SwuPgnMEmit($run, 'PLAY_UPGRADE', ['p' => $p, 'card' => $copy, 'zone' => $zone,
                    'target' => $hostUid === null ? null : _SwuPgnMRef($run, intval($hostUid)), 'cost' => $n['cost'] ?? null], $ni, true);
                _SwuPgnMStats($run, $hostUid === null ? null : intval($hostUid), $ni);
            } elseif ($how === 'event') {
                _SwuPgnMEmit($run, 'PLAY_EVENT', ['p' => $p, 'card' => $copy, 'zone' => 'discard', 'cost' => $n['cost'] ?? null], $ni, true);
                if ($onward) {
                    $copy = _SwuPgnMMove($run, ['from' => 'discard', 'fromSeat' => $mv['toSeat'] ?? $mv['seat']] + $mv, $ni);
                    _SwuPgnMArrival($run, $mv, $copy, $ni);   // an event that puts itself into the row exhausted
                }
            } else {
                _SwuPgnMStats($run, $mv['uid'] ?? $n['uid'] ?? null, $ni);   // STATS sits with the MOVE, before the PLAY
                _SwuPgnMEmit($run, $how === 'smuggle' ? 'PLAY_SMUGGLE' : 'PLAY', ['p' => $p, 'card' => $copy, 'zone' => $zone ?? 'ground', 'cost' => $n['cost'] ?? null], $ni, true);
                if ($mv !== null) _SwuPgnMArrival($run, $mv, $copy, $ni);
            }
            return;
        }
        case 'ATTACK': {
            $atk = intval($n['atkUid']);
            $ex = _SwuPgnMTake($run, fn($c) => $c['k'] === 'exhaust' && $c['target'] === $atk);
            if ($ex !== null) _SwuPgnMEmit($run, 'EXHAUST', ['card' => _SwuPgnMRef($run, $atk)], $ni);
            $def = $n['def'];
            _SwuPgnMEmit($run, 'ATTACK', ['p' => $p, 'atk' => _SwuPgnMRef($run, $atk), 'def' => _SwuPgnMRef($run, $def),
                'defenderType' => is_string($def) && str_starts_with($def, 'base@') ? 'base' : 'unit'], $ni, true);
            return;
        }
        case 'DAMAGE': _SwuPgnMHarm($run, $ni, $n, 'damage'); return;
        case 'HEAL':   _SwuPgnMHarm($run, $ni, $n, 'heal'); return;
        case 'DEFEAT': {
            $uid = $n['uid'] ?? null;
            // Out of play — or, for a base upgrade (no uid), out of the base area. A defeat that was replaced (Roger Roger
            // moving to a Battle Droid instead) stayed in play: no DEFEAT.
            $mv = _SwuPgnMTake($run, fn($c) => $c['k'] === 'move' && (in_array($c['from'], SWUPGN_ARENAS, true) || ($uid === null && $c['from'] === 'base'))
                && !in_array($c['to'], SWUPGN_ARENAS, true)
                && ($uid !== null ? ($c['uid'] ?? null) === $uid && !isset($c['host']) : $c['cardID'] === ($n['cardID'] ?? null)));
            if ($mv === null) return;
            $by = isset($n['by']) ? _SwuPgnMRef($run, $n['by']) : null;   // before the move: the killer may die too
            // Its upgrades leave first (the engine discards them first): defeated by the framework — their host left play.
            if ($uid !== null) {
                while (($up = _SwuPgnMTake($run, fn($c) => $c['k'] === 'move' && ($c['host'] ?? null) === $uid && in_array($c['from'], SWUPGN_ARENAS, true))) !== null) {
                    $upCopy = _SwuPgnMMove($run, $up, $ni);
                    _SwuPgnMArrival($run, $up, $upCopy, $ni);   // a Pilot leader comes home exhausted
                    if ($up['to'] === 'discard') _SwuPgnMEmit($run, 'DEFEAT', ['card' => $upCopy, 'reason' => 'frameworkEffect'], $ni);
                }
            }
            $copy = _SwuPgnMMove($run, $mv, $ni);
            _SwuPgnMArrival($run, $mv, $copy, $ni);
            _SwuPgnMEmit($run, 'DEFEAT', ['card' => $copy, 'reason' => $n['reason'] ?? 'unknown', 'defeatedBy' => $by], $ni);
            return;
        }
        case 'TOKEN': {
            $want = intval($n['delta']);
            $i = _SwuPgnMFind($run, fn($c) => $c['k'] === 'token' && $c['uid'] === $n['uid'] && $c['kind'] === $n['kind'] && ($c['delta'] > 0) === ($want > 0));
            if ($i === null) return;
            $c = $run['pool'][$i];
            $have = $run['remaining'][$i] ?? $c['delta'];
            $use = $want > 0 ? min($want, $have) : max($want, $have);
            $run['remaining'][$i] = $have - $use;
            if ($run['remaining'][$i] === 0) $run['claimed'][$i] = true;
            _SwuPgnMTokenEvents($run, array_replace($c, ['delta' => $use]), $ni);
            return;
        }
        case 'TRIGGER':
            _SwuPgnMEmit($run, 'TRIGGER', ['card' => _SwuPgnMRef($run, $n['cardID'], $p ?: null), 'p' => $p ?: null], $ni);
            return;
        case 'ABILITY': {
            $card = $n['cardID'];
            $kind = $n['kind'] ?? 'triggered';
            $ref = _SwuPgnMRef($run, $card, $p, 'base');   // its own leader (or a unit of its own)
            if (is_string($card) && str_starts_with($card, 'base@') && $kind === 'epic') {
                // Epic only if it spent the base's Epic Action: a limited-use base Action (Mystic Monastery) does not.
                if (_SwuPgnMTake($run, fn($c) => $c['k'] === 'baseEpic' && "base@{$c['seat']}" === $card) === null) $kind = 'action';
            }
            $ex = _SwuPgnMTake($run, fn($c) => $c['k'] === 'exhaust' && (($c['target'] === "leader@$p" && $c['cardID'] === $card)
                || (isset($n['uid']) && $c['target'] === $n['uid'])));
            if ($ex !== null) _SwuPgnMEmit($run, 'EXHAUST', ['card' => is_int($ex['target']) ? _SwuPgnMRef($run, $ex['target']) : $ref], $ni);
            _SwuPgnMEmit($run, 'ABILITY_ACTIVATE', ['p' => $p, 'card' => $ref, 'kind' => $kind, 'epic' => $kind === 'epic' ? true : null], $ni,
                in_array($kind, ['action', 'epic'], true));
            return;
        }
        case 'CHOICE':
            _SwuPgnMEmit($run, 'CHOICE', ['p' => $p, 'prompt' => $n['prompt'] ?? null,
                'offered' => array_map(fn($x, $i) => _SwuPgnMRef($run, $x, $n['offeredSeats'][$i] ?? $p, $n['offeredZones'][$i] ?? null),
                    $n['offered'] ?? [], array_keys($n['offered'] ?? [])), 'chose' => intval($n['chose'] ?? 0)], $ni);
            return;
        case 'DRAW': {
            $cards = [];
            for ($k = 0; $k < intval($n['n'] ?? 0); $k++) {
                $mv = _SwuPgnMTake($run, fn($c) => $c['k'] === 'move' && $c['seat'] === $p && $c['from'] === 'deck' && $c['to'] === 'hand');
                if ($mv === null) break;
                $cards[] = _SwuPgnMMove($run, $mv, $ni);
            }
            if ($cards) _SwuPgnMEmit($run, 'DRAW', ['p' => $p, 'count' => count($cards), 'cards' => $cards], $ni);
            return;
        }
        case 'DISCARD': {
            $cards = [];
            foreach ($n['cardIDs'] ?? [] as $id) {
                $mv = _SwuPgnMTake($run, fn($c) => $c['k'] === 'move' && $c['seat'] === $p && $c['cardID'] === $id && $c['to'] === 'discard'
                    && in_array($c['from'], ['hand', 'deck'], true));
                if ($mv !== null) $cards[] = _SwuPgnMMove($run, $mv, $ni);
            }
            if ($cards) _SwuPgnMEmit($run, 'DISCARD', ['p' => $p, 'cards' => $cards], $ni);
            return;
        }
        case 'RESOURCE': {
            // A note without a cardID (the log line names no card: "resourced the top card of their deck") takes
            // that seat's next move into the row.
            $mv = _SwuPgnMTake($run, fn($c) => $c['k'] === 'move' && $c['seat'] === $p && $c['to'] === 'resource'
                && (($n['cardID'] ?? null) === null || $c['cardID'] === $n['cardID']));
            if ($mv === null) return;
            $copy = _SwuPgnMMove($run, $mv, $ni);   // the MOVE and its RESOURCE
            _SwuPgnMArrival($run, $mv, $copy, $ni);
            return;
        }
        case 'SEARCH':
            // §10.2: SEARCH, then a MOVE for only the cards that left the deck.
            _SwuPgnMEmit($run, 'SEARCH', ['p' => $p], $ni);
            foreach ($n['cardIDs'] ?? [] as $id) {
                $mv = _SwuPgnMTake($run, fn($c) => $c['k'] === 'move' && $c['seat'] === $p && $c['cardID'] === $id && in_array($c['from'], ['deck', 'temp'], true));
                if ($mv !== null) _SwuPgnMMove($run, $mv, $ni);
            }
            return;
        case 'REVEAL':
            _SwuPgnMEmit($run, 'REVEAL', ['p' => $p, 'zone' => $n['zone'] ?? 'hand', 'cards' => array_map(fn($x) => _SwuPgnMRef($run, $x), $n['cardIDs'] ?? [])], $ni);
            return;
        case 'CAPTURE': {
            $cap = _SwuPgnMTake($run, fn($c) => $c['k'] === 'capture' && $c['cardID'] === $n['cardID'] && (!isset($n['by']) || $c['captor'] === $n['by']));
            if ($cap === null) return;
            $by = is_int($cap['captor']) ? _SwuPgnMRef($run, $cap['captor']) : $cap['captor'];
            [$copy, $fromSeat] = _SwuPgnMLedgerMove($run, $cap, $cap['from'], 'capture', intval($cap['seat']));
            _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => $cap['from'], 'to' => 'capture', 'p' => $fromSeat, 'kind' => _SwuPgnMKind($cap['cardID'])], $ni);
            _SwuPgnMEmit($run, 'CAPTURE', ['p' => $p, 'card' => $copy, 'by' => $by], $ni);
            return;
        }
        case 'RESCUE': {
            $rs = _SwuPgnMTake($run, fn($c) => $c['k'] === 'rescue' && $c['cardID'] === $n['cardID'] && $c['seat'] === $p);
            if ($rs === null) return;
            [$copy] = _SwuPgnMLedgerMove($run, $rs, 'capture', $rs['to']);
            _SwuPgnMEmit($run, 'RESCUE', ['p' => $p, 'card' => $copy], $ni);
            _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => 'capture', 'to' => $rs['to'], 'p' => $p, 'kind' => _SwuPgnMKind($rs['cardID'])], $ni);
            _SwuPgnMStats($run, $rs['uid'] ?? null, $ni);
            _SwuPgnMArrival($run, $rs, $copy, $ni);
            return;
        }
        case 'CONTROL': {
            $ct = _SwuPgnMTake($run, fn($c) => $c['k'] === 'control' && $c['uid'] === $n['uid']);
            if ($ct === null) return;
            _SwuPgnMControl($run, $ct, $ni);
            return;
        }
        case 'DEPLOY': {
            $mv = _SwuPgnMTake($run, fn($c) => $c['k'] === 'move' && $c['seat'] === $p && $c['cardID'] === $n['cardID'] && $c['from'] === 'base');
            if ($mv === null) { $run['carry'][] = $n; return; }   // a pilot deploy picks its vehicle first
            $epic = !empty($n['epic']);   // the deploy spent the Epic Action when the leader's flag flipped with it
            while (($lc = _SwuPgnMTake($run, fn($c) => $c['k'] === 'leader' && $c['seat'] === $p)) !== null) {
                if ($lc['field'] === 'epicUsed' && $lc['value'] === true) $epic = true;
            }
            $copy = _SwuPgnMMove($run, $mv, $ni);
            $pilot = isset($mv['host']);
            if (!$pilot) _SwuPgnMStats($run, $mv['uid'] ?? null, $ni);
            _SwuPgnMEmit($run, 'DEPLOY_LEADER', ['p' => $p, 'card' => $copy, 'zone' => $mv['to'], 'epic' => $epic ? true : null,
                'kind' => $pilot ? 'upgrade' : null, 'target' => $pilot ? _SwuPgnMRef($run, intval($mv['host'])) : null], $ni, true);
            if ($pilot) _SwuPgnMStats($run, intval($mv['host']), $ni);
            else _SwuPgnMArrival($run, $mv, $copy, $ni);   // deployed exhausted (Phee Genoa's tax unpaid)
            return;
        }
        case 'FLIP': {
            $fl = _SwuPgnMTake($run, fn($c) => $c['k'] === 'leader' && $c['seat'] === $p && $c['field'] === 'onStartingSide');
            if ($fl === null) return;
            _SwuPgnMEmit($run, 'LEADER_FLIP', ['p' => $p, 'card' => _SwuPgnMRef($run, $n['cardID']), 'onStartingSide' => $fl['value']], $ni);
            return;
        }
        case 'PASS': _SwuPgnMEmit($run, 'PASS', ['p' => $p], $ni, true); return;
        case 'CLAIM':
            _SwuPgnMTake($run, fn($c) => $c['k'] === 'initiative' && $c['seat'] === $p);
            _SwuPgnMEmit($run, 'CLAIM_INITIATIVE', ['p' => $p], $ni, true);
            return;
        case 'MULLIGAN':
            _SwuPgnMEmit($run, 'MULLIGAN', ['p' => $p], $ni);
            while (($mv = _SwuPgnMTake($run, fn($c) => $c['k'] === 'move' && $c['seat'] === $p && $c['from'] === 'hand' && $c['to'] === 'deck')) !== null) {
                _SwuPgnMMove($run, $mv, $ni);
            }
            _SwuPgnMEmit($run, 'SHUFFLE', ['p' => $p], $ni);
            return;
        case 'KEEP': _SwuPgnMEmit($run, 'KEEP_HAND', ['p' => $p], $ni); return;
    }
}

function _SwuPgnMControl(array &$run, array $ct, ?int $owner): void {
    $L = &$run['ctx']['ledger'];
    $copy = SwuPgnLedgerByUid($L, intval($ct['uid']));
    $zone = 'ground';
    if ($copy !== null && ($loc = _SwuPgnLedgerLocate($L, $copy)) !== null) {
        $zone = $loc[1];
        SwuPgnLedgerTake($L, $loc[0], $zone, $ct['cardID'], intval($ct['uid']));
        SwuPgnLedgerPut($L, intval($ct['to']), $zone, $copy, intval($ct['uid']));
    }
    _SwuPgnMEmit($run, 'TAKE_CONTROL', ['p' => intval($ct['to']), 'card' => $copy ?? SwuPgnCardRef($ct['cardID']), 'zone' => $zone], $owner);
}

// ── Unclaimed changes ───────────────────────────────────────────────────────────────────────────

// Plain events for what no note claimed, in the fixed order; returns the announcement gaps.
function _SwuPgnMUnclaimed(array &$run): array {
    $gaps = $run['gaps'];   // any written early (_SwuPgnMEnsureUid)
    $phase = $run['ctx']['phase'] ?? 'A';
    $buckets = [
        ['move', 'capture', 'rescue', 'control', 'credit', 'force'], ['damage', 'heal'], ['token'],
        ['exhaust', 'ready', 'resources'], ['stats'], ['leader', 'baseEpic', 'initiative'],
    ];
    foreach ($buckets as $kinds) {
        foreach ($run['pool'] as $i => $c) {
            if (!empty($run['claimed'][$i]) || !in_array($c['k'], $kinds, true)) continue;
            $run['claimed'][$i] = true;
            if (_SwuPgnMPlain($run, $c, $phase)) $gaps[] = $c;
        }
    }
    return $gaps;
}

// Emits one unclaimed change; true when it counts as an announcement gap.
function _SwuPgnMPlain(array &$run, array $c, string $phase): bool {
    $seat = intval($c['seat']);
    switch ($c['k']) {
        case 'move': {
            if (_SwuPgnMIsStaging($c)) { _SwuPgnMStage($run, $c); return false; }
            if (_SwuPgnMLimboOut($run, $c)) return false;
            if ($c['from'] === 'outsideTheGame' && ($landed = _SwuPgnMLimboIn($run, $c)) !== null) return $landed;
            $tokenUnit = $c['from'] === 'outsideTheGame' && CardType($c['cardID']) === 'Token Unit';
            if ($tokenUnit) {
                $at = count($run['out']);
                $copy = _SwuPgnMMove($run, $c, null);
                array_splice($run['out'], $at, 0, [['ev' => ['t' => 'CREATE_TOKEN', 'p' => $seat, 'token' => $copy, 'zone' => $c['to'], 'kind' => 'unit'], 'top' => false, 'owner' => null]]);
            } else {
                $copy = _SwuPgnMMove($run, $c, null);
            }
            _SwuPgnMArrival($run, $c, $copy, null);
            return !$tokenUnit;
        }
        case 'capture': {
            [$copy, $fromSeat] = _SwuPgnMLedgerMove($run, $c, $c['from'], 'capture', $seat);
            _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => $c['from'], 'to' => 'capture', 'p' => $fromSeat, 'kind' => _SwuPgnMKind($c['cardID'])], null);
            _SwuPgnMEmit($run, 'CAPTURE', ['p' => $seat, 'card' => $copy, 'by' => is_int($c['captor']) ? _SwuPgnMRef($run, $c['captor']) : $c['captor']], null);
            return true;
        }
        case 'rescue': {
            [$copy] = _SwuPgnMLedgerMove($run, $c, 'capture', $c['to']);
            _SwuPgnMEmit($run, 'RESCUE', ['p' => $seat, 'card' => $copy], null);
            _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => 'capture', 'to' => $c['to'], 'p' => $seat, 'kind' => _SwuPgnMKind($c['cardID'])], null);
            _SwuPgnMArrival($run, $c, $copy, null);
            return true;
        }
        case 'control': _SwuPgnMControl($run, $c, null); return true;
        case 'credit':
        case 'force': {
            $cardID = $c['k'] === 'credit' ? 'LAW_T01' : 'LOF_T03';
            $n = $c['k'] === 'credit' ? intval($c['delta']) : ($c['has'] ? 1 : -1);
            $L = &$run['ctx']['ledger'];
            for ($i = 0; $i < abs($n); $i++) {
                if ($n > 0) {
                    $copy = _SwuPgnMIssue($run, $cardID, $c['k'] === 'credit' ? 'credit' : 'the-force');
                    SwuPgnLedgerPut($L, $seat, $c['k'], $copy);
                    _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => 'outsideTheGame', 'to' => 'base', 'p' => $seat], null);
                } else {
                    $copy = SwuPgnLedgerTake($L, $seat, $c['k'], $cardID) ?? _SwuPgnMIssue($run, $cardID, $c['k'] === 'credit' ? 'credit' : 'the-force');
                    _SwuPgnMEmit($run, 'MOVE', ['card' => $copy, 'from' => 'base', 'to' => 'outsideTheGame', 'p' => $seat], null);
                }
            }
            return false;
        }
        case 'damage':
        case 'heal':
            _SwuPgnMEmit($run, $c['k'] === 'damage' ? 'DAMAGE' : 'HEAL', ['src' => 'unknown', 'tgt' => _SwuPgnMRef($run, $c['target']), 'amt' => $c['amount'],
                'damageType' => $c['k'] === 'damage' ? 'ability' : null, 'hp' => $c['hp']], null);
            return true;
        case 'token': _SwuPgnMTokenEvents($run, $c, null); return true;
        case 'exhaust':
        case 'ready': {
            $card = is_int($c['target']) ? _SwuPgnMRef($run, $c['target']) : _SwuPgnMRef($run, $c['cardID'], $seat, 'base');   // leader@N
            _SwuPgnMEmit($run, $c['k'] === 'exhaust' ? 'EXHAUST' : 'READY', ['card' => $card], null);
            return $phase !== 'G';
        }
        case 'resources':
            _SwuPgnMEmit($run, $c['exhaustedDelta'] > 0 ? 'EXHAUST_RESOURCES' : 'READY_RESOURCES', ['p' => $seat, 'amount' => abs($c['exhaustedDelta'])], null);
            return $phase !== 'G';
        case 'stats':
            _SwuPgnMEmit($run, 'STATS', ['card' => _SwuPgnMRef($run, intval($c['uid'])), 'power' => $c['power'], 'hp' => $c['hp'], 'keywords' => $c['keywords']], null);
            return false;
        case 'leader':
            if ($c['field'] === 'onStartingSide') {
                _SwuPgnMEmit($run, 'LEADER_FLIP', ['p' => $seat, 'card' => _SwuPgnMRef($run, _SwuPgnMLeaderCard($run, $seat)), 'onStartingSide' => $c['value']], null);
                return true;
            }
            if ($c['field'] === 'deployed' && $c['value'] === true) {
                // Deployed while lifted off the base (its vehicle not yet picked): written when it lands.
                foreach ($run['ctx']['ledger']['limbo'] ?? [] as $i => $lb) {
                    if ($lb['seat'] === $seat && $lb['from'] === 'base') { $run['ctx']['ledger']['limbo'][$i]['deploy'] = true; return false; }
                }
                // Where it went: its own move off the base (a pilot deploy names its host).
                $lm = null;
                foreach ($run['pool'] as $x) if ($x['k'] === 'move' && $x['from'] === 'base' && $x['seat'] === $seat && $x['cardID'] === _SwuPgnMLeaderCard($run, $seat)) $lm = $x;
                $pilot = $lm !== null && isset($lm['host']);
                _SwuPgnMEmit($run, 'DEPLOY_LEADER', ['p' => $seat, 'card' => _SwuPgnMRef($run, _SwuPgnMLeaderCard($run, $seat)), 'zone' => $lm['to'] ?? null,
                    'kind' => $pilot ? 'upgrade' : null, 'target' => $pilot ? _SwuPgnMRef($run, intval($lm['host'])) : null], null, true);
                return true;
            }
            return false;
        case 'baseEpic':
            _SwuPgnMEmit($run, 'ABILITY_ACTIVATE', ['p' => $seat, 'card' => "base@$seat", 'kind' => 'epic', 'epic' => true], null, true);
            return true;
        case 'initiative':
            if (!$c['taken']) return false;
            _SwuPgnMEmit($run, 'CLAIM_INITIATIVE', ['p' => $seat], null, true);
            return true;
    }
    return true;
}

// Limbo: a printed card lifted out of sight while its next place is picked in a later request — off the base, a host,
// capture, staging or the deck (Poe Dameron deploying himself as a pilot or hopping Vehicles, a bounced host's pilot,
// Dryden Vos's captive, a held search pick) — shows as a move to outsideTheGame and, a request later, one back.
// Neither half is true on its own — it never left the game, and a fresh copy would come back — so nothing is written
// until it lands: then one move from where it really was (and, for a deploy, the DEPLOY_LEADER held until now).
function _SwuPgnMLimboOut(array &$run, array $c): bool {
    if ($c['to'] !== 'outsideTheGame' || !(in_array($c['from'], ['base', 'capture', 'deck'], true) || isset($c['host']))) return false;
    if (str_contains((string)(CardType($c['cardID']) ?? ''), 'Token') || (string)(CardTitle($c['cardID']) ?? '') === '') return false;
    $L = &$run['ctx']['ledger'];
    $key = _SwuPgnMZoneKey($c, $c['from']);
    foreach ([intval($c['seat']), 3 - intval($c['seat'])] as $s) {
        $copy = SwuPgnLedgerTake($L, $s, $key, $c['cardID']);
        if ($copy === null) continue;
        $L['limbo'][] = ['copy' => $copy, 'cardID' => $c['cardID'], 'seat' => $s, 'from' => $c['from'], 'key' => $key,
            'host' => $c['host'] ?? null, 'captor' => $c['captor'] ?? null, 'deploy' => false];
        return true;
    }
    return false;
}
function _SwuPgnMLimboFind(array $L, string $cardID, string $from): ?int {
    // Out of the zone it was held from — or, for a deck card, back out of the staging area it is returned through.
    foreach ($L['limbo'] ?? [] as $i => $lb) {
        if ($lb['cardID'] === $cardID && ($lb['from'] === $from || ($from === 'temp' && $lb['from'] === 'deck')) && $lb['host'] === null && $from !== 'base') return $i;
    }
    return null;
}
// null = not a held card; otherwise whether its landing is an announcement gap.
function _SwuPgnMLimboIn(array &$run, array $c): ?bool {
    $L = &$run['ctx']['ledger'];
    foreach ($L['limbo'] ?? [] as $i => $lb) {
        if ($lb['cardID'] !== $c['cardID']) continue;
        array_splice($L['limbo'], $i, 1);
        if ($lb['from'] === 'capture' && $c['to'] === 'capture') {   // a tucked captive back under its captor: no zone change
            SwuPgnLedgerPut($L, intval($c['seat']), _SwuPgnMZoneKey($c, 'capture'), $lb['copy']);
            _SwuPgnMEmit($run, 'CAPTURE', ['p' => intval($c['seat']), 'card' => $lb['copy'],
                'by' => is_int($c['captor'] ?? null) ? _SwuPgnMRef($run, $c['captor']) : ($c['captor'] ?? null)], null);
            return true;
        }
        SwuPgnLedgerPut($L, $lb['seat'], $lb['key'], $lb['copy']);   // back where it was, then the one real move
        if ($lb['from'] === $c['to'] && $lb['host'] === null && !isset($c['host']) && $lb['from'] !== 'capture') return false;   // put straight back (a reveal)
        $mv = ['from' => $lb['from'], 'seat' => $lb['seat']] + (($lb['captor'] ?? null) !== null ? ['captor' => $lb['captor']] : []) + $c;
        if ($lb['host'] !== null && isset($c['host'])) $mv['fromHost'] = $lb['host'];
        elseif ($lb['host'] !== null && in_array($c['to'], SWUPGN_ARENAS, true)) $mv += ['host' => $lb['host'], 'kind' => 'upgrade', 'detach' => true];   // ejected
        $copy = _SwuPgnMMove($run, $mv, null);
        if ($lb['deploy']) {
            $epic = false;
            foreach ($run['pool'] as $j => $x) if ($x['k'] === 'leader' && $x['seat'] === $lb['seat'] && $x['field'] === 'epicUsed' && $x['value'] === true) { $epic = true; $run['claimed'][$j] = true; }
            $pilot = isset($c['host']);
            _SwuPgnMEmit($run, 'DEPLOY_LEADER', ['p' => $lb['seat'], 'card' => $copy, 'zone' => $c['to'], 'epic' => $epic ? true : null,
                'kind' => $pilot ? 'upgrade' : null, 'target' => $pilot ? _SwuPgnMRef($run, intval($c['host'])) : null], null, true);
        }
        _SwuPgnMArrival($run, $mv, $copy, null);
        return true;
    }
    return null;
}

function _SwuPgnMLeaderCard(array &$run, int $seat): string {
    foreach ($run['pool'] as $c) if ($c['k'] === 'move' && $c['from'] === 'base' && $c['seat'] === $seat) return $c['cardID'];
    return $run['ctx']['ledger']['zones'][$seat]['base'][0] ?? 'unknown';
}

// ── seq and `for` (§9.1) ────────────────────────────────────────────────────────────────────────

function _SwuPgnSubLetters(int $n): string {
    $s = '';
    while ($n > 0) { $n--; $s = chr(97 + $n % 26) . $s; $n = intdiv($n, 26); }
    return $s;
}

function _SwuPgnMSequence(array &$run): array {
    $ctx = &$run['ctx'];
    $phase = $ctx['phase'] ?? 'A'; $round = intval($ctx['round'] ?? 0);
    $key = "R$round.$phase";
    if (($ctx['counters']['key'] ?? null) !== $key) $ctx['counters'] = ['key' => $key, 'step' => 0, 'sub' => 0];
    $cn = &$ctx['counters'];
    $out = &$run['out'];
    foreach ($out as &$e) {
        if ($phase !== 'A' || $e['top']) { $cn['step']++; $cn['sub'] = 0; $e['seq'] = "$key.{$cn['step']}"; $e['isStep'] = true; }
        else { $cn['sub']++; $e['seq'] = "$key.{$cn['step']}" . _SwuPgnSubLetters($cn['sub']); $e['isStep'] = false; }
    }
    unset($e);
    $isActionOwner = fn($o) => $o !== null && in_array($run['notes'][$o]['t'] ?? '', SWUPGN_ACTION_NOTES, true)
        || ($o !== null && ($run['notes'][$o]['t'] ?? '') === 'ABILITY' && in_array($run['notes'][$o]['kind'] ?? '', ['action', 'epic'], true));
    foreach ($out as $i => $e) {
        $t = $e['ev']['t'];
        if ($t === 'RESOURCE' && $i > 0 && !$out[$i - 1]['isStep'] && $out[$i - 1]['ev']['t'] === 'MOVE' && ($out[$i - 1]['ev']['card'] ?? null) === $e['ev']['card']) {
            $out[$i - 1]['for'] = $e['seq'];
            continue;
        }
        if (!$e['top'] || $e['owner'] === null) continue;
        $allowed = $t === 'ATTACK' ? ['CHOICE', 'MODAL_CHOICE', 'EXHAUST'] : ['MOVE', 'CHOICE', 'MODAL_CHOICE', 'EXHAUST', 'STATS', 'EXHAUST_RESOURCES'];
        $anchor = $e['ev']['atk'] ?? $e['ev']['card'] ?? null;
        $runIdx = []; $named = false;
        for ($j = $i - 1; $j >= 0; $j--) {
            $r = $out[$j];
            if ($r['isStep'] || !in_array($r['ev']['t'], $allowed, true)) break;
            if ($r['owner'] !== $e['owner'] && $isActionOwner($r['owner'])) break;   // another action's own records
            $runIdx[] = $j;
            if ($anchor !== null && ($r['ev']['card'] ?? null) === $anchor) $named = true;
        }
        if ($named) foreach ($runIdx as $j) $out[$j]['for'] = $e['seq'];
    }
    $events = [];
    foreach ($out as $e) {
        $ev = ['seq' => $e['seq']] + $e['ev'];
        if (isset($e['for'])) $ev['for'] = $e['for'];
        $events[] = $ev;
    }
    return $events;
}
