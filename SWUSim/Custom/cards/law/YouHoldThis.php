<?php
// LAW_085
// Cost 1 - You Hold This - [Aggression,Cunning]
// Text: Choose a friendly non-leader unit. An opponent takes control of it. If they do, deal 4 damage to another unit in the same arena.

// LAW_085 You Hold This — step 0: opponent takes control of the chosen friendly unit; then deal 4 to
// another unit in the SAME arena (any controller, excluding the taken unit).
$customDQHandlers["LAW_085#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $opp = intval($parts[0] ?? OtherPlayer(intval($player)));
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
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-leader_unit",
                "LAW_085#0|" . OtherPlayer(intval($player)));
            return;
};
