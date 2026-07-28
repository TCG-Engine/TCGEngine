<?php
// SOR_056
// Cost 6 - Bendu - The One in the Middle - [Vigilance,Vigilance] - Power 4 - HP 7
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / On Attack: The next non-[Heroism], non-[Villainy] card you play this phase costs [2 resources] less.

// SOR_056 Bendu — On Attack: arm the one-shot "next non-Heroism/non-Villainy card you play this phase
// costs 2 less" charge (consumed in ActivateCard; the −2 lives in SWUComputePlayCost).
$onAttackAbilities["SOR_056:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_NEUTRAL_DISCOUNT');
};
