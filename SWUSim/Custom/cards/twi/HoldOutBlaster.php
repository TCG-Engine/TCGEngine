<?php
// TWI_256
// Cost 1 - Hold-Out Blaster - Upgrade Power 1 - Upgrade HP 0
// Text: Attach to a non-Vehicle unit. / When Played: You may have attached unit deal 1 damage to a ground unit.

// TWI_256 Hold-Out Blaster — "When Played: You may have attached unit deal 1 damage to a ground unit."
// (Upgrade; $mzID = the host. Attach restriction handled in SWUGetUpgradeValidTargets.)
$whenPlayedAbilities["TWI_256:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_have_attached_unit_deal_1_to_a_ground_unit", "Deal_1_damage_to_a_ground_unit", "DEAL_UNIT_DAMAGE|1");
};
