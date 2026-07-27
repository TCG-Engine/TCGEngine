<?php
// JTL_040
// Cost 7 - Fleet Interdictor - [Vigilance,Villainy] - Power 6 - HP 6
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When Defeated: You may defeat a space unit that costs 3 or less.

// ── JTL_040 Fleet Interdictor — Sentinel (auto) + When Defeated: may defeat a space unit ≤3 cost. ────
$whenDefeatedAbilities["JTL_040:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'arena' => 'Space', 'may' => true,
        'extraFilter' => fn($o) => intval(CardCost($o->CardID)) <= 3,
        'question' => "You_may_defeat_a_space_unit_costing_3_or_less", 'prompt' => "Defeat_a_space_unit",
    ]);
};
