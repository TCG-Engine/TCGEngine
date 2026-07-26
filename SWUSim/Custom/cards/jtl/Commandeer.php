<?php
// JTL_235
// Cost 5 - Commandeer - [Cunning]
// Text: Take control of a non-leader Vehicle unit that costs 6 or less without a Pilot on it. If you do, ready it. At the start of the next regroup phase, return that unit to its owner's hand.

// ── JTL_235 Commandeer — take control of the chosen Vehicle, ready it, mark it for return next regroup.
$customDQHandlers["JTL_235#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SWUTakeControlOfUnit(intval($player), $lastDecision);   // moves into the caster's arena
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    $u = GetZoneObject($mz);
    if ($u !== null && empty($u->removed)) {
        OnReadyCard(intval($player), $mz);                                 // ready it
        AddGlobalEffects(intval($player), 'SWU_JTL235_RETURN_' . $uid);    // return at next regroup
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_235:0"] = function($player, $mzID = '') {
// Commandeer — "Take control of a non-leader Vehicle unit that costs 6 or less
                          // without a Pilot. Ready it. At the start of the next regroup phase, return it."
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch('myGroundArena',    NonLeaderUnitFilter), ZoneSearch('mySpaceArena',    NonLeaderUnitFilter),
                ZoneSearch('theirGroundArena', NonLeaderUnitFilter), ZoneSearch('theirSpaceArena', NonLeaderUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (HasTrait($o->CardID, 'Vehicle') && intval(CardCost($o->CardID)) <= 6 && !_SWUHasPilotOnIt($o)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Take_control_of_a_Vehicle_(returns_next_regroup)", "JTL_235#0");
            return;
};
