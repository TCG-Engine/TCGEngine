<?php
// LAW_151
// Cost 1 - Profiteering Hunter - [Command] - Power 1 - HP 3
// Text: When Played: Another friendly unit gets +1/+1 for this phase.

// LAW_151 Profiteering Hunter — When Played: another friendly unit gets +1/+1 for this phase.
$whenPlayedAbilities["LAW_151:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $others = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $others[] = $mz;
    }
    if (empty($others)) return;
    SWUQueueChooseTarget(intval($player), $others, "Give_another_friendly_unit_+1/+1_for_this_phase", "APPLY_PHASE_BUFF|1|1|LAW_151");
};
