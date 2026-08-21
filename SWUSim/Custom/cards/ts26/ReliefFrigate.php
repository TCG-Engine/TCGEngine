<?php
// TS26_42
// Cost 5 - Relief Frigate - [Vigilance] - Power 3 - HP 7
// Text: When Played: Choose a base. Heal 3 damage from each other base.

// TS26_42 Relief Frigate — When Played: choose a base; heal 3 from each OTHER base.
$whenPlayedAbilities["TS26_42:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation'=>'TS26_42#0','prompt'=>"Choose_a_base_(heal_3_from_each_OTHER_base)"]);
};

$customDQHandlers["TS26_42#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    // "each OTHER base" = every base in the game except the chosen one. ⚠ Was a two-entry literal, so
    // in Twin Suns seats 3-4 were never healed; the owner also came from OtherPlayer() rather than the mzID.
    foreach (SWUAllBaseMzIDs(intval($player), 'any') as $b) {
        if ($b === $lastDecision) continue;                      // skip the chosen base
        OnHealBase(intval($player), SWUMzOwner($b, intval($player)), 3);
    }
};
