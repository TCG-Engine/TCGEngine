<?php
// SHD_229
// Cost 1 - Ma Klounkee - [Cunning]
// Text: Return a friendly non-leader Underworld unit to its owner's hand. If you do, deal 3 damage to a unit.

// ─── SHD_229 Ma Klounkee (Event) continuation ─────────────────────────────────
// Return the chosen friendly Underworld unit to hand; if it returned, deal 3 damage to a unit.
$customDQHandlers["SHD_229#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (!SWUBounceUnit(intval($player), $lastDecision)) return;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_3_to_a_unit", "DEAL_UNIT_DAMAGE|3");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_229:0"] = function($player, $mzID = '') {
// Ma Klounkee — "Return a friendly non-leader Underworld unit to its owner's hand.
                          // If you do, deal 3 damage to a unit."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Underworld')) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;   // no friendly Underworld unit → no return, no damage
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_friendly_Underworld_unit_to_hand", "SHD_229#0");
            return;
};
