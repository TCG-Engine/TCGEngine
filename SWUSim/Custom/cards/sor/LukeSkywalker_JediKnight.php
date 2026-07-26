<?php
// SOR_051
// Cost 7 - Luke Skywalker - Jedi Knight - [Vigilance,Heroism] - Power 6 - HP 7
// Text: Restore 3 / When Played: Give an enemy unit -3/-3 for this phase. If a friendly unit was defeated this phase, give that enemy unit -6/-6 for this phase instead.

// SOR_051 Luke Skywalker — "When Played: Give an enemy unit -3/-3 for this phase. If a friendly
// unit was defeated this phase, give that enemy unit -6/-6 for this phase instead." (Restore 3 is
// keyword-wired.) The friendly-defeated condition reads the SWU_FRIENDLY_DEFEATED phase flag.
$whenPlayedAbilities["SOR_051:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $enemies = array_merge(
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena',  AnyUnitFilter)
    );
    $amount = GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') > 0 ? 6 : 3;
    SWUQueueChooseTarget(intval($player), $enemies,
        "Give_an_enemy_unit_-{$amount}/-{$amount}_for_this_phase", "APPLY_PHASE_DEBUFF|{$amount}|{$amount}|SOR_051");
};
