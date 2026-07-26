<?php
// ASH_253
// Cost 3 - Yellow Aces Bomber - [Heroism] - Power 2 - HP 4
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: If this unit is upgraded, deal 2 damage to a base.

// ASH_253 Yellow Aces Bomber — On Attack: if this unit is upgraded, deal 2 damage to a base.
$onAttackAbilities["ASH_253:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self) || !_SWUIsUpgraded($self)) return;
    SWUQueueChooseTarget(intval($player), ['theirBase-0', 'myBase-0'], "Deal_2_damage_to_a_base", "DEAL_BASE_DAMAGE|2");
};
