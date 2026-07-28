<?php
// JTL_129
// Cost 4 - Focus Fire - [Command]
// Text: Choose a unit. Each friendly Vehicle unit in the same arena deals damage equal to its power to that unit.

// ── JTL_129 Focus Fire — each friendly Vehicle in the chosen unit's arena deals its power to it. ──────
$customDQHandlers["JTL_129#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $arena = $obj->Location ?? 'GroundArena';   // 'GroundArena' or 'SpaceArena'
    $sum = 0;
    foreach (ZoneSearch('my' . $arena, AnyUnitFilter) as $mz) {
        $u = GetZoneObject($mz);
        if ($u !== null && empty($u->removed) && HasTrait($u->CardID ?? '', 'Vehicle')) $sum += intval(ObjectCurrentPower($u));
    }
    if ($sum > 0) SWUDealDamageToUnit($lastDecision, $sum, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_129:0"] = function($player, $mzID = '') {
// Focus Fire — "Choose a unit. Each friendly Vehicle unit in the same arena
                          // deals damage equal to its power to that unit."
            global $playerID;
            $playerID = intval($player);
            // A unit is only a legal target if at least one friendly Vehicle shares its arena — otherwise
            // the effect would deal 0 damage, and such a zero-effect selection is disallowed.
            $friendlyVehicleIn = function(string $arenaZone): bool {
                foreach (ZoneSearch($arenaZone, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Vehicle')) return true;
                }
                return false;
            };
            $targets = [];
            if ($friendlyVehicleIn('myGroundArena')) {
                $targets = array_merge($targets, ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('theirGroundArena', AnyUnitFilter));
            }
            if ($friendlyVehicleIn('mySpaceArena')) {
                $targets = array_merge($targets, ZoneSearch('mySpaceArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
            }
            $targets = array_values($targets);
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Each_friendly_Vehicle_in_that_arena_deals_its_power", "JTL_129#0");
            return;
};
