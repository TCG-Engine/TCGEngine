<?php
// LAW_181
// Cost 2 - Cloud-Rider Veteran - [Aggression,Heroism] - Power 1 - HP 4
// Text: On Attack: Deal 2 damage to a base.

// LAW_181 Cloud-Rider Veteran — On Attack: deal 2 damage to a base. "A base" has no "enemy" qualifier, so
// the attacker MAY choose their own base or the enemy base.
$onAttackAbilities["LAW_181:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_2_damage_to_a_base", "DEAL_BASE_DAMAGE|2");
};
