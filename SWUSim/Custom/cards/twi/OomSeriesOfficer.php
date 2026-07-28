<?php
// TWI_131
// Cost 2 - OOM-Series Officer - [Aggression,Villainy] - Power 2 - HP 1
// Text: When Defeated: Deal 2 damage to a base.

// TWI_131 OOM-Series Officer — "When Defeated: Deal 2 damage to a base."
$whenDefeatedAbilities["TWI_131:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>2,'prompt'=>"Deal_2_damage_to_a_base"]);
};
