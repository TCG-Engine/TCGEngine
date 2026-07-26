<?php
// LAW_109
// Cost 7 - Tantive IV - Carrying Hope - [Vigilance,Heroism] - Power 5 - HP 8
// Text: Restore 2 (When this unit attacks, heal 2 damage from your base.) / When Played: If a friendly unit was defeated this phase, heal 4 damage from your base.

// LAW_109 Tantive IV — Restore 2 + When Played: if a friendly unit was defeated this phase, heal 4 from base.
$whenPlayedAbilities["LAW_109:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') > 0) OnHealBase(intval($player), intval($player), 4);
};
