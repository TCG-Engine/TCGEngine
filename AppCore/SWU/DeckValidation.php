<?php
// Shared SWU deck-legality validator — usable by any SWU site (SWUSim game, SWUDeck builder).
//
// It is effectively a PURE "legal or not" check: no side effects, deterministic. Its only card-data
// needs are (a) the universe of card IDs, for reprint resolution — identical shared reference data
// every SWU site loads, read from the global $titleData; and (b) leader ASPECTS, for the Twin Suns
// alignment rule — the one genuinely per-leader input, which callers may pass in explicitly
// ($leaderAspects) so the check needs nothing from ambient state, falling back to the global
// $aspectData when omitted (so in-engine callers are unchanged).
//
// Static deps only (config + the reprint map); the card dictionary is a runtime global supplied by
// whichever site includes this — this file deliberately does NOT load a dictionary (that would couple
// AppCore/SWU to one site).

include_once __DIR__ . '/Overrides.php'; // CardIDOverride — reprint → earliest printing
include_once __DIR__ . '/Formats.php';             // SWUGetFormat / SWUFormatLegalSets / config

// Short set prefix of a SET_NNN card ID.
function SWUCardSet($cardID) {
    return strtoupper(explode('_', (string)$cardID)[0] ?? '');
}

// Every known printing that shares a canonical (earliest) printing with $cardID, including $cardID
// and the canonical itself. Built once by inverting CardIDOverride over the full card-ID universe
// (the shared $titleData list) — the single source of reprint relationships shared with SWUDeck stats.
function SWUReprintGroup($cardID) {
    static $groups = null;
    if ($groups === null) {
        global $titleData;
        $groups = [];
        $ids = is_array($titleData) ? array_keys($titleData) : [];
        foreach ($ids as $id) {
            $groups[CardIDOverride($id)][] = $id;
        }
    }
    $canon = CardIDOverride($cardID);
    $group = $groups[$canon] ?? [];
    if (!in_array($cardID, $group, true)) $group[] = $cardID;
    if (!in_array($canon,  $group, true)) $group[] = $canon;
    return array_values(array_unique($group));
}

// True when $cardID — by any of its printings — appears in one of $legalSets. Lets a deck list an
// older/alternate printing of a card that is legal via a reprint (e.g. SHD_030 Death Trooper is legal
// because SOR_033 is).
function SWUCardHasLegalPrint($cardID, array $legalSets) {
    foreach (SWUReprintGroup($cardID) as $print) {
        if (in_array(SWUCardSet($print), $legalSets, true)) return true;
    }
    return false;
}

// Starting-side alignment of a leader, for the Twin Suns leader-pairing rule (CR §12.2.1.a: the two
// leaders' faceup sides can't combine Heroism + Villainy). Returns 'HEROISM' | 'VILLAINY' | 'NEUTRAL'
// (color-only leaders have neither and pair with anyone). $leaderAspects (optional) maps cardID →
// aspect string; when omitted, reads the global $aspectData. Flip leaders that PRINT both alignments
// are keyed by their STARTING (faceup) side via an explicit override — their aspect data lists both,
// so a data-only read can't tell which side starts in play.
function _SWULeaderStartAlignment($cardID, $leaderAspects = null) {
    static $startSideOverride = [
        // Chancellor Palpatine "Playing Both Sides" (TWI) — prints Heroism+Villainy, STARTS Heroism
        // (flips to a Villainy side with no leader-unit deploy). Counts as Heroism for pairing.
        'TWI_017' => 'HEROISM',
    ];
    $cardID = (string)$cardID;
    $canon  = CardIDOverride($cardID);
    if (isset($startSideOverride[$cardID])) return $startSideOverride[$cardID];
    if (isset($startSideOverride[$canon]))  return $startSideOverride[$canon];
    if (!is_array($leaderAspects)) { global $aspectData; $leaderAspects = $aspectData ?? []; }
    $a    = $leaderAspects[$cardID] ?? ($leaderAspects[$canon] ?? '');
    $aStr = is_array($a) ? implode(',', $a) : (string)$a;
    $h = stripos($aStr, 'Heroism')  !== false;
    $v = stripos($aStr, 'Villainy') !== false;
    if ($h && !$v) return 'HEROISM';
    if ($v && !$h) return 'VILLAINY';
    return 'NEUTRAL';   // no alignment (color-only), or both-without-override → treat as neutral
}

