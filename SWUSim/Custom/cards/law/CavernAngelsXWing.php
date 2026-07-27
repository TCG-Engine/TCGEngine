<?php
// LAW_189
// Cost 2 - Cavern Angels X-Wing - [Aggression] - Power 2 - HP 1
// Text: When Defeated: Deal 2 damage to a base.

// LAW_189 Cavern Angels X-Wing — When Defeated: deal 2 damage to a base.
$whenDefeatedAbilities["LAW_189:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>2,'prompt'=>"Deal_2_to_a_base"]);
};
