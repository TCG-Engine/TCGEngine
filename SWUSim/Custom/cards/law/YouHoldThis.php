<?php
// LAW_085
// Cost 1 - You Hold This - [Aggression,Cunning]
// Text: Choose a friendly non-leader unit. An opponent takes control of it. If they do, deal 4 damage to another unit in the same arena.

// LAW_085 You Hold This — step 0: opponent takes control of the chosen friendly unit; then deal 4 to
// another unit in the SAME arena (any controller, excluding the taken unit).
// The caster picked the unit; now pick WHICH opponent receives it. Carried by UID — the pick is
// interactive and the arena can reindex before the continuation runs.
$customDQHandlers["LAW_085#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    if ($uid <= 0) return;
    SWUQueueChooseOpponent(intval($player), "LAW_085#2|{$uid}", "Choose_an_opponent_to_take_control");
};

$customDQHandlers["LAW_085#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $opp = SWUPickedOpponent($lastDecision);
    if ($uid <= 0 || $opp <= 0 || $opp === intval($player)) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    // Re-enter the original step via the QUEUE rather than by calling the handler directly: PASSPARAMETER
    // puts the (re-resolved) unit mzID into $lastDecision, and the CUSTOM then runs LAW_085#0 with the
    // chosen seat in its Param — exactly the shape that handler already expects.
    // ⚠ Invoking $customDQHandlers[...] inline does NOT work here; the registry is not reachable that way
    //   from inside a handler, and the step silently did nothing (caught by this card's four-seat pin).
    DecisionQueueController::AddDecision(intval($player), "PASSPARAMETER", $mz, 1);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_085#0|" . $opp, 1);
};

$customDQHandlers["LAW_085#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    // ⚠ NO ?: FALLBACK. The seat always rides the Param now; a missing one is a NO-OP, never a guess
    // (§5 defect class 3 — a fallback that invents a seat is the bug, not the safety net).
    $opp = intval($parts[0] ?? 0);
    if ($opp <= 0 || $opp === intval($player)) return;
    $isSpace = (strpos($lastDecision, 'Space') !== false);
    $chosen = GetZoneObject($lastDecision);          // capture UID BEFORE control transfer (preserved)
    $takenUID = SWUObjUID($chosen, 0);
    $newMz = SWUTakeControlOfUnit($opp, $lastDecision);
    if ($newMz === '') return;                       // take-control blocked (e.g. LAW_149) → no deal
    $playerID = intval($player);
    $zones = $isSpace ? ["mySpaceArena", "theirSpaceArena"] : ["myGroundArena", "theirGroundArena"];
    $targets = [];
    foreach ($zones as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval($o->UniqueID ?? 0) === $takenUID) continue;   // "another unit"
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_4_to_another_unit_in_the_same_arena", "DEAL_UNIT_DAMAGE|4");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_085:0"] = function($player, $mzID = '') {
// You Hold This — "Choose a friendly non-leader unit. An opponent takes
                          // control of it. If they do, deal 4 damage to another unit in the same arena."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($friendly)) return;
            // ORDER: unit first, then WHO takes it — the printed order ("Choose a friendly non-leader
            // unit. An opponent takes control of it"), and it keeps the existing 2-player prompt sequence
            // byte-identical since the picker auto-resolves invisibly at one eligible opponent.
            // ⚠ NO $eligible filter: any live opponent can receive a unit. LAW_149 Rey blocks EVERY
            // opponent equally (a property of the unit, not the seat), so it never shrinks the menu.
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-leader_unit", "LAW_085#1");
            return;
};
