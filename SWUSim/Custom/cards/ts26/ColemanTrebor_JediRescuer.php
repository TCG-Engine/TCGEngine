<?php
// TS26_19
// Cost 1 - Coleman Trebor - Jedi Rescuer - [Vigilance,Aggression,Heroism] - Power 2 - HP 2
// Text: Hidden (This unit can't be attacked if he was played this phase.) / When Played: Deal 1 damage to each enemy base. Heal 1 damage from your base for each damage dealt this way.

// TS26_19 Coleman Trebor — When Played: deal 1 to each enemy base, then heal 1 from your base per
// damage dealt this way. (2-player: one enemy base → deal 1 → heal 1.)
$whenPlayedAbilities["TS26_19:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));                    // 2-player: one enemy base
    // "Heal 1 damage from your base FOR EACH DAMAGE DEALT this way" — measure what actually landed
    // instead of assuming the 1 got through. JTL_074 Close the Shield Gate (or any other prevention on
    // that base) stops it, and then there is nothing to heal. Healing unconditionally handed the
    // Coleman player a free point of healing off damage that never happened.
    $dmgOf = function(int $seat): int {
        foreach (GetBase($seat) as $b) { if (empty($b->removed)) return intval($b->Damage ?? 0); }
        return 0;
    };
    $before = $dmgOf($opp);
    SWUDealDamageToBase(1, $opp);
    $dealt = max(0, $dmgOf($opp) - $before);
    if ($dealt > 0) OnHealBase(intval($player), intval($player), $dealt);
};
