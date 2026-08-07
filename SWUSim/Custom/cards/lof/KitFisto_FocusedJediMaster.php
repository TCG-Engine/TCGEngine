<?php
// LOF_011
// Cost 5 - Kit Fisto - Focused Jedi Master - [Aggression,Heroism] - Power 1 - HP 6
// Text: Action [1 resource, Exhaust]: If you attacked with a Jedi unit this phase, deal 2 damage to a unit.
// DeployText: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / This unit gets +1/+0 for each other friendly Jedi unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

// LOF_011 Kit Fisto — Action [1 resource, Exhaust]: If you attacked with a Jedi unit this phase, deal 2
// damage to a unit.
$leaderAbilities["LOF_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_ATTACKED_JEDI') <= 0) { SWUAfterAction($player); return; }
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                           ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_2_damage_to_a_unit", "LOF_011#0");
};

$customDQHandlers["LOF_011#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDealDamageToUnit($lastDecision, 2, intval($player));
    SWUAfterAction(intval($player));
};