// Config-driven format legality. Returns a list of blocking error strings ([] = legal). Banned IDs
// and copy-exception / deck-modifier keys are matched CANONICALLY (CardIDOverride on both sides)
// because deck cards are canonicalized before compare. $leader is a single CardID (standard formats)
// or an array (Twin Suns = 2). $leaderAspects (optional) supplies leader aspects for the alignment
// rule; omit it to read the global $aspectData.
function SWUCheckFormat($formatId, $leader, $base, array $mainDeck, array $sideboard, $leaderAspects = null) {
    $fmt = SWUGetFormat($formatId);
    if ($fmt === null) {
        return ["Unknown format: $formatId"];
    }

    $errors    = [];
    $legalSets = SWUFormatLegalSets($formatId);

    // Canonicalize config keys/entries so they match canonicalized deck cards.
    $bannedCanon = [];
    foreach ($fmt['banned'] as $id) { $bannedCanon[CardIDOverride($id)] = true; }

    $copyExceptions = [];
    foreach ($fmt['copyExceptions'] as $id => $max) { $copyExceptions[CardIDOverride($id)] = $max; }

    $deckSizeModifiers = [];
    foreach ($fmt['deckSizeModifiers'] as $id => $delta) { $deckSizeModifiers[CardIDOverride($id)] = $delta; }

    $minDeck = $fmt['minDeck'];   // format-configured minimum (50 default, 80 for Twin Suns)

    // 1. Leader(s) legality + ban + count.
    $leaders = is_array($leader)
        ? array_values(array_filter($leader, fn($l) => $l !== '' && $l !== null))
        : (($leader !== '' && $leader !== null) ? [$leader] : []);
    if ($fmt['leaderCount'] !== 1 && count($leaders) !== $fmt['leaderCount']) {
        $errors[] = "$formatId requires exactly {$fmt['leaderCount']} leaders; found " . count($leaders) . ".";
    }
    // Highlander formats (maxCopies 1, e.g. Twin Suns): the 1-copy limit includes leaders
    // (CR §12.2.2), so the leaders must all be different cards.
    if ($fmt['maxCopies'] === 1 && count($leaders) > 1) {
        $leaderCanon = array_map('CardIDOverride', $leaders);
        if (count($leaderCanon) !== count(array_unique($leaderCanon))) {
            $errors[] = "$formatId decks cannot include duplicate leaders.";
        }
    }
    foreach ($leaders as $ld) {
        if (!SWUCardHasLegalPrint($ld, $legalSets)) {
            $errors[] = "Leader $ld is not legal in $formatId.";
        }
        if (isset($bannedCanon[CardIDOverride($ld)])) {
            $errors[] = "Leader $ld is banned in $formatId.";
        }
    }
    // Multi-leader alignment rule (Twin Suns, CR §12.2.1.a): the leaders' starting sides cannot
    // combine the Heroism and Villainy aspects. Color-only leaders (NEUTRAL) pair with anyone.
    if ($fmt['leaderCount'] > 1 && count($leaders) >= 2) {
        $aligns = array_map(fn($ld) => _SWULeaderStartAlignment($ld, $leaderAspects), $leaders);
        if (in_array('HEROISM', $aligns, true) && in_array('VILLAINY', $aligns, true)) {
            $errors[] = "$formatId leaders cannot combine the Heroism and Villainy aspects (their starting sides).";
        }
    }

    // 2. Base legality + ban + deck-size modifier.
    if ($base) {
        if (!SWUCardHasLegalPrint($base, $legalSets)) {
            $errors[] = "Base $base is not legal in $formatId.";
        }
        if (isset($bannedCanon[CardIDOverride($base)])) {
            $errors[] = "Base $base is banned in $formatId.";
        }
        $baseCanon = CardIDOverride($base);
        if (isset($deckSizeModifiers[$baseCanon])) {
            $minDeck += $deckSizeModifiers[$baseCanon];
        }
    }

    // 3. Legality + ban + copy limits over the ENTIRE registered pool (main deck + sideboard),
    //    counted by canonical printing (CR 8.36). The copy limit spans the sideboard because
    //    sideboard cards swap 1-for-1 into the deck between games — a 4th copy parked in the
    //    sideboard could otherwise be swapped in to make an illegal deck. Off-format/banned cards
    //    in the sideboard are likewise illegal since they can enter the deck.
    $pool         = array_merge($mainDeck, $sideboard);
    $cardCounts   = array_count_values(array_map('CardIDOverride', $pool));
    $illegalCards = [];
    $bannedCards  = [];
    $overLimit    = [];
    foreach ($cardCounts as $cardID => $count) {   // $cardID is already canonical
        if (!SWUCardHasLegalPrint($cardID, $legalSets)) $illegalCards[] = $cardID;
        if (isset($bannedCanon[$cardID]))               $bannedCards[]  = $cardID;
        $limit = $copyExceptions[$cardID] ?? $fmt['maxCopies'];
        if ($count > $limit) $overLimit[] = "$cardID ($count copies, max $limit)";
    }
    if (!empty($illegalCards)) {
        $shown = array_slice($illegalCards, 0, 5);
        $more  = count($illegalCards) > 5 ? ' +' . (count($illegalCards) - 5) . ' more' : '';
        $errors[] = "Cards not legal in $formatId: " . implode(', ', $shown) . $more;
    }
    if (!empty($bannedCards)) {
        $errors[] = "Banned in $formatId: " . implode(', ', array_slice($bannedCards, 0, 5));
    }
    if (!empty($overLimit)) {
        $errors[] = 'Over the copy limit: ' . implode('; ', $overLimit);
    }

    // 4. Minimum deck size.
    $deckSize = count($mainDeck);
    if ($deckSize < $minDeck) {
        $note = ($minDeck !== 50) ? " (modified to $minDeck by base)" : '';
        $errors[] = "Deck has $deckSize cards; $formatId minimum is $minDeck$note.";
    }

    // 5. Sideboard maximum.
    if (count($sideboard) > 10) {
        $errors[] = 'Sideboard has ' . count($sideboard) . ' cards; maximum is 10.';
    }

    return $errors;
}

// Boolean convenience wrapper — true when the deck is legal for the format.
function SWUIsDeckLegal($formatId, $leader, $base, array $mainDeck, array $sideboard, $leaderAspects = null) {
    return SWUCheckFormat($formatId, $leader, $base, $mainDeck, $sideboard, $leaderAspects) === [];
}

// Back-compat wrapper for the Premier default.
function SWUCheckPremierFormat($leader, $base, array $mainDeck, array $sideboard) {
    return SWUCheckFormat('premier', $leader, $base, $mainDeck, $sideboard);
}
