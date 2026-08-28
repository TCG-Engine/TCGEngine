<?php
// SOR_135
// Cost 8 - Emperor Palpatine - Master of the Dark Side - [Aggression,Villainy] - Power 6 - HP 6
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When Played: Deal 6 damage divided as you choose among enemy units.

// SOR_135 Emperor Palpatine (Unit) — Overwhelm (auto-wired) + When Played: deal 6 damage divided
// as you choose among enemy units. MZSPLITASSIGN over both enemy arenas; the full 6 must be
// assigned (UI-gated). SWUDealSplitDamage applies it SIMULTANEOUSLY (apply-all then defeat sweep).
$whenPlayedAbilities["SOR_135:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits('their');
    if (empty($targets)) return;   // no enemy units → fizzle
    // CR 9.12 — a unit's ability deals its damage, so Palpatine himself is the source.
    SWUOfferSplitDamage(intval($player), 6, $targets, "Divide_6_damage_among_enemy_units",
        false, false, $mzID);
};
