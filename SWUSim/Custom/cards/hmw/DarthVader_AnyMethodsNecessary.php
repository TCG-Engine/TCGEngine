<?php
// HMW_043
// Cost 9 - Darth Vader, Any Methods Necessary - [Aggression][Command][Villainy] - Unit (Ground) 9/8
// Traits: Force, Imperial, Sith - Unique
// Text: Saboteur
//       When Played: Search the top 8 cards of your deck for up to 2 units that each cost 4 or less,
//       play them for free, and deal 2 damage to each of them.
//
// SABOTEUR needs no code: HMW_043 is already in $Saboteur_Cards (the generator derives membership from
// the card text) and the keyword has generic behaviour tests under Tests/Cases/keywords/.
//
// THE COST CAP IS PER-CARD, NOT A BUDGET. "up to 2 units that EACH cost 4 or less" is two independent
// gates — a type+cost FILTER on what may be picked, and a COUNT cap of 2. That is why the constraint is
// `count:2` and the cost lives in the filter, rather than the `cost:N:M` combined-budget constraint
// DoTopDeckPlay uses for SOR_104 U-Wing Reinforcement ("combined cost 7 or less").
//
// "PLAY them for free" is a REAL PLAY — and per the USER RULING (2026-08-13, strict CR 522.e/7.6.8):
// the fetched units' When Played / Shielded / Ambush triggers resolve ONLY AFTER this whole ability
// finishes resolving, INCLUDING the "deal 2 damage to each of them" rider. So: play both, damage both,
// THEN the entry triggers resolve (CR 780: they still resolve for a unit the rider defeated — and
// fizzle if their subject is gone, which is how a 2-HP Shielded unit dies before its shield arrives).
// Mechanically: both plays and the damage run inline in ONE handler — ActivateCard QUEUES the entry-
// trigger decisions rather than resolving them mid-handler, so everything queued lands after our inline
// damage. That deletes the old play-loop (whose whole point was draining triggers between plays, per
// the earlier per-play ruling this one supersedes). Damage targets are captured by UNIQUE ID right
// after each play — never arena slot (mid-chain death re-indexes) and never CardID (same-named
// bystanders).

// THE gate, used in BOTH directions: it builds the offer AND re-checks every pick server-side.
function _SWUHmw043IsLegalPick(string $cardID): bool {
    return strpos(CardType($cardID) ?? '', 'Unit') !== false && intval(CardCost($cardID)) <= 4;
}

$whenPlayedAbilities["HMW_043:0"] = function ($player, $mzID = '') {
    _topDeckSearchBegin(intval($player), 8, '_SWUHmw043IsLegalPick', "count:2", "HMW_043#0");
};

// Resolve the picks, bottom the rest, then play + damage INLINE (triggers resolve after — see above).
$customDQHandlers["HMW_043#0"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    // Server-side re-check of the pick filter (defence in depth beside the central TopDeckLegalIDs
    // gate): an illegal pick is not played and joins the cards going to the bottom.
    $picks = []; $back = [];
    foreach ($resolved['drawn'] as $cid) {
        if (_SWUHmw043IsLegalPick($cid)) $picks[] = $cid; else $back[] = $cid;
    }
    _topDeckPutRemainingToBottom(intval($player), array_merge($resolved['remaining'], $back));

    $uids = [];
    foreach ($picks as $cardID) {
        if (SWUCardPlayBlocked(intval($player), $cardID)) {   // SOR_062 Regional Governor
            _topDeckPutRemainingToBottom(intval($player), [$cardID]);
            continue;
        }
        $deck = &GetDeck(intval($player));
        $obj  = new Deck($cardID, 'Deck', intval($player));
        array_unshift($deck, $obj);
        foreach ($deck as $i => $c) { $c->mzIndex = $i; }
        SWUPlayTopDeckCard(intval($player), true);
        // Placement is synchronous inside the call; its trigger DECISIONS are queued, not yet resolved.
        // Newest (highest-UID) friendly arena object with this CardID = the one just played.
        $best = 0;
        foreach (["myGroundArena", "mySpaceArena"] as $z) {
            foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (($o->CardID ?? '') !== $cardID) continue;
                $u = intval($o->UniqueID ?? 0);
                if ($u > $best && !in_array($u, $uids, true)) $best = $u;
            }
        }
        if ($best > 0) $uids[] = $best;
    }

    // The rider, INLINE — before any queued entry trigger resolves. A unit the 2 defeats still gets its
    // When Played/Shielded afterwards (CR 780), which then fizzles if it needs the unit in play.
    foreach ($uids as $uid) {
        $mz = _SWUHmw043MzIDForUID(intval($player), intval($uid));
        if ($mz === null) continue;
        SWUDealDamageToUnit($mz, 2, intval($player));
    }
    DecisionQueueController::CleanupRemovedCards();
    $playerID = $savedPID;
};

// Current mzID of the friendly unit with $uid, or null if it is no longer in play.
function _SWUHmw043MzIDForUID(int $player, int $uid): ?string {
    global $playerID;
    $saved = $playerID; $playerID = intval($player);
    $found = null;
    foreach (["myGroundArena", "mySpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            if (intval($o->UniqueID ?? 0) === $uid) { $found = $mz; break 2; }
        }
    }
    $playerID = $saved;
    return $found;
}
