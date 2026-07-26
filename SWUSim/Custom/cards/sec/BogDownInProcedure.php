<?php
// SEC_234
// Cost 3 - Bog Down in Procedure - [Cunning]
// Text: Exhaust a unit. / You may disclose Cunning (reveal a card from your hand with this aspect icon). If you do, exhaust another unit.

// SEC_234 Bog Down in Procedure — #0: exhaust the chosen unit, then offer the Cunning disclose;
// #1: exhaust ANOTHER unit.
$customDQHandlers["SEC_234#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $firstUID = intval($o->UniqueID ?? 0);
    OnExhaustCard(intval($player), $lastDecision);
    SWUQueueDisclose(intval($player), ['Cunning'], "SEC_234#1|{$firstUID}",
        "Disclose_Cunning_to_exhaust_another_unit");
};

$customDQHandlers["SEC_234#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $firstUID = intval($parts[0] ?? 0);
    $others = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $firstUID) continue;   // "another unit"
        $others[] = $mz;
    }
    if (empty($others)) return;
    SWUQueueChooseTarget(intval($player), $others, "Exhaust_another_unit", "EXHAUST_UNIT");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_234:0"] = function($player, $mzID = '') {
// Bog Down in Procedure — "Exhaust a unit. You may disclose Cunning →
                          // exhaust another unit."
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Exhaust_a_unit", "SEC_234#0");
            return;
};
