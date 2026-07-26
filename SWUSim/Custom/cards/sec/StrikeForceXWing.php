<?php
// SEC_152
// Cost 4 - Strike Force X-Wing - [Aggression,Heroism] - Power 3 - HP 2
// Text: When Played: You may deal 2 damage to a ready unit. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_152 Strike Force X-Wing — When Played: you may deal 2 to a READY unit. (Plot auto.)
$whenPlayedAbilities["SEC_152:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_2_to_a_ready_unit?", "Choose_a_ready_unit", "DEAL_UNIT_DAMAGE|2");
};
