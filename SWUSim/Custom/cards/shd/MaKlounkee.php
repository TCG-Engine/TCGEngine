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
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 3, 'side' => 'any',
        'prompt' => "Deal_3_to_a_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_229:0"] = function($player, $mzID = '') {
// Ma Klounkee — "Return a friendly non-leader Underworld unit to its owner's hand.
                          // If you do, deal 3 damage to a unit."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && TraitContains($o, 'Underworld')) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;   // no friendly Underworld unit → no return, no damage
            SWUQueueChooseTarget(intval($player), $targets, "Return_a_friendly_Underworld_unit_to_hand", "SHD_229#0");
            return;
};
