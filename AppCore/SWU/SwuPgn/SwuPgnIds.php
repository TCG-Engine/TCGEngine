<?php
// SWU-PGN ids and names (SWU-PGN/1.0 spec §6.1 card ids, §6.3 base refs, §6.5 CARDS index,
// §16 nm()/who()).
//
// The copy suffix `:N` is always LAST, so it is stripped before anything else is read from an id —
// including a token's numeric card id (`TOKEN:advantage#5844562972:2` → card id `5844562972`).

function SwuPgnBaseId(string $id): string
{
    return preg_replace('/:\d+\z/', '', $id);
}

function SwuPgnCopyNumber(string $id): ?int
{
    return preg_match('/:(\d+)\z/', $id, $m) ? (int)$m[1] : null;
}

// §6.1 TOKEN:<internalName>#<cardId>[:<copy>]. `TOKEN:<name>` without `#` is the degraded form:
// a stable identity with nothing to resolve. Returns null for anything that is not a token id.
function SwuPgnTokenParts(string $id): ?array
{
    if (strncmp($id, 'TOKEN:', 6) !== 0) return null;
    $rest = substr(SwuPgnBaseId($id), 6);
    $hash = strpos($rest, '#');
    $name = $hash === false ? $rest : substr($rest, 0, $hash);
    $cardId = $hash === false ? null : substr($rest, $hash + 1);
    return [
        'name' => $name,
        'cardId' => $cardId,
        'copy' => SwuPgnCopyNumber($id),
        'resolvable' => $cardId !== null && $cardId !== '' && ctype_digit($cardId),
    ];
}

// §6.1 "Two reserved token names": a Credit token drives `credits`, the Force token `hasForce`.
// Recognised by name, so the degraded `TOKEN:credit` form counts too.
function _SwuPgnReservedToken($id): ?string
{
    if (!is_string($id)) return null;
    $t = SwuPgnTokenParts($id);
    if ($t === null) return null;
    return ($t['name'] === 'credit' || $t['name'] === 'the-force') ? $t['name'] : null;
}

// §6.3 base@N → N; anything else → null (it is a card id).
function SwuPgnSeatOfBaseRef($ref): ?int
{
    if (!is_string($ref) || !preg_match('/^base@(\d{1,9})\z/', $ref, $m)) return null;
    return (int)$m[1];
}

// §6.5: the CARDS section as [baseId => name]. Later entries win, like header tags.
function SwuPgnCardIndex(array $doc): array
{
    $index = [];
    foreach (($doc['cards'] ?? []) as $r) {
        if (is_array($r) && is_string($r['id'] ?? null) && is_string($r['name'] ?? null)) {
            $index[$r['id']] = $r['name'];
        }
    }
    return $index;
}

// §16 nm(): strip `:N`, look the base up, append ` #N` back for a copy; an id the index does not
// cover falls back to the id itself (§6.5); `base@N` reads `Player N's base`.
function SwuPgnName(array $index, $id): string
{
    if (is_int($id)) $id = (string)$id;
    if (!is_string($id)) return '?';
    $seat = SwuPgnSeatOfBaseRef($id);
    if ($seat !== null) return "Player $seat's base";
    $base = SwuPgnBaseId($id);
    $name = $index[$base] ?? $base;
    $copy = SwuPgnCopyNumber($id);
    return $copy === null ? $name : "$name #$copy";
}

// §16 who(): "Player 1" for seat 1, "Player 2" for seat 2, "" otherwise.
function SwuPgnWho($p): string
{
    return $p === 1 ? 'Player 1' : ($p === 2 ? 'Player 2' : '');
}

// A seat number as the fold accepts it: the integer 1 or 2.
function _SwuPgnSeat($v): ?int
{
    return ($v === 1 || $v === 2) ? $v : null;
}
