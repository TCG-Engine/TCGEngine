<?php
// LOF_149
// Cost 6 - Mace Windu - Leaping into Action - [Aggression,Heroism] - Power 6 - HP 6
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When Played: You may use the Force (lose your Force token). If you do, deal 4 damage to a unit.

// LOF_149 Mace Windu — Overwhelm + When Played: may use the Force → deal 4 damage to a unit.
$whenPlayedAbilities["LOF_149:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_deal_4_to_a_unit?", "LOF_149#0");
};

$customDQHandlers["LOF_149#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_4_damage_to_a_unit", "DEAL_UNIT_DAMAGE|4");
};
