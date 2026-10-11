<?php
// SHD_008
// Cost 6 - Boba Fett - Daimyo - [Command,Heroism] - Power 4 - HP 7
// Text: When you play a unit that has 1 or more keywords: You may exhaust this leader. If you do, give a friendly unit +1/+0 for this phase.
// DeployText: Each other friendly unit that has 1 or more keywords gets +1/+0.
// Epic Action: If you control 6 or more resources, deploy this leader.

$customDQHandlers["SHD_008#front"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'SHD_008' && empty($l->removed)) { $l->Ready = false; SWULogLeaderExhaustCost(intval($player), 'SHD_008'); break; } }  // exhaust the leader (cost)
    unset($l);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'APPLY_PHASE_BUFF|1|0|SHD_008',
        'side'         => 'friendly',
        'prompt'       => "Give_a_friendly_unit_+1/+0_for_this_phase",
    ]);
};

function Shd008FrontReaction($player): void
{
  global $playerID;
  $playerID = intval($player);
  if (!_SWULeaderReadyUndeployed(intval($player), 'SHD_008'))
    return;
  // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): a teammate's unit is friendly,
  // so this pool is SWUFriendlyUnits(). ⚠ NOT SWUControlledUnits() — "a unit you control", an
  // ability COST, and "attack with" all stay 'my'. Degrades to 'my' outside a team game.
  $friendly = SWUFriendlyUnits(null, AnyUnitFilter);
  if (empty($friendly))
    return;   // nothing to buff → don't bother offering the exhaust
  DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Boba_Fett_to_give_a_friendly_unit_+1/+0?");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_008#front", 1);
}
