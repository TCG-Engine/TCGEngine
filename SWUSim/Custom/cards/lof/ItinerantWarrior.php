<?php
// LOF_048
// Cost 4 - Itinerant Warrior - [Vigilance,Heroism] - Power 4 - HP 4
// Text: Shielded (When you play this unit, give a Shield token to it.) / When Played: You may use the Force (lose your Force token). If you do, heal 3 damage from a base.

// LOF_048 Itinerant Warrior — Shielded + When Played: may use the Force → heal 3 damage from a base.
$whenPlayedAbilities["LOF_048:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_heal_3_from_a_base?", "LOF_048#0");
};

$customDQHandlers["LOF_048#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    SWUQueueChooseTarget(intval($player), ["myBase-0", "theirBase-0"], "Heal_3_damage_from_a_base", "HEAL_TARGET|3");
};
