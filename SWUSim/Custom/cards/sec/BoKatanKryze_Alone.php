<?php
// SEC_051
// Cost 9 - Bo-Katan Kryze - Alone - [Vigilance,Heroism] - Power 8 - HP 8
// Text: When Played: Give each enemy unit -3/-3 for this phase. / When an enemy unit is defeated: Give an Experience token to a friendly unit.

// SEC_051 Bo-Katan Kryze — When Played: give each enemy unit -3/-3 this phase. (Reactive defeat-observer
// in SWUCollectLeavePlayReactions.)
$whenPlayedAbilities["SEC_051:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUAllUnits('their') as $mz) {
        SWUApplyPhaseDebuff($mz, 3, 3, 'SEC_051');
    }
};
