<?php
// LAW_099
// Cost 5 - Governor's Shuttle - [Vigilance,Villainy] - Power 2 - HP 4
// Text: When Played: Each player chooses a unit they control. Defeat those units.

// "EACH PLAYER chooses" — one pick per LIVE seat, asked in player order (caster first), and only THEN
// are all the chosen units defeated. The defeats stay SIMULTANEOUS: nothing is removed until every seat
// has answered, which is what stops an early defeat re-indexing a later seat's pool.
//
// ⚠ Was a hard-coded two-step chain — the caster picked, then `OtherPlayer($caster)` picked, then both
// died. At four seats seats 3 and 4 were never asked and never lost anything.
//
// The walk is a QUEUED chain rather than a loop because each pick is interactive: the remaining seats
// and the UIDs chosen so far ride the continuation's Param (a positional mzID would be stale by the time
// the next seat answers, and an in-memory global would be empty across the request boundary).
// A seat controlling no units is skipped silently — it has nothing to choose.

if (!function_exists('_SWULaw099Ask')) {
    function _SWULaw099Ask(int $caster, array $remaining, array $uids): void {
        global $playerID;
        while (!empty($remaining)) {
            $seat = intval(array_shift($remaining));
            $playerID = $seat;
            $units = SWUAllUnits('my');          // that seat's OWN units, in its own frame
            if (empty($units)) continue;         // controls nothing → nothing to choose
            SWUQueueChooseTarget($seat, $units, "Choose_a_unit_you_control_to_defeat",
                "LAW_099#PICK|{$caster}|" . implode(',', $uids) . '|' . implode(',', $remaining));
            return;                              // resumes in LAW_099#PICK once this seat answers
        }
        _SWULaw099Defeat($caster, $uids);        // everyone has been asked
    }
}

// Defeat every chosen unit, resolved BY UID under the caster's frame (the mzIDs minted when each seat
// chose are long stale) and all at the same point, so the choices were simultaneous.
if (!function_exists('_SWULaw099Defeat')) {
    function _SWULaw099Defeat(int $caster, array $uids): void {
        global $playerID; $playerID = $caster;
        foreach ($uids as $uid) {
            $uid = intval($uid);
            if ($uid <= 0) continue;
            $mz = SWUFindMzByUID($uid);
            if ($mz !== null) SWUDefeatUnit($caster, $mz);
        }
    }
}

$whenPlayedAbilities["LAW_099:0"] = function($player, $mzID) {
    _SWULaw099Ask(intval($player), SWUSeatsInPlayerOrder(intval($player)), []);
};

$customDQHandlers["LAW_099#PICK"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster    = intval($parts[0] ?? $player);
    $uids      = array_values(array_filter(explode(',', (string)($parts[1] ?? '')), fn($v) => $v !== ''));
    $remaining = array_values(array_filter(explode(',', (string)($parts[2] ?? '')), fn($v) => $v !== ''));
    if (!SWUDecisionDeclined($lastDecision)) {
        $playerID = intval($player);             // the mzID was minted in THIS seat's frame
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    _SWULaw099Ask($caster, $remaining, $uids);
};
