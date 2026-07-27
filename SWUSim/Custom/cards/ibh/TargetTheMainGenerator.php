<?php
// IBH_059 / IBH_071
// Cost 2 - Target the Main Generator - [Aggression]
// Text: Deal 2 damage to a base.

$whenPlayedAbilities["IBH_059:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>2,'prompt'=>"Deal_2_damage_to_a_base"]);
};
$whenPlayedAbilities["IBH_071:0"] = $whenPlayedAbilities["IBH_059:0"];
