<?php
// TWI_181
// Cost 3 - Elite P-38 Starfighter - [Cunning,Villainy] - Power 3 - HP 2
// Text: When Played/When Defeated: You may deal 1 damage to a unit.

// TWI_181 Elite P-38 Starfighter — "When Played/When Defeated: You may deal 1 damage to a unit."
$whenPlayedAbilities["TWI_181:0"] = $whenDefeatedAbilities["TWI_181:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_1_damage_to_a_unit", "Deal_1_damage_to_a_unit", "DEAL_UNIT_DAMAGE|1");
};
