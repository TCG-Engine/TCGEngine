<?php
// ASH_157
// Cost 4 - Danger Squadron Wingmen - [Aggression,Heroism] - Power 4 - HP 5
// Text: On Attack: You may give an Advantage token to another unit.

// ASH_157 Danger Squadron Wingmen — On Attack: you may give an Advantage token to another unit.
$onAttackAbilities["ASH_157:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID); $uid = SWUObjUID($self, 0);
    $targets = [];
    foreach (SWUAllUnits() as $mz) { $o = GetZoneObject($mz); if ($o && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $targets[] = $mz; }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_an_Advantage_token_to_another_unit?", "Choose_a_unit", "GIVE_ADVANTAGE");
};
