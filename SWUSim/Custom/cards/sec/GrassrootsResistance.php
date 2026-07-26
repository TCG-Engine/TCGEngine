<?php
// SEC_258  |  Reprints: ASH_258
// Cost 4 - Grassroots Resistance - [Heroism]
// Text: Deal 3 damage to a unit. / Heal 3 damage from your base.

// ASH_258 Grassroots Resistance — continuation: deal 3 to the chosen unit (the base-heal already happened).
$customDQHandlers["ASH_258#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_258:0"] = function($player, $mzID = '') {
// Grassroots Resistance — "Deal 3 to a unit. Heal 3 from your base."
            global $playerID; $playerID = intval($player);
            OnHealBase(intval($player), intval($player), 3);
            $units = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Deal_3_to_a_unit", "DEAL_UNIT_DAMAGE|3");
            return;
};
