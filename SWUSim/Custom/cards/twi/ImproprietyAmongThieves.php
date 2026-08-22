<?php
// TWI_204
// Cost 4 - Impropriety Among Thieves - [Cunning,Cunning]
// Text: Choose a ready non-leader unit controlled by each player. If you do, each player takes control of
//       the chosen unit controlled by the player to their right. At the start of the regroup phase, each
//       player takes control of each unit they own that was chosen for this ability.
//
// ⚠ AT TWO SEATS THIS IS A SWAP; AT FOUR IT IS A ROTATION. Same sentence, structurally different effect —
// and a swap implementation does not generalise. "The player to their right" is the next live seat along
// SeatOrder (USER RULING 2026-08-21, see SWUSeatToTheRight), so with seats 1234:
//     P1 takes P2's chosen unit · P2 takes P3's · P3 takes P4's · P4 WRAPS and takes P1's
// The old code hard-coded the two-seat swap (caster ⇄ OtherPlayer) and asked for only two picks.
//
// THE CASTER MAKES EVERY PICK. "Choose a ready non-leader unit controlled by each player" is one
// instruction addressed to the player resolving the event — the other seats do not choose their own.
// So the picks are queued on the CASTER, one per seat in player order, and every pool is minted in the
// CASTER's frame.
//
// "IF YOU DO" IS ALL-OR-NOTHING: a unit must be chosen for EVERY player. If any seat has no ready
// non-leader unit there is no legal set of choices, so nothing happens at all — not a partial rotation.
// Checked up front AND again as each seat is asked (the board can move between picks).
//
// TEMPORARY_STEAL on each moved unit is what implements the last sentence: the regroup sweep returns
// every marked unit to its owner. That already walks all seats, so it needs no change here.

// Ready, non-leader units controlled by $seat, as mzIDs in the CASTER's frame (the caster is choosing).
if (!function_exists('_SWUTwi204PoolOf')) {
    function _SWUTwi204PoolOf(int $caster, int $seat): array {
        global $playerID; $playerID = $caster;
        $out = [];
        $zones = ($seat === $caster)
            ? ['myGroundArena', 'mySpaceArena']
            : ['theirGroundArena', 'theirSpaceArena'];
        foreach ($zones as $z) {
            foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (intval($o->Controller ?? 0) !== $seat) continue;   // Twin Suns: "their…" spans every opponent
                if (intval($o->Status ?? 0) !== 1) continue;           // READY only
                if (IsLeaderUnit($o)) continue;                        // NON-LEADER only
                $out[] = $mz;
            }
        }
        return $out;
    }
}

// Ask the caster for the next seat's unit. $chosen is "seat:uid" pairs collected so far.
if (!function_exists('_SWUTwi204Ask')) {
    function _SWUTwi204Ask(int $caster, array $remaining, array $chosen): void {
        while (!empty($remaining)) {
            $seat = intval(array_shift($remaining));
            $pool = _SWUTwi204PoolOf($caster, $seat);
            if (empty($pool)) return;    // no legal choice for this seat ⇒ "if you do" fails ⇒ nothing happens
            $enc = [];
            foreach ($chosen as $s => $u) $enc[] = "{$s}:{$u}";
            SWUQueueChooseTarget($caster, $pool, "Choose_a_ready_non-leader_unit_controlled_by_P{$seat}",
                "TWI_204#PICK|{$seat}|" . implode(',', $enc) . '|' . implode(',', $remaining));
            return;
        }
        _SWUTwi204Rotate($caster, $chosen);
    }
}

// Everyone takes the unit chosen for the seat to THEIR right, all at once.
if (!function_exists('_SWUTwi204Rotate')) {
    function _SWUTwi204Rotate(int $caster, array $chosen): void {
        global $playerID;
        foreach (SWUSeatsInPlayerOrder($caster) as $seat) {
            $uid = intval($chosen[SWUSeatToTheRight($seat)] ?? 0);
            if ($uid <= 0) continue;
            $playerID = $caster;
            $mz = SWUFindMzByUID($uid);          // re-found每 time: each move re-indexes the arenas
            if ($mz === null || $mz === '') continue;
            $newMz = SWUTakeControlOfUnit($seat, $mz);
            if ($newMz === '') continue;
            // SWUTakeControlOfUnit returns the mzID in the NEW CONTROLLER's frame, so stamp the marker
            // under that frame or it lands on whatever sits in that slot of the caster's arena.
            $playerID = $seat;
            AddTurnEffect($newMz, 'TEMPORARY_STEAL');
            $playerID = $caster;
        }
    }
}

$customDQHandlers["TWI_204#PICK"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $seat   = intval($parts[0] ?? 0);
    $chosen = [];
    foreach (array_filter(explode(',', (string)($parts[1] ?? '')), fn($v) => $v !== '') as $pair) {
        [$s, $u] = array_pad(explode(':', $pair, 2), 2, 0);
        $chosen[intval($s)] = intval($u);
    }
    $remaining = array_values(array_filter(explode(',', (string)($parts[2] ?? '')), fn($v) => $v !== ''));
    if (SWUDecisionDeclined($lastDecision)) return;      // mandatory choose; a blank answer fizzles it all
    $playerID = $caster;
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $chosen[$seat] = intval($o->UniqueID ?? 0);
    _SWUTwi204Ask($caster, $remaining, $chosen);
};

$whenPlayedAbilities["TWI_204:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $seats = SWUSeatsInPlayerOrder(intval($player));
    // "If you do" — EVERY seat must have a legal pick, or the whole thing fizzles before any choice.
    foreach ($seats as $seat) {
        if (empty(_SWUTwi204PoolOf(intval($player), $seat))) return;
    }
    _SWUTwi204Ask(intval($player), $seats, []);
};
