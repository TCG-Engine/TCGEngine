<?php
// Limited Draft rules — the pure half of a Draft room, kept on the lobby blob as $lobby->limited['draft'].
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §2.4, §3.2, §3.6.
//
// Like LimitedRoomState.php: functions take the blob and return a NEW blob, or null when refused. No I/O and NO CARD
// DICTIONARY — every card's aspects are copied into the blob when the packs open (`aspects`), so the timeout auto-pick is
// pure and the lobby poll (which loads no card data) can apply overdue picks in its own lock.
//
//   stage        'leaders' (round 0) → 'cards' (rounds 1..N)
//   order        the seating, shuffled once at start. LEFT = the next seat, RIGHT = the previous one (wrapping)
//   unopened     [pid => the card packs still to open, one per remaining round] — leader- and base-slot cards removed
//   queues       [pid => [[id, cards[], size], …]] — a seat picks from its FRONT pack; passed packs join the neighbour's
//                tail; size = the cards the pack opened with (Icons packs are smaller)
//   frontSince   [pid => when the current front pack reached the front] — the pick clock (null = nothing to pick)
//   leaders / picks   [pid => card ids]      autoPicks   [pid => timeout auto-picks]
//   timeoutRun   [pid => timeout auto-picks since that seat last picked for itself] — the kick rule reads this
//   pickCounter  every pick so far (seeds the auto-pick RNG)        aspects   [cardId => aspect[]]
require_once __DIR__ . '/LimitedRoomState.php';

const LT_PICK_SECONDS    = 300;   // owner: at most 5 minutes a pick
const LT_ASPECT_ORDER    = ['Vigilance', 'Command', 'Aggression', 'Cunning', 'Heroism', 'Villainy'];

// $leaderPacks[pid] = the N leaders of that seat's own packs; $cardPacks[pid] = its N card packs (no leader/base slot).
function LTDraftBegin(array $lt, array $order, array $leaderPacks, array $cardPacks, array $aspects, int $seed, int $pickSeconds, int $now): ?array {
    $live = LTLiveSeats($lt);
    $sorted = $order; sort($sorted);
    if ($lt['phase'] !== 'setup' || $lt['mode'] !== 'draft' || $lt['settingsErrors'] !== [] || $sorted !== $live
        || count($order) < LT_DRAFT_MIN_SEATS || count($order) > LT_DRAFT_MAX_SEATS) return null;
    $d = ['stage' => 'leaders', 'round' => 0, 'rounds' => count($cardPacks[$order[0]] ?? []), 'order' => array_values($order),
          'unopened' => [], 'queues' => [], 'frontSince' => [], 'leaders' => [], 'picks' => [], 'autoPicks' => [], 'timeoutRun' => [],
          'pickCounter' => 0, 'nextPackId' => 0, 'aspects' => $aspects, 'pickSeconds' => $pickSeconds];
    foreach ($order as $p) {
        $d['unopened'][$p] = array_values(array_map('array_values', $cardPacks[$p] ?? []));
        // A seat whose packs opened no leader (an Icons pack is a leader only 1 time in 4) has no leader pack at all.
        $lp = array_values($leaderPacks[$p] ?? []);
        $d['queues'][$p] = $lp ? [['id' => ++$d['nextPackId'], 'cards' => $lp, 'size' => count($lp)]] : [];
        $d['frontSince'][$p] = $lp ? $now : null;
        $d['leaders'][$p] = []; $d['picks'][$p] = []; $d['autoPicks'][$p] = 0; $d['timeoutRun'][$p] = 0;
    }
    // No leader opened anywhere: there is no leader draft — pack 1 opens at once.
    if (!array_filter($d['queues'])) { $d['stage'] = 'cards'; $d['round'] = 1; $d = _LTDraftOpenRound($d, $now); }
    $lt['draft'] = $d;
    $lt['seed'] = $seed;
    $lt['phase'] = 'drafting';
    return $lt;
}

function LTDraftNeighbour(array $d, int $pid, string $dir): int {
    $n = count($d['order']);
    $i = array_search($pid, $d['order'], true);
    return $d['order'][($dir === 'left' ? $i + 1 : $i - 1 + $n) % $n];
}

// Leaders pass right; card rounds alternate left, right, left, … (spec §2.4, §3.2).
function LTDraftDirection(array $d): string {
    if ($d['stage'] === 'leaders') return 'right';
    return $d['round'] % 2 === 1 ? 'left' : 'right';
}

function LTDraftFront(array $d, int $pid): ?array {
    return $d['queues'][$pid][0] ?? null;
}

// Every seat opens its pack for the current round; each new front starts its clock now.
function _LTDraftOpenRound(array $d, int $now): array {
    foreach ($d['order'] as $p) {
        $pack = array_shift($d['unopened'][$p]);
        $d['queues'][$p] = $pack ? [['id' => ++$d['nextPackId'], 'cards' => $pack, 'size' => count($pack)]] : [];   // size: for "Pick k of n"
        $d['frontSince'][$p] = $pack ? $now : null;
    }
    return $d;
}

