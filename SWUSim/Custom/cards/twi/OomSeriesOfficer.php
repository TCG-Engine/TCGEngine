<?php
// TWI_131
// Cost 2 - OOM-Series Officer - [Aggression,Villainy] - Power 2 - HP 1
// Text: When Defeated: Deal 2 damage to a base.

// TWI_131 OOM-Series Officer — "When Defeated: Deal 2 damage to a base."
$whenDefeatedAbilities["TWI_131:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), ["myBase-0", "theirBase-0"], "Deal_2_damage_to_a_base", "DEAL_BASE_DAMAGE|2");
};
