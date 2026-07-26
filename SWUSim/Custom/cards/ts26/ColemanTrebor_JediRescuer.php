<?php
// TS26_19
// Cost 1 - Coleman Trebor - Jedi Rescuer - [Vigilance,Aggression,Heroism] - Power 2 - HP 2
// Text: Hidden (This unit can't be attacked if he was played this phase.) / When Played: Deal 1 damage to each enemy base. Heal 1 damage from your base for each damage dealt this way.

// TS26_19 Coleman Trebor — When Played: deal 1 to each enemy base, then heal 1 from your base per
// damage dealt this way. (2-player: one enemy base → deal 1 → heal 1.)
$whenPlayedAbilities["TS26_19:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealDamageToBase(1, OtherPlayer(intval($player)));   // 2-player: one enemy base
    OnHealBase(intval($player), intval($player), 1);        // heal 1 per damage dealt (1 in 2-player)
};
