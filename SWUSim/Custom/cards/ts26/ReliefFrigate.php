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
    foreach (['myBase-0', 'theirBase-0'] as $b) {
        if ($b === $lastDecision) continue;                      // skip the chosen base
        $bp = ($b === 'myBase-0') ? intval($player) : OtherPlayer(intval($player));
        OnHealBase(intval($player), $bp, 3);
    }
};
