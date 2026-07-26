<?php
// LOF_051
// Cost 1 - Jedi Holocron - [Vigilance,Heroism] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a Force unit. / Attached unit gains: "On Attack: You may heal 3 damage from another unit."

// LOF_051 Jedi Holocron — attached unit gains "On Attack: may heal 3 damage from another unit."
$onAttackAbilities["LOF_051:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Heal_3_from_another_unit?", "Choose_a_unit", "HEAL_TARGET|3");
};
