<?php
// SWU-PGN step list for replay viewers: one step per numbered action (SWU-PGN/1.0 spec §16) plus one
// per PHASE_START, each with the event position whose board it shows. Engine-free.

// §9.1 "Which types can precede depends on the action".
const SWUPGN_ATTACK_PRECURSORS = ['CHOICE', 'MODAL_CHOICE', 'EXHAUST'];
const SWUPGN_ABILITY_PRECURSORS = ['CHOICE', 'MODAL_CHOICE'];   // its costs are paid after it is announced
const SWUPGN_PLAY_PRECURSORS = ['MOVE', 'CHOICE', 'MODAL_CHOICE', 'EXHAUST', 'STATS', 'EXHAUST_RESOURCES'];

// §9.1: another card arriving in play (into an arena, or onto a base as an upgrade) ends a run — that card's arrival
// and the records naming it right after are its own story.
function _SwuPgnArrivalOf($r, $anchor): ?string
{
    if (!is_array($r) || ($r['t'] ?? null) !== 'MOVE' || !is_string($r['card'] ?? null) || $r['card'] === $anchor) return null;
    $to = $r['to'] ?? null;
    return SwuPgnIsArena($to) || ($to === 'base' && ($r['kind'] ?? null) === 'upgrade') ? $r['card'] : null;
}

function _SwuPgnIsTopLevelSeq($seq): bool
{
    return is_string($seq) && preg_match('/^R\d+\.[SAG]\.\d+$/', $seq) === 1;
}

// §9.1: where a numbered action's step starts — its earliest `for`-stamped record, else the
// contiguous precursor run before it when that run names the action's card.
function _SwuPgnActionStart(array $events, int $i, int $floor): int
{
    $e = $events[$i];
    $seq = $e['seq'] ?? null;
    $start = $i;
    if (is_string($seq)) {
        for ($j = $i - 1; $j >= $floor; $j--) {
            if (is_array($events[$j]) && ($events[$j]['for'] ?? null) === $seq) $start = $j;
        }
        if ($start !== $i) return $start;
    }
    $t = $e['t'] ?? null;
    $isAttack = $t === 'ATTACK';
    $types = $isAttack ? SWUPGN_ATTACK_PRECURSORS : ($t === 'ABILITY_ACTIVATE' ? SWUPGN_ABILITY_PRECURSORS : SWUPGN_PLAY_PRECURSORS);
    $card = $isAttack ? ($e['atk'] ?? null) : ($e['card'] ?? null);
    $j = $i - 1;
    $named = false;
    $arrived = null;
    while ($j >= $floor && is_array($events[$j]) && in_array($events[$j]['t'] ?? null, $types, true)
        && !_SwuPgnIsTopLevelSeq($events[$j]['seq'] ?? null)) {
        if (($arrived = _SwuPgnArrivalOf($events[$j], $card)) !== null) break;
        if ($card !== null && ($events[$j]['card'] ?? null) === $card) $named = true;
        $j--;
    }
    $start = $j + 1;
    // The records naming the arrived card right after its arrival stay with it.
    while ($arrived !== null && $start < $i && ($events[$start]['card'] ?? null) === $arrived) $start++;
    return $named ? $start : $i;
}

// A story line as $seat sees it: the other seat's draws, resources and search finds name no card.
// $seat null = all-seeing. $faces: which side of a two-named card is up at this event.
function _SwuPgnSeatLine(array $e, array $names, ?int $seat, ?array $faces = null): ?string
{
    $p = $e['p'] ?? null;
    if ($seat !== null && in_array($p, [1, 2], true) && $p !== $seat) {
        $who = SwuPgnWho($p);
        switch ($e['t'] ?? null) {
            case 'DRAW': return "$who draws " . (is_int($e['count'] ?? null) ? $e['count'] : (is_array($e['cards'] ?? null) ? count($e['cards']) : '?'));
            case 'RESOURCE': return "$who resources a card";
            case 'SEARCH': return "$who searches their deck";
        }
    }
    return SwuPgnStoryLine($e, $names, $faces);
}

function SwuPgnSteps(array $doc, ?int $seat = null): array
{
    $events = array_values(array_slice(is_array($doc['events'] ?? null) ? $doc['events'] : [], 0, SWUPGN_MAX_EVENTS));
    $names = SwuPgnCardIndex($doc);
    // The side of each two-named card that is up AT each event (before its own change).
    $faces = SwuPgnFacesStart(SwuPgnCardBackIndex($doc));
    $facesAt = [];
    foreach ($events as $i => $e) {
        if (!is_array($e)) continue;
        if (_SwuPgnIsKeyframeEvent($e)) $faces = SwuPgnFacesStep($faces, $e);
        $facesAt[$i] = $faces;
        if (!_SwuPgnIsKeyframeEvent($e)) $faces = SwuPgnFacesStep($faces, $e);
    }
    $marks = [];
    $round = 0;
    $phase = 'setup';
    $prevAnchor = -1;
    foreach ($events as $i => $e) {
        if (!is_array($e)) continue;
        $t = $e['t'] ?? null;
        if ($t === 'ROUND_START' && is_int($e['round'] ?? null)) $round = $e['round'];
        if ($t === 'PHASE_START') {
            $phase = is_string($e['phase'] ?? null) ? $e['phase'] : $phase;
            $marks[] = ['start' => $i, 'anchor' => $i, 'kind' => 'phase', 'round' => $round, 'phase' => $phase];
            $prevAnchor = $i;
        } elseif (SwuPgnIsNumberedAction($e)) {
            $marks[] = ['start' => _SwuPgnActionStart($events, $i, $prevAnchor + 1), 'anchor' => $i, 'kind' => 'action', 'round' => $round, 'phase' => $phase];
            $prevAnchor = $i;
        }
    }
    $steps = [];
    $rounds = [];
    $last = count($events) - 1;
    foreach ($marks as $k => $m) {
        $pos = isset($marks[$k + 1]) ? $marks[$k + 1]['start'] - 1 : $last;
        $anchor = $events[$m['anchor']];
        $caption = $m['kind'] === 'action'
            ? (_SwuPgnSeatLine($anchor, $names, $seat, $facesAt[$m['anchor']] ?? null) ?? (string)($anchor['t'] ?? ''))
            : ($m['phase'] === 'setup' ? 'Setup' : "Round {$m['round']} · {$m['phase']} phase");
        $lines = [];
        for ($j = $m['start']; $j <= $pos; $j++) {
            if ($j === $m['anchor'] || !is_array($events[$j])) continue;
            $line = _SwuPgnSeatLine($events[$j], $names, $seat, $facesAt[$j] ?? null);
            if ($line !== null) $lines[] = $line;
        }
        $n = count($steps);
        if (!array_key_exists($m['round'], $rounds)) $rounds[$m['round']] = $n;
        $steps[] = ['n' => $n, 'kind' => $m['kind'], 'seq' => is_string($anchor['seq'] ?? null) ? $anchor['seq'] : null,
            'round' => $m['round'], 'phase' => $m['phase'],
            'actor' => $m['kind'] === 'action' && in_array($anchor['p'] ?? null, [1, 2], true) ? $anchor['p'] : null,
            'pos' => $pos, 'caption' => $caption, 'lines' => $lines];
    }
    return ['steps' => $steps, 'rounds' => $rounds];
}
