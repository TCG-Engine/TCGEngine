<?php
// ASH_047
// Cost 3 - Gar Saxon - Coveting Power - [Vigilance,Villainy] - Power 3 - HP 4
// Text: When you play an upgrade on this unit: You may create a Mandalorian token. Use this ability only once each round.

$customDQHandlers["ASH_047#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;   // declined → the round is NOT spent
    global $playerID; $playerID = intval($player);
    SWUSpendUnitRoundUse(intval($parts[0] ?? 0));   // spend THIS Gar's round only on an accepted YES
    SWUCreateUnitToken(intval($player), 'ASH_T01');
};

function Ash047UpgradeReaction($player, int $garUID = 0)
{
  global $playerID;
  $playerID = intval($player);
  if (SWUUnitRoundUseSpent($garUID))
    return;   // once each round — per copy (NumUses on this Gar)
  // ⚠ The round is spent in ASH_047#0, on the accepted YES — NOT here. USER RULING 2026-09-07: a
  // triggered "you may" whose whole effect is the optional part is not USED by declining it, so a
  // later trigger the same round still offers. (Cleared at RegroupPhaseStart.)
  DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Create_a_Mandalorian_token?");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_047#0|{$garUID}", 1);
}
