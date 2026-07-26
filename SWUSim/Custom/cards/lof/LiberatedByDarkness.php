<?php
// LOF_189
// Cost 5 - Liberated by Darkness - [Cunning,Villainy]
// Text: Use the Force (lose your Force token). If you do, take control of a non-leader unit. At the start of the regroup phase, its owner takes control of it.

// LOF_189 Liberated by Darkness — take control of the chosen non-leader unit until regroup (TEMPORARY_STEAL
// returns it to its owner at RegroupPhaseStart). Mirrors SOR_224 Change of Heart.
$customDQHandlers["LOF_189#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $newMzID = SWUTakeControlOfUnit(intval($player), $lastDecision);
    if ($newMzID !== '') AddTurnEffect($newMzID, 'TEMPORARY_STEAL');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_189:0"] = function($player, $mzID = '') {
// Liberated by Darkness — "Use the Force (lose your Force token). If you do, take
                          // control of a non-leader unit. At the start of the regroup phase, its owner takes
                          // control of it." (Reuses the TEMPORARY_STEAL marker → returned at RegroupPhaseStart.)
            global $playerID; $playerID = intval($player);
            if (!PlayerHasTheForce(intval($player))) return; // can't pay the Force → whole effect fizzles
            UseTheForce(intval($player));
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
                $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Take_control_of_a_non-leader_unit_(until_regroup)", "LOF_189#0");
            return;
};
