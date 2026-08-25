<?php
// JTL_176
// Cost 2 - Shoot Down - [Aggression]
// Text: Deal 3 damage to a space unit. If that unit is defeated this way, you may deal 2 damage to a base.

// ── JTL_176 Shoot Down (event) — deal 3 to the chosen space unit; if defeated, may deal 2 to a base. ──
$customDQHandlers["JTL_176#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    $uid = ($o !== null) ? intval($o->UniqueID ?? 0) : 0;
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
    if ($uid !== 0 && SWUFindMzByUID($uid) === null) { // defeated this way
        SWUQueueMayChooseTarget(intval($player), SWUAllBaseMzIDs(intval($player), 'any'),
            "You_may_deal_2_to_a_base", "Deal_2_to_a_base", "DEAL_BASE_DAMAGE|2");
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_176:0"] = function($player, $mzID = '') {
// Shoot Down — deal 3 to a space unit; if it is defeated this way, you may
                          // deal 2 to a base (continuation JTL_176).
            global $playerID;
            $playerID = intval($player);
            $targets = SWUAllUnits(null, 'Space');   // "A space unit" is unqualified -> both teams
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_3_to_a_space_unit", "JTL_176#0");
            return;
};
