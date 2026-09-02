<?php
// HMW_080
// Cost 7 - Fambaa Shield Team - [Vigilance][Heroism] - Unit, Ground - Power 4 - HP 7
// Traits: Creature, Gungan - non-unique
// Text: When Played: Give a Shield token to each friendly ground unit without a Shield token on it.
//
// "EACH" is a LOOP, not a choice — no offer, no decision, nothing to decline. The closest precedent is
// ASH_064 The Armorer ("give a Shield token to each friendly unit with Shielded, including this one"),
// which is also a straight loop over a team-aware collector.
//
// THREE filters in one sentence, and each is a separate way to get this wrong:
//   • "FRIENDLY"          -> SWUFriendlyUnits(), i.e. SWUAllUnits('team', ...). Team-wide in a 2v2 (a
//                            teammate's unit is friendly though you do not CONTROL it) and degrading to
//                            your own board elsewhere, so Premier is byte-identical. Deliberately NOT
//                            SWUControlledUnits() and NOT a hand-rolled ZoneSearch('myGroundArena') —
//                            both are self-only and could never reach a teammate.
//   • "GROUND"            -> the 'Ground' arena argument. Without it the loop shields your space units.
//   • "without a Shield"  -> skip anything already carrying SOR_T02. This is the clause a careless loop
//                            silently ignores: it tops the unit up to two Shields instead of skipping
//                            it, and every other section still passes. Pinned by
//                            AlreadyShielded_DoesNotGetASecond.
//
// ⚠ The Fambaa is itself a friendly ground unit with no Shield, and a unit's When Played resolves after
// it has entered play — so it shields ITSELF too. The text says "each friendly ground unit", not "each
// OTHER", so there is no self-exclusion (AloneOnTheBoard_ShieldsOnlyITSELF).
//
// Giving a Shield appends a subcard; it never removes a unit, so the arena does not reindex mid-loop and
// the mzIDs collected up front stay valid (contrast a damage/defeat loop, which needs UID snapshots).

$whenPlayedAbilities["HMW_080:0"] = function ($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    foreach (SWUFriendlyUnits('Ground') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (SWUFindUpgradeIndex($o, 'SOR_T02') >= 0) continue;   // already has a Shield — skip, do not stack
        DoGiveShieldToken(intval($player), $mz);
    }
};
