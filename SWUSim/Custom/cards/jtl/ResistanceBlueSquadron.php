<?php
// JTL_102
// Cost 4 - Resistance Blue Squadron - [Command,Heroism] - Power 3 - HP 4
// Text: When Played: You may deal damage to a unit equal to the number of friendly space units.

// ── JTL_102 Resistance Blue Squadron — When Played: You may deal damage to a unit equal to the number
// of friendly space units (including itself, which has just entered). ───────────────────────────────
$whenPlayedAbilities["JTL_102:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $amount = count(SWUFriendlyUnits('Space'));   // "the number of FRIENDLY space units"
    if ($amount <= 0) return;
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_damage_to_a_unit", "Deal_{$amount}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|" . $amount);
};
