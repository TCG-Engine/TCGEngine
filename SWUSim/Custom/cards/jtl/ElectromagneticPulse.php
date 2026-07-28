<?php
// JTL_230
// Cost 1 - Electromagnetic Pulse - [Cunning]
// Text: Deal 2 damage to a Droid or Vehicle unit and exhaust it.

// ── JTL_230 Electromagnetic Pulse (event continuation) — deal 2 to the chosen unit, then exhaust it
// (if it survived). ──────────────────────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_230#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    $uid = ($o !== null) ? intval($o->UniqueID ?? 0) : 0;
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    if ($uid !== 0) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') OnExhaustCard(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_230:0"] = function($player, $mzID = '') {
// Electromagnetic Pulse — deal 2 to a Droid or Vehicle unit and exhaust it.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (HasTrait($o->CardID, 'Droid') || HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Deal_2_and_exhaust_a_Droid_or_Vehicle_unit", "JTL_230#0");
            return;
};
