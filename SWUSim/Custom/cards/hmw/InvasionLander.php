<?php
// HMW_111 Invasion Lander — Unit (Space) 3/7, cost 6, [Command][Villainy], Separatist/Vehicle/Transport.
// "When Played: Give each other friendly unit +2/+2 for this phase."
// A snapshot of the units in play now (units entering later this phase get nothing). "friendly" spans the
// Team Suns team; "other" excludes the Lander by UniqueID. SWUApplyPhaseBuff stacks per application, so two
// Landers give +4/+4.

$whenPlayedAbilities["HMW_111:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $selfUID = SWUObjUID(GetZoneObject($mzID));
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        SWUApplyPhaseBuff($mz, 2, 2, 'HMW_111');
    }
};
