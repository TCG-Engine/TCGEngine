<?php
// ASH_047
// Cost 3 - Gar Saxon - Coveting Power - [Vigilance,Villainy] - Power 3 - HP 4
// Text: When you play an upgrade on this unit: You may create a Mandalorian token. Use this ability only once each round.

$customDQHandlers["ASH_047#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'ASH_T01');
};

function Ash047UpgradeReaction($player)
{
  global $playerID;
  $playerID = intval($player);
  if (GlobalEffectCount(intval($player), 'SWU_ASH047_USED') > 0)
    return;
  AddGlobalEffects(intval($player), 'SWU_ASH047_USED');  // cleared at RegroupPhaseStart
  DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Create_a_Mandalorian_token?");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_047#0", 1);
}
