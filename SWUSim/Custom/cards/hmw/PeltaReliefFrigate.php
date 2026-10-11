<?php
// HMW_091 Pelta Relief Frigate — Unit (Space) 5/4, cost 5, [Vigilance], Republic/Vehicle/Transport.
// "When Played: Heal 2 damage from a friendly base and 2 damage from a friendly unit."
// Mandatory, two independent heals. "friendly" spans the Team Suns team, so a teammate's base or unit is
// legal; in 2P the base is your own and resolves without a prompt. The Frigate itself is a friendly unit.

$whenPlayedAbilities["HMW_091:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $bases = ['myBase-0'];
    foreach (SWUTeammatesOf(intval($player)) as $mate) $bases[] = "p{$mate}Base-0";
    SWUQueueChooseTarget(intval($player), $bases, "Heal_2_damage_from_a_friendly_base", "HEAL_TARGET|2");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_091#0", 1);
};

$customDQHandlers["HMW_091#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), SWUFriendlyUnits(), "Heal_2_damage_from_a_friendly_unit", "HEAL_TARGET|2");
};
