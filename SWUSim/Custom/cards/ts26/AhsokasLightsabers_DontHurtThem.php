<?php
// TS26_35
// Cost 4 - Ahsoka's Lightsabers - Don't Hurt Them - [Cunning,Vigilance,Heroism] - Upgrade Power 2 - Upgrade HP 3
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "On Attack/When Defeated: You may give a Shield token to an enemy unit. If you do, the next event you play this phase costs 2 resources less."

$onAttackAbilities["TS26_35:0"] = function($player, $mzID) { _SWUTs26035Offer(intval($player)); };

$customDQHandlers["TS26_35#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    DoGiveShieldToken(intval($player), $lastDecision);
    AddGlobalEffects(intval($player), 'SWU_TS26035_DISCOUNT_NEXT');   // next event this phase costs 2 less
};
