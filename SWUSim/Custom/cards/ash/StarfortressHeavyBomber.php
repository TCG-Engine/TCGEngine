<?php
// ASH_174
// Cost 5 - StarFortress Heavy Bomber - [Aggression] - Power 3 - HP 3
// Text: When Played: You may deal 6 damage to a non-<uq> ground unit.

// ASH_174 StarFortress Heavy Bomber — When Played: you may deal 6 damage to a non-unique ground unit.
$whenPlayedAbilities["ASH_174:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (SWUAllUnits(null, GroundArena) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && !CardUnique($o->CardID ?? '')) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Deal_6_to_a_non-unique_ground_unit?", "Choose_a_non-unique_ground_unit", "DEAL_UNIT_DAMAGE|6");
};
