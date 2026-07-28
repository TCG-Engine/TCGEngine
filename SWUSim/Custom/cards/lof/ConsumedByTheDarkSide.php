<?php
// LOF_239
// Cost 2 - Consumed by the Dark Side - [Villainy]
// Text: Give 2 Experience tokens to a unit, then deal 2 damage to it.

// LOF_239 Consumed by the Dark Side — give the chosen unit 2 Experience tokens, then deal 2 damage to it.
$customDQHandlers["LOF_239#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    DoGiveExperienceToken(intval($player), $lastDecision, false);   // fire the ASH_208 observer once, after both
    DoGiveExperienceToken(intval($player), $lastDecision, false);
    $h239 = GetZoneObject($lastDecision);
    if ($h239 !== null && empty($h239->removed)) _SWUAsh208OnUpgradeAttach(intval($player), $h239);   // one attach event
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_239:0"] = function($player, $mzID = '') {
// Consumed by the Dark Side — "Give 2 Experience tokens to a unit, then deal 2
                          // damage to it."
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_2_Experience_then_deal_2_to_a_unit", "LOF_239#0");
            return;
};
