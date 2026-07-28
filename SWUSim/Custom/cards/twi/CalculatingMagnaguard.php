<?php
// TWI_033
// Cost 3 - Calculating MagnaGuard - [Vigilance,Villainy] - Power 3 - HP 4
// Text: When Played/When a friendly unit is defeated: This unit gains Sentinel for this phase. (Units in this arena can't attack your non-Sentinel units or your base.)

// TWI_033 Calculating MagnaGuard — "When Played/When a friendly unit is defeated: This unit gains
// Sentinel for this phase." (The defeat half is in SWUCollectLeavePlayReactions.)
$whenPlayedAbilities["TWI_033:0"] = function($player, $mzID) {
    AddTurnEffect($mzID, 'SENTINEL');
};
