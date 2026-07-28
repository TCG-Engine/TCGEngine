<?php
// ASH_027
// Cost 4 - Enoch - Solemn Servant - [Vigilance,Command,Villainy] - Power 4 - HP 5
// Text: When Defeated: You may deal up to 6 damage to your base. The next unit you play this phase costs 1 resource less for every 2 damage dealt this way.

// ASH_027 Enoch — When Defeated: you may deal up to 6 damage to your base. The next unit you play this
// phase costs 1 resource less for every 2 damage dealt this way.
$whenDefeatedAbilities["ASH_027:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "NUMBERCHOOSE", "0|6", 1, "Deal_how_much_damage_to_your_base?_(-1_cost_per_2)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_027#0", 1);
};

$customDQHandlers["ASH_027#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $dmg = max(0, min(6, intval($lastDecision)));
    if ($dmg <= 0) return;
    SWUDealDamageToBase($dmg, intval($player));
    $charges = intdiv($dmg, 2);   // 1 less per 2 damage
    for ($i = 0; $i < $charges; $i++) AddGlobalEffects(intval($player), 'SWU_ASH027_DISCOUNT_NEXT');
};
