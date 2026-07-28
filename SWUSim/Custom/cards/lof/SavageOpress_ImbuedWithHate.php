<?php
// LOF_137
// Cost 6 - Savage Opress - Imbued With Hate - [Aggression,Villainy] - Power 9 - HP 6
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When Played/When Defeated: You may use the Force (lose your Force token). If you don't, deal 9 damage to your base.

// LOF_137 Savage Opress — Overwhelm + When Played/When Defeated: you may use the Force. If you DON'T,
// deal 9 damage to your base. (Inverted: declining — or being unable to use it — is the punished branch.)
$whenPlayedAbilities["LOF_137:0"]   =
$whenDefeatedAbilities["LOF_137:0"] = function($player, $mzID) {
    if (!PlayerHasTheForce(intval($player))) { SWUDealDamageToBase(9, intval($player)); return; }
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip: "Use_the_Force?_If_not,_deal_9_damage_to_your_base");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_137#0", 1);
};

$customDQHandlers["LOF_137#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === 'YES') { UseTheForce(intval($player)); return; }
    SWUDealDamageToBase(9, intval($player)); // declined → punished
};
