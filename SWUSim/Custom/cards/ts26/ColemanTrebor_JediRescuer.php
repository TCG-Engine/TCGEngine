<?php
// TS26_19
// Cost 1 - Coleman Trebor - Jedi Rescuer - [Vigilance,Aggression,Heroism] - Power 2 - HP 2
// Text: Hidden (This unit can't be attacked if he was played this phase.) / When Played: Deal 1 damage to each enemy base. Heal 1 damage from your base for each damage dealt this way.

// TS26_19 Coleman Trebor — When Played: deal 1 to each enemy base, then heal 1 from your base per
// damage dealt this way. (2-player: one enemy base → deal 1 → heal 1.)
$whenPlayedAbilities["TS26_19:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "Deal 1 damage to EACH enemy base" — a fan-out. This took OtherPlayer(), so above two seats only
    // one enemy base was hit AND the heal was undercounted to match.
    // "Heal 1 damage from your base FOR EACH DAMAGE DEALT this way" — measure what actually landed
    // instead of assuming the 1 got through. JTL_074 Close the Shield Gate (or any other prevention on
    // that base) stops it, and then there is nothing to heal. Healing unconditionally handed the
    // Coleman player a free point of healing off damage that never happened.
    $dmgOf = function(int $seat): int {
        foreach (GetBase($seat) as $b) { if (empty($b->removed)) return intval($b->Damage ?? 0); }
        return 0;
    };
    // Measure per base and SUM: "heal 1 for each damage dealt this way" counts every point that
    // actually landed across every enemy base, so at four seats this heals up to 2 (or 3), not 1.
    $dealt = 0;
    foreach (OpponentsOf(intval($player)) as $opp) {
        $before = $dmgOf($opp);
        SWUDealDamageToBase(1, $opp);
        $dealt += max(0, $dmgOf($opp) - $before);
    }
    if ($dealt > 0) OnHealBase(intval($player), intval($player), $dealt);
};