function LTDraftPick(array $lt, int $pid, int $packId, string $cardId, int $now, bool $auto = false): ?array {
    if ($lt['phase'] !== 'drafting') return null;
    $d = $lt['draft'];
    $front = LTDraftFront($d, $pid);
    if ($front === null || $front['id'] !== $packId) return null;
    $at = array_search($cardId, $front['cards'], true);
    if ($at === false) return null;
    $rest = $front['cards'];
    array_splice($rest, $at, 1);
    if ($d['stage'] === 'leaders') $d['leaders'][$pid][] = $cardId; else $d['picks'][$pid][] = $cardId;
    array_shift($d['queues'][$pid]);
    $d['frontSince'][$pid] = $d['queues'][$pid] ? $now : null;
    if ($rest) {
        $to = LTDraftNeighbour($d, $pid, LTDraftDirection($d));
        if (!$d['queues'][$to]) $d['frontSince'][$to] = $now;   // it lands at the front: its clock starts now
        $d['queues'][$to][] = ['id' => $packId, 'cards' => array_values($rest), 'size' => intval($front['size'] ?? count($front['cards']))];
    }
    if ($auto) $d['autoPicks'][$pid]++;
    $d['timeoutRun'][$pid] = $auto ? intval($d['timeoutRun'][$pid] ?? 0) + 1 : 0;   // picking for yourself again clears it
    $d['pickCounter']++;
    $empty = true;
    foreach ($d['order'] as $p) if ($d['queues'][$p]) { $empty = false; break; }
    if ($empty) {
        if ($d['round'] < $d['rounds']) {
            $d['stage'] = 'cards';
            $d['round']++;
            $d = _LTDraftOpenRound($d, $now);
        } else {
            // The draft is over: each seat's pool is what it drafted, and the 30-minute build begins.
            $d['stage'] = 'done';
            foreach ($d['order'] as $p) $lt['pools'][$p] = array_merge($d['leaders'][$p], $d['picks'][$p]);
            $lt['phase'] = 'building';
            $lt['buildDeadline'] = $now + LT_BUILD_SECONDS;
        }
    }
    $lt['draft'] = $d;
    return $lt;
}

// Every aspect icon on the seat's leaders and picks, and the two it leans on (ties by the canonical order).
function LTDraftLean(array $lt, int $pid): array {
    $d = $lt['draft'];
    $counts = array_fill_keys(LT_ASPECT_ORDER, 0);
    foreach (array_merge($d['leaders'][$pid] ?? [], $d['picks'][$pid] ?? []) as $c) {
        foreach ($d['aspects'][$c] ?? [] as $a) if (isset($counts[$a])) $counts[$a]++;
    }
    $ranked = array_values(array_filter(LT_ASPECT_ORDER, fn($a) => $counts[$a] > 0));
    usort($ranked, fn($x, $y) => $counts[$y] <=> $counts[$x] ?: array_search($x, LT_ASPECT_ORDER) <=> array_search($y, LT_ASPECT_ORDER));
    return ['counts' => $counts, 'pair' => array_slice($ranked, 0, 2)];
}

// The card a timeout picks for this seat's front pack (spec §3.2): a leader at random in the leader draft; otherwise a
// random card that fits the lean pair (every aspect in the pair; an aspect-less card fits), else one that fits the top
// aspect alone, else any. Seeded by the room seed + the pick count, so a state always picks the same card.
function LTDraftAutoPickCard(array $lt, int $pid): ?string {
    $d = $lt['draft'];
    $front = LTDraftFront($d, $pid);
    if ($front === null || !$front['cards']) return null;
    $cards = $front['cards'];
    $pool = $cards;
    if ($d['stage'] !== 'leaders') {
        $pair = LTDraftLean($lt, $pid)['pair'];
        $fits = fn(array $set) => array_values(array_filter($cards, fn($c) => !array_diff($d['aspects'][$c] ?? [], $set)));
        if ($pair) {
            $byPair = $fits($pair);
            $byTop  = $fits([$pair[0]]);
            $pool = $byPair ?: ($byTop ?: $cards);
        }
    }
    $rng = new \Random\Randomizer(new \Random\Engine\Mt19937(intval($lt['seed'] ?? 0) + intval($d['pickCounter'])));
    return $pool[$rng->pickArrayKeys($pool, 1)[0]];
}

// The draft's clock: a dropped seat picks at once (it keeps the pod moving, spec §3.6); a live seat whose front pack has
// waited pickSeconds is auto-picked (and that counts toward its kick rule). Repeats until nothing more is due.
function LTDraftTick(array $lt, array $lastSeen, int $now): array {
    for ($guard = 0; $guard < 10000 && $lt['phase'] === 'drafting'; $guard++) {
        $moved = false;
        foreach ($lt['draft']['order'] as $p) {
            if ($lt['phase'] !== 'drafting') break;
            $d = $lt['draft'];
            $front = LTDraftFront($d, $p);
            if ($front === null) continue;
            $dropped = !empty($lt['seats'][$p]['dropped']);
            $due = $d['frontSince'][$p] !== null && $d['frontSince'][$p] + intval($d['pickSeconds']) <= $now;
            if (!$dropped && !$due) continue;
            $next = LTDraftPick($lt, $p, $front['id'], LTDraftAutoPickCard($lt, $p), $now, !$dropped);
            if ($next !== null) { $lt = $next; $moved = true; }
        }
        if (!$moved) break;
    }
    return $lt;
}

// Who may be kick-voted while drafting (spec §3.6): a seat silent for over 30 s, or a connected seat whose last pick was a
// timeout auto-pick (one slow pick, then picking again, does not leave it kickable for the rest of the draft) — outside a Wait.
function LTDraftKickable(array $lt, array $lastSeen, int $now): array {
    if ($lt['phase'] !== 'drafting') return [];
    $out = [];
    foreach (LTLiveSeats($lt) as $p) {
        if (intval($lt['kick'][$p]['waitUntil'] ?? 0) > $now) continue;
        $gone = $now - intval($lastSeen[$p] ?? 0) > LT_DISCONNECT_SECONDS;
        if ($gone || intval($lt['draft']['timeoutRun'][$p] ?? 0) >= 1) $out[] = $p;
    }
    return $out;
}
