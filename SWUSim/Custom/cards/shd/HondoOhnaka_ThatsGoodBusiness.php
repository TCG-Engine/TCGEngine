<?php
// SHD_005
// Cost 6 - Hondo Ohnaka - That's Good Business - [Command,Villainy] - Power 3 - HP 7
// Text: When you play a card using Smuggle: You may exhaust this leader. If you do, give an Experience token to a unit.
// DeployText: Raid 1 (This unit gets +1/+0 while attacking.) / When you play a card using Smuggle: You may give an Experience token to a unit.
// Epic Action: If you control 6 or more resources, deploy this leader.

$customDQHandlers["SHD_005#exhaust"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'SHD_005' && empty($l->removed)) { $l->Ready = false; break; } }  // exhaust the leader (cost)
    unset($l);
    GiveTokenUpgrade(intval($player), '', [
        'friendlyOnly' => false,
        'prompt' => "Give_a_unit_an_Experience_token",
    ]);
};

function Shd005FrontReaction($player): void
{
  global $playerID;
  $playerID = intval($player);
  // DEPLOYED side: "When you play a card using Smuggle: You may give an Experience token to a unit."
  // No exhaust cost (that is the FRONT side's price), so there is no YES/NO-to-pay step and readiness
  // is irrelevant — the offer is simply a declinable pick over every unit in play. This arm did not
  // exist, so a deployed Hondo never reacted to Smuggle ("deployed side unimplemented" family).
  if (_SWULeaderDeployed(intval($player), 'SHD_005')) {
    if (empty(SWUAllUnits()))
      return;                        // no unit to receive the token → no offer
    GiveTokenUpgrade(intval($player), '', [
      'friendlyOnly' => false,
      'may'          => true,
      'prompt'       => "Give_a_unit_an_Experience_token",
      'question'     => "Give_a_unit_an_Experience_token?",
    ]);
    return;
  }
  if (!_SWULeaderReadyUndeployed(intval($player), 'SHD_005'))
    return;
  if (empty(SWUAllUnits()))
    return;   // no unit to receive the Experience token → no offer
  DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Hondo_to_give_a_unit_an_Experience_token?");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_005#exhaust", 1);
}
