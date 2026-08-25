<?php
// SOR_139
// Cost 2 - Force Choke - [Aggression,Villainy]
// Text: If you control a FORCE unit, this event costs [1 resource] less to play. / Deal 5 damage to a non-VEHICLE unit. That unit's controller draws a card.

// SOR_139 Force Choke — deal 5 to the chosen non-Vehicle unit, then THAT unit's controller draws.
// Custom (not DEAL_UNIT_DAMAGE) because the draw depends on the target's controller, captured BEFORE
// the damage in case the hit defeats the unit and cleans it up.
$customDQHandlers["SOR_139#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $controller = intval($o->Controller ?? 0);
    SWUDealDamageToUnit($lastDecision, 5, intval($player));
    if ($controller > 0) DoDrawCard($controller, 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_139:0"] = function($player, $mzID = '') {
// Force Choke — "Deal 5 damage to a non-Vehicle unit. That unit's controller
                          // draws a card." (The cost reduction lives in $playCostModifiers.)
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (HasTrait($o->CardID, 'Vehicle')) continue;
                $targets[] = $mz;
            }
            SWUQueueChooseTarget(intval($player), $targets, "Deal_5_damage_to_a_non-Vehicle_unit", "SOR_139#0");
            return;
};
