<?php
// LOF_102
// Cost 2 - Yoda's Lightsaber - [Command,Heroism] - Upgrade Power 3 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / When Played: You may use the Force (lose your Force token). If you do, heal 3 damage from a base.

// LOF_102 Yoda's Lightsaber — When Played: may use the Force → heal 3 damage from a base.
$whenPlayedAbilities["LOF_102:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_heal_3_from_a_base?", "LOF_102#0");
};

$customDQHandlers["LOF_102#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    SWUOfferBaseTarget(intval($player), ['continuation'=>'HEAL_TARGET','amount'=>3,'prompt'=>"Heal_3_damage_from_a_base"]);
};
